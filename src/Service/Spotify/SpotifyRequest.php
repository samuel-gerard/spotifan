<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Session\Session;

class SpotifyRequest
{
    private const SPOTIFY_API_URL = 'https://api.spotify.com/v1';

    protected $accessToken;
    protected $refreshToken;

    protected $spotifyAuthenticator;

    public function __construct(SpotifyAuth $spotifyAuthenticator)
    {
        $this->spotifyAuthenticator = $spotifyAuthenticator;
    }

    public function get(string $endPoint)
    {
        $client = new Client();
        $session = new Session();

        try {
            $response = $client->get(
                self::SPOTIFY_API_URL.$endPoint,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$session->get('accessToken'),
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
