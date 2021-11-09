<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SpotifyRequest
{
    private const SPOTIFY_API_URL = 'https://api.spotify.com/v1';

    protected $client;
    protected $session;

    public function __construct(SessionInterface $session)
    {
        $this->client = new Client();
        $this->session = $session;
    }

    /**
     * Perform GET request on spotify API.
     */
    public function get(string $endPoint)
    {
        /* TO DO : Check if token is expired. If yes, refresh access token. */

        try {
            $response = $this->client->get(
                self::SPOTIFY_API_URL.$endPoint,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->session->get('accessToken'),
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
