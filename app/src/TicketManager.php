<?php

namespace App;

use GuzzleHttp\Promise;

class TicketManager
{
    private ZendeskApiClient $zendeskApiClient;
    private CSVWriter $csvWriter;
    const NUMBER_OF_CONCURRENT_REQUESTS = 5;

    public function __construct(ZendeskApiClient $zendeskApiClient, CSVWriter $csvWriter)
    {
        $this->zendeskApiClient = $zendeskApiClient;
        $this->csvWriter = $csvWriter;
    }

    public function exportTicketsToCSV()
    {
        $headers = [
            'Ticket ID', 'Description', 'Status', 'Priority', 'Agent ID', 'Agent Name', 'Agent Email',
            'Contact ID', 'Contact Name', 'Contact Email', 'Group ID', 'Group Name', 'Company ID',
            'Company Name', 'Comments'
        ];

        $this->csvWriter->writeHeaders($headers);

        $page = 1;
        $perPage = 100; // Zendesk limit per page
        $hasMorePages = true;

        while ($hasMorePages) {
            for ($i = 0; $i < self::NUMBER_OF_CONCURRENT_REQUESTS; $i++) {
                $promises[] = $this->zendeskApiClient->sendAsyncRequest("tickets", $page++, $perPage);
            }

            try {
                $promisesResults = Promise\Utils::unwrap($promises);
            } catch (\Throwable $e) {
                echo 'Could not send requests.<br>';
                return;
            }

            foreach ($promisesResults as $result) {
                $resultContent = $result->getBody()->getContents();
                $decodedResult = json_decode($resultContent, true);
                $tickets = $decodedResult['tickets'] ?? [];

                if (count($tickets) > 0) {
                    foreach ($tickets as $ticket) {
                        // make here parallel requests to Users (Agent and Contact), Groups, Organizations API
//                        $users[] = $this->zendeskApiClient->sendAsyncRequest('users');
//                        Promise\Utils::unwrap()

                        $formattedTicket = $this->formatTicket($ticket);
                        $this->csvWriter->writeRow($formattedTicket);
                    }
                }
                $hasMorePages = isset($decodedResult['next_page']);
            }

            if (!$hasMorePages) {
                break;
            }
        }

        echo 'Tickets have been written in file.<br>';
    }

    private function formatTicket(array $ticket): array
    {
        return [
            $ticket['id'],
            $ticket['description'],
            $ticket['status'],
            $ticket['priority'],
            $ticket['assignee_id'], // agent id
            $ticket['recipient'], // agent name
            $ticket['recipient'], // agent email
            $ticket['requester_id'],
            $ticket['requester']['name'] ?? '', // Contacts API
            $ticket['requester']['email'] ?? '', // Contacts API
            $ticket['group_id'],
            $ticket['group']['name'] ?? '',
            $ticket['organization_id'],
            $ticket['organization']['name'] ?? '',
            implode('; ', array_map(function ($comment) {
                return $comment['body'];
            }, $ticket['comments'] ?? []))
        ];
    }
}
