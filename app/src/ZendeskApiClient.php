<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;

class ZendeskApiClient
{
    private Client $client;
    private array $auth;
    private string $baseUri;

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

    public function sendAsyncRequest(string $path): PromiseInterface
    {
        return $this->client->getAsync($path);
    }
}
