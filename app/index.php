<?php declare(strict_types=1);

require '../vendor/autoload.php';

use App\CSVWriter;
use App\TicketManager;
use App\ZendeskApiClient;

$zendeskSubdomain = getenv('ZENDESK_SUBDOMAIN');
$zendeskEmail = getenv('ZENDESK_EMAIL');
$zendeskApiToken = getenv('ZENDESK_API_TOKEN');
$csvFilePath = './public/tickets.csv';

$zendeskApiClient = new ZendeskApiClient(
    "https://{$zendeskSubdomain}.zendesk.com/api/v2/",
    $zendeskEmail,
    $zendeskApiToken
);

$csvWriter = new CSVWriter($csvFilePath);

$ticketManager = new TicketManager($zendeskApiClient, $csvWriter);
$ticketManager->exportTicketsToCSV();

$csvWriter->close();

//echo "Tickets are exported to {$csvFilePath}\n";
