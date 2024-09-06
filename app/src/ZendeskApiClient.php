<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

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

    public function getAsyncTickets(int $page, int $perPage = 100): Promise\PromiseInterface
    {
        return $this->client->getAsync("tickets.json", [
            'query' => [
                'page' => $page,
                'per_page' => $perPage
            ]
        ]);
    }
}
