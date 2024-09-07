<?php

namespace App;

use GuzzleHttp\Promise;
use React\Promise\PromiseInterface;

class TicketManager
{
    private $zendeskApiClient;
    private $csvWriter;

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
        ]; //15

        $this->csvWriter->writeHeaders($headers);

        // 1066 all tickets
        $page = 1;
        $perPage = 100;
        $hasMorePages = true;

        $client = $this->zendeskApiClient->get_client();

        while ($hasMorePages) {
            for ($i = 0; $i < 5; $i++) {
                $promises[] = $client->getAsync("tickets.json", [
                    'query' => [
                        'page' => $page++,
                        'per_page' => $perPage
                    ]
                ]);
            }

            $promisesResults = [];
            try {
                $promisesResults = Promise\Utils::unwrap($promises); // here PHP call stack waits
            } catch (\Throwable $e) {
                echo $e->getMessage();
            }

//            $decodedResult = null;
            // big block I see in browser
            foreach ($promisesResults as $result) {
                $resultContent = $result->getBody()->getContents();
                $decodedResult = json_decode($resultContent, true);
                $tickets = $decodedResult['tickets'] ?? [];

                $temp = false;
                $formattedTicket = [];
                if (count($tickets) > 0) {
                    foreach ($tickets as $ticket) {
                        $formattedTicket = $this->formatTicket($ticket);
                        if ($formattedTicket[0] === 500) {
                            $temp = true;
                        }
                        $this->csvWriter->writeRow($formattedTicket);
                    }
                }
                if ($temp) {
//                    var_dump($decodedResult);
                    var_dump(isset($decodedResult['next_page']));
                    // only writes 500 tickets to CSV file ???
                    // PUT DEBUGGER HERE
                }
                $hasMorePages = isset($decodedResult['next_page']);
            }

            if (!$hasMorePages) {
                var_dump('inside hasMorePages break');
                break;
            }
        }

        var_dump('Done');
    }

    private function formatTicket(array $ticket): array
    {
        return [
            $ticket['id'],
            $ticket['description'],
            $ticket['status'],
            $ticket['priority'],
            $ticket['assignee_id'],
            $ticket['assignee']['name'] ?? '',
            $ticket['assignee_email'],
            $ticket['requester_id'],
            $ticket['requester']['name'] ?? '',
            $ticket['requester']['email'] ?? '',
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
