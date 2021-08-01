<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyRequest
{
    private const SPOTIFY_API_URL = 'https://api.spotify.com/v1';

    protected $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function get(string $endPoint)
    {
        $client = new Client();

        try {
            $response = $client->get(
                self::SPOTIFY_API_URL.$endPoint,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->accessToken,
                        'Accepts' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
        } catch (RequestException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents());
            $status = $errorResponse->error->status;
            $message = $errorResponse->error->message;

            throw new SpotifyApiException($message, $status, $errorResponse);
        }

        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            $message = 'Error when GET request';
            throw new SpotifyApiException($message, $statusCode);
        }

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }
}
