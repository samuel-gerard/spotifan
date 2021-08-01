<?php

namespace App\Service\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Service\Spotify\SpotifyApiException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class SpotifyAuth
{
    protected $spotifyApiTokenUrl;
    protected $spotifyApiAUthUrl;
    protected $redirectUri;
    protected $clientId;
    protected $clientSecret;

    public function __construct($spotifyApiTokenUrl, $spotifyApiAUthUrl, $redirectUri, $clientId, $clientSecret)
    {
        $this->spotifyApiTokenUrl = $spotifyApiTokenUrl;
        $this->spotifyApiAUthUrl = $spotifyApiAUthUrl;
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
        $uri = $this->spotifyApiAUthUrl.
            '?response_type=code'.
            '&client_id='.$this->clientId.
            '&redirect_uri='.$this->redirectUri.
            '&state=spotify_auth_state'.
            '&scope=user-read-private user-read-email user-top-read';

        return $uri;
    }

    /**
     * Get the acces token to make API requets.
     *
     * @return ?string
     */
    public function generateAccessToken(): ?string
    {
        $client = new Client();

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
                        'grant_type' => 'client_credentials',
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

        return $body->access_token;
    }
}
