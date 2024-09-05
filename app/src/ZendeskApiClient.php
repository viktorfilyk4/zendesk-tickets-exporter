<?php

namespace App;

use GuzzleHttp\Client;

class ZendeskApiClient
{
    private $client;
    private $auth;
    private $baseUri;

    public function __construct(string $baseUri, string $email, string $apiToken)
    {
        $this->baseUri = $baseUri;
        $this->auth = [$email . '/token', $apiToken];

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'auth' => $this->auth,
            'headers' => ['Accept' => 'application/json']
        ]);
    }

    public function getTickets(int $page = 1, int $perPage = 100)
    {
        $response = $this->client->get("tickets.json", [
            'query' => [
                'page' => $page,
                'per_page' => $perPage
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
}
