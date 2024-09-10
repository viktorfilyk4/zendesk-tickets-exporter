<?php declare(strict_types=1);
namespace App;

use Psr\Http\Message\ResponseInterface;

class Utils {
    static function decodeResponse(ResponseInterface $response)
    {
        $contents = $response->getBody()->getContents();
        return json_decode($contents, true);
    }
}