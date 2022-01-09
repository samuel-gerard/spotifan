<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Service\Spotify\SpotifyApiException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SpotifyAuth
{
    protected $spotifyApiTokenUrl;
    protected $spotifyApiAuthUrl;
    protected $redirectUri;
    protected $clientId;
    protected $clientSecret;
    protected $client;
    protected $session;

    public function __construct(
        $spotifyApiTokenUrl,
        $spotifyApiAuthUrl,
        $redirectUri,
        $clientId,
        $clientSecret,
        SessionInterface $session
    ) {
        $this->spotifyApiTokenUrl = $spotifyApiTokenUrl;
        $this->spotifyApiAuthUrl = $spotifyApiAuthUrl;
        $this->redirectUri = $redirectUri;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->session = $session;
        $this->client = new Client();
        ;
    }

    /**
     * Request authorization for application.
     *
     * @return string
     */
    public function buildAuthorizationUri():string
    {
        $uri = $this->spotifyApiAuthUrl.
            '?response_type=code'.
            '&client_id='.$this->clientId.
            '&redirect_uri='.$this->redirectUri.
            '&state=spotify_auth_state'.
            '&scope=user-top-read user-read-private user-read-email';

        return $uri;
    }

    /**
     * Get the acces token to make API requets.
     *
     * @return void
     */
    public function generateAccessToken(string $code): void
    {
        $this->session->start();

        try {
            $response = $this->client->post(
                $this->spotifyApiTokenUrl,
                [
                    'headers' => $this->buildHeaders(),
                    'query' => [
                        'redirect_uri' => $this->redirectUri,
                        'grant_type' => 'authorization_code',
                        'code' => $code
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
            $message = 'Error when generating access token';
            throw new SpotifyApiException($message, $statusCode);
        }

        $body = json_decode($response->getBody()->getContents());

        $this->session->set('accessToken', $body->access_token);
        $this->session->set('refreshToken', $body->refresh_token);
    }

    /**
     * Refreshing the access token.
     *
     * @return void
     */
    public function refreshAccessToken(): void
    {
        $response = $this->client->post(
            $this->spotifyApiTokenUrl,
            [
                'headers' => $this->buildHeaders(),
                'query' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken
                ],
            ]
        );

        $body = json_decode($response->getBody()->getContents());

        $this->session->set('accessToken', $body->access_token);
        $this->session->set('refreshToken', $body->refresh_token);
    }

    /**
     * Build headers parameters for access or refresh token query.
     *
     * @return array
     */
    public function buildHeaders(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->clientId . ':' . $this->clientSecret),
            'Accepts' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Content-Length' => 0,
        ];
    }
}
