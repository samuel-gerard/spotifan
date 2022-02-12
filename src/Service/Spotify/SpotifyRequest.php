<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use App\Service\Spotify\SpotifyApiException;
use Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SpotifyRequest
{
    private const SPOTIFY_BASE_URL = 'https://api.spotify.com/v1';

    protected $client;
    protected $session;
    protected $spotifyAuthenticator;

    public function __construct(SessionInterface $session)
    {
        $this->client = new Client();
        $this->session = $session;
    }

    /**
     * Send a request to the Spotify API & refresh the access token if expired.
     *
     * @param string $method The HTTP method to use.
     * @param string $uri The URI of the request.
     * @param array $parameters Query string parameters of HTTP body, optional.
     *
     * @throws SpotifyApiException
     *
     * @return void The response of the body.
     */
    public function send(string $method, string $uri, array $parameters = []): array
    {
        $method = strtoupper($method);

        $url = self::SPOTIFY_BASE_URL . $uri;
        $headers = $this->buildHeaders();

        try {
            switch ($method) {
                case 'GET':
                    $requestResponse = $this->client->get($url, [
                            'headers' => $headers,
                            'query' => $parameters,
                        ]);
                    break;
            }
        } catch (SpotifyApiException $e) {
            if ($e->hasExpiredToken()) {
                /* @TODO refresh token + retry */
                $result = $this->spotifyAuthenticator->refreshAccessToken();

                if ($result) {
                    die;
                    $this->send($method, $uri, $parameters);
                }
            } elseif ($e->isRateLimited()) {
                /* @TODO sleep + retry */
            }

            throw $e;
        }

        $statusCode = $requestResponse->getStatusCode();

        $body = json_decode($requestResponse->getBody()->getContents());

        if ($statusCode >= 400) {
            $this->handleResponseError($body, $requestResponse);
        }

        $response = [
            'body' => $body,
            'status' => $statusCode,
            'url' => $url
        ];

        return $response;
    }

    /**
     * Return headers parameters.
     *
     * @return array
     */
    public function buildHeaders(): array
    {
        $accessToken = $this->session->get('accessToken');

        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accepts' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function handleResponseError($body, $response)
    {
        $message = 'Error when GET request';
        $statusCode = $response->getStatusCode();

        throw new SpotifyApiException($message, $statusCode);
    }

    public function setAuthenticator(SpotifyAuth $spotifyAuthenticator)
    {
        $this->spotifyAuthenticator = $spotifyAuthenticator;
    }

    /**
     * Get the current user top tracks or top artists.
     * https://developer.spotify.com/documentation/web-api/reference/#/operations/get-users-top-artists-and-tracks
     *
     * @param string $type The user top type. (artists|tracks)
     * @param array $options Query string parameters (limit, offset, time_range).
     * @return object List of requested top entities.
     */
    public function getUserTop(string $type, array $options = []): object
    {
        $uri = '/me/top/' . $type;

        $response = $this->send('GET', $uri, $options);
        
        return $response['body'];
    }

    /**
     * Get user profile informations.
     * https://developer.spotify.com/documentation/web-api/reference/#/operations/get-users-profile
     *
     * @param string $userId The user identifier.
     * @return object List of requested user informations.
     */
    public function getUserProfile(string $userId): object
    {
        $uri = '/users/' . $userId;

        $response = $this->send('GET', $uri);

        return $response['body'];
    }

    /**
     * Get current user profile informations.
     * https://developer.spotify.com/documentation/web-api/reference/#/operations/get-current-users-profile
     *
     * @return object List of current user informations.
     */
    public function getMeProfile(): object
    {
        $uri = '/me';

        $response = $this->send('GET', $uri);

        return $response['body'];
    }
}
