<?php

namespace App\Service\Spotify;

use Exception;

class SpotifyApiException extends Exception
{
    protected $apiResponse;

    public const TOKEN_EXPIRED = 'The access token expired';
    public const RATE_LIMIT_STATUS = 429;

    /* 
    * Check if the exception is about expired token.
    */
    public function hasExpiredToken(): bool
    {
        return $this->getMessage() === self::TOKEN_EXPIRED;
    }

    /* 
    * Check if the exception is about rate limite.
    */
    public function isRateLimited(): bool
    {
        return $this->getCode() === self::RATE_LIMIT_STATUS;
    }
}
