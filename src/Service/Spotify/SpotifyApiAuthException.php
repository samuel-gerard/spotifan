<?php

namespace App\Service\Spotify;

use Exception;

class SpotifyApiAuthException extends Exception
{
    protected $apiResponse;

    public const INVALID_CLIENT = 'Invalid client';
    public const INVALID_CLIENT_SECRET = 'Invalid client secret';
    public const INVALID_REFRESH_TOKEN = 'Invalid refresh token';

    /*
    * Check if the exception is about bad client id or secret.
    */
    public function hasInvalidCredentials(): bool
    {
        return in_array($this->getMessage(), [
            self::INVALID_CLIENT,
            self::INVALID_CLIENT_SECRET,
        ]);
    }

    /*
    * Check if the exception is about bad refresh token.
    */
    public function hasInvalidRefreshToken(): bool
    {
        return $this->getCode() === self::INVALID_REFRESH_TOKEN;
    }
}
