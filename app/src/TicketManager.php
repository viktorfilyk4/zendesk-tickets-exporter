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
                $promises[] = $this
                        ->zendeskApiClient
                        ->sendAsyncRequest("tickets?page=$page&per_page=$perPage");
                $page++;
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
        // TODO: Refactor code to fulfill fields' values with `must be`
        return [
            $ticket['id'],
            $ticket['description'],
            $ticket['status'],
            $ticket['priority'],
            $ticket['assignee_id'], // Agent ID
            '', // Must be Agent name
            '', // Must be Agent email
            $ticket['requester_id'], // Contact ID
            '', // Must be Contact name
            '', // Must be Contact email
            $ticket['group_id'],
            '', // Must be Group name,
            $ticket['organization_id'], // Company ID
            '', // Must be Company name
            '' // Must be Comments
        ];
    }
}
