<?php declare(strict_types=1);

namespace App;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;

class TicketManager
{
    private ZendeskApiClient $zendeskApiClient;
    private CSVWriter $csvWriter;
    const NUMBER_OF_CONCURRENT_REQUESTS = 10;
    const RATELIMIT_RESET = 65;
    const ASSIGN_MAPPINGS = [
        'agent' => [
            'idKey' => 'assignee_id',
            'responseKey' => 'users',
            'basePath' => 'users',
            'assignedFieldsToTicket' => 'name,email',
            'isShowMany' => true
        ],
        'contact' => [
            'idKey' => 'requester_id',
            'responseKey' => 'users',
            'basePath' => 'users',
            'assignedFieldsToTicket' => 'name,email',
            'isShowMany' => true
        ],
        'company' => [
            'idKey' => 'organization_id',
            'responseKey' => 'organizations',
            'basePath' => 'organizations',
            'assignedFieldsToTicket' => 'name',
            'isShowMany' => true
        ],
        'group' => [
            'idKey' => 'group_id',
            'responseKey' => 'groups',
            'basePath' => 'groups',
            'assignedFieldsToTicket' => 'name',
            'isShowMany' => false
        ]
    ];

    public function __construct(ZendeskApiClient $zendeskApiClient, CSVWriter $csvWriter)
    {
        $this->zendeskApiClient = $zendeskApiClient;
        $this->csvWriter = $csvWriter;
    }

    public function exportTicketsToCSV()
    {
        Logger::log("Writing headers to CSV file...");

        $headers = [
            'Ticket ID', 'Description', 'Status', 'Priority', 'Agent ID', 'Agent Name', 'Agent Email',
            'Contact ID', 'Contact Name', 'Contact Email', 'Group ID', 'Group Name', 'Company ID',
            'Company Name', 'Comments'
        ];

        $this->csvWriter->writeHeaders($headers);

        Logger::log("Start processing tickets...");

        $page = 1;
        $perPage = 100; // Zendesk limit per page
        $hasMorePages = true;

        while ($hasMorePages) {
            for ($i = 0; $i < self::NUMBER_OF_CONCURRENT_REQUESTS; $i++) {
                $promises[] = $this
                        ->zendeskApiClient
                        ->sendAsyncRequest("tickets?page=$page&per_page=$perPage");
                $page++;
            }

            try {
                $promisesResponses = Promise\Utils::unwrap($promises);
            } catch (\Throwable $e) {
                Logger::log("Could not fetch tickets. Stop exporting tickets...");

                // TODO: Recursively call this function again
                return;
            }

            foreach ($promisesResponses as $response) {
                $decodedResponse = Utils::decodeResponse($response);
                $tickets = $decodedResponse['tickets'] ?? [];

                if (count($tickets) > 0) {
                    $this->assignToEachTicket('agent', $tickets);
                    $this->assignToEachTicket('contact', $tickets);
                    $this->assignToEachTicket('company', $tickets);
                    $this->assignToEachTicket('group', $tickets);
                    $this->assignCommentsToEachTicket($tickets);

                    foreach ($tickets as $ticket) {
                        $formattedTicket = $this->formatTicket($ticket);
                        $this->csvWriter->writeRow($formattedTicket);
                    }
                }
                $hasMorePages = isset($decodedResponse['next_page']);
            }

            if (!$hasMorePages) {
                break;
            }
        }

        Logger::log("End processing tickets...");
    }

    private function assignToEachTicket(string $what, array &$tickets): void
    {
        $idKey = self::ASSIGN_MAPPINGS[$what]['idKey'];
        $responseKey = self::ASSIGN_MAPPINGS[$what]['responseKey'];
        $basePath = self::ASSIGN_MAPPINGS[$what]['basePath'];
        $assignedFieldsToTicket = self::ASSIGN_MAPPINGS[$what]['assignedFieldsToTicket'];
        $isShowMany = self::ASSIGN_MAPPINGS[$what]['isShowMany'];

        if ($isShowMany) {
            $ids = implode(',',
                array_unique(
                    array_column($tickets, $idKey)
                )
            );
            $path = "$basePath/show_many?ids=$ids";
        } else {
            $path = $basePath;
        }

        try {
            $response = $this->zendeskApiClient->sendRequest($path);
        } catch (GuzzleException $e) {
            Logger::log("Could not assign <b>$what</b> to tickets. Sleeping...");
            sleep(self::RATELIMIT_RESET);

            $this->assignToEachTicket($what, $tickets);
            return;
        }
        $decodedResponse = Utils::decodeResponse($response);
        $responseValues = $decodedResponse[$responseKey] ?? [];

        foreach ($tickets as &$ticket) {
            // Find `$key` by current ticket's `$idKey`
            $key = array_search($ticket[$idKey], array_column($responseValues, 'id'));

            $fieldsToAssign = explode(',', $assignedFieldsToTicket);

            foreach ($fieldsToAssign as $field) {
                $ticket[$what][$field] = $responseValues[$key][$field];
            }
        }
    }

    private function assignCommentsToEachTicket(array &$tickets): void
    {
        $z = 0;

        for ($i = 0; $i < count($tickets); $i++) {
            $ticketId = $tickets[$i]['id'];
            $promises[] = $this->zendeskApiClient->sendAsyncRequest("tickets/$ticketId/comments");

            $isLastTicket = ($i === (count($tickets) - 1));

            if ((count($promises) === self::NUMBER_OF_CONCURRENT_REQUESTS) || $isLastTicket) {
                try {
                    $promisesResponses = Promise\Utils::unwrap($promises);
                } catch (\Throwable $e) {
                    Logger::log("Could not assign <b>comments</b> to tickets. Sleeping...");
                    sleep(self::RATELIMIT_RESET);

                    $promises = [];
                    $i -= self::NUMBER_OF_CONCURRENT_REQUESTS;

                    continue;
                }

                foreach ($promisesResponses as $response) {
                    $decodedResponse = Utils::decodeResponse($response);
                    $comments = $decodedResponse['comments'] ?? [];
                    $tickets[$z]['comments'] = implode("\n\n", array_column($comments, 'body'));
                    $z++;
                }

                $promises = [];
            }
        }
    }

    private function formatTicket(array $ticket): array
    {
        return [
            $ticket['id'],
            $ticket['subject'], // Description
            $ticket['status'],
            $ticket['priority'],
            $ticket['assignee_id'], // Agent ID
            $ticket['agent']['name'],
            $ticket['agent']['email'],
            $ticket['requester_id'], // Contact ID
            $ticket['contact']['name'],
            $ticket['contact']['email'],
            $ticket['group_id'],
            $ticket['group']['name'],
            $ticket['organization_id'], // Company ID
            $ticket['company']['name'],
            $ticket['comments'],
        ];
    }
}
