<?php

namespace App;

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
        ];

        $this->csvWriter->writeHeaders($headers);

        $page = 1;
        $perPage = 100;

        do {
            $tickets = $this->zendeskApiClient->getTickets($page, $perPage);

            foreach ($tickets['tickets'] as $ticket) {
                $this->csvWriter->writeRow($this->formatTicket($ticket));
            }

            $page++;
        } while (!empty($tickets['tickets']));
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
            $ticket['assignee']['email'] ?? '',
            $ticket['requester_id'],
            $ticket['requester']['name'] ?? '',
            $ticket['requester']['email'] ?? '',
            $ticket['group_id'],
            $ticket['group']['name'] ?? '',
            $ticket['organization_id'],
            $ticket['organization']['name'] ?? '',
            implode('; ', array_map(function ($comment) {
                return $comment['body'];
            }, $ticket['comments'] ?? [])) // Comments
        ];
    }
}
