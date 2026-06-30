<?php

namespace App\DTOs;

readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $accessExpiresAt,
        public string $refreshToken,
        public string $refreshExpiresAt,
        public string $jti,
    ) {}

    /**
     * Create a new TokenPair from an array of data.
     *
     * @param array{
     *     access_token?: string,
     *     access_expires_at?: string,
     *     refresh_token?: string,
     *     refresh_expires_at?: string,
     *     jti?: string,
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['access_token'] ?? '',
            accessExpiresAt: $data['access_expires_at'] ?? '',
            refreshToken: $data['refresh_token'] ?? '',
            refreshExpiresAt: $data['refresh_expires_at'] ?? '',
            jti: $data['jti'] ?? '',
        );
    }
}
