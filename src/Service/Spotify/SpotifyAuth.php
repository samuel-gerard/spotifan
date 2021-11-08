<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Service\Spotify\SpotifyApiException;
use Symfony\Component\HttpFoundation\Session\Session;

class SpotifyAuth
{
    protected $spotifyApiTokenUrl;
    protected $spotifyApiAuthUrl;
    protected $redirectUri;
    protected $clientId;
    protected $clientSecret;

    protected $accessToken;
    protected $refreshToken;
    protected $expiresAt;

    public function __construct($spotifyApiTokenUrl, $spotifyApiAuthUrl, $redirectUri, $clientId, $clientSecret)
    {
        $this->spotifyApiTokenUrl = $spotifyApiTokenUrl;
        $this->spotifyApiAuthUrl = $spotifyApiAuthUrl;
        $this->redirectUri = $redirectUri;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
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
     * @return ?string
     */
    public function generateAccessToken(string $state, string $code): ?string
    {
        $client = new Client();

        $session = new Session();
        $session->start();

        try {
            $response = $client->post(
                $this->spotifyApiTokenUrl,
                [
                    'headers' => [
                        'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                        'Accepts' => 'application/json',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Content-Length' => 0,
                    ],
                    'query' => [
                        'redirect_uri' => 'http://127.0.0.1:8000/login/oauth',
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
        
        $this->accessToken = $body->access_token;
        $this->refreshToken = $body->refresh_token;
        $this->expiresAt = $body->expires_in;

        $session->set('accessToken', $this->accessToken);
        $session->set('refreshToken', $this->refreshToken);
        $session->set('expiresAt', $this->expiresAt);

        return true;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function refreshAccessToken()
    {
        $client = new Client();

        $response = $client->post(
            $this->spotifyApiTokenUrl,
            [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                    'Accepts' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Content-Length' => 0,
                ],
                'query' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken
                ],
            ]
        );

        $body = json_decode($response->getBody()->getContents());


        $this->accessToken = $body->access_token;
        $this->refreshToken = $body->refresh_token;
        $this->expiresAt = $body->expires_in;

        return true;
    }
}
