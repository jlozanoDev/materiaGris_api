<?php

namespace App\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Str;

class JwtService
{
    protected Configuration $config;

    public function __construct()
    {
        $secret = config('jwt.secret');
        $this->config = Configuration::forSymmetricSigner(new Sha256(), \Lcobucci\JWT\Signer\Key\InMemory::plainText($secret));
    }

    public function issue(int $userId, array $scopes = []): array
    {
        $now = new DateTimeImmutable();
        $accessTtl = config('jwt.access_ttl', 15);
        $refreshTtlDays = config('jwt.refresh_ttl', 14);

        $jti = (string) Str::uuid();

        $accessToken = $this->config->builder()
            ->issuedAt($now)
            ->expiresAt($now->add(new DateInterval('PT' . ($accessTtl * 60) . 'S')))
            ->relatedTo((string) $userId)
            ->withClaim('scopes', $scopes)
            ->identifiedBy($jti)
            ->getToken($this->config->signer(), $this->config->signingKey());

        $refreshToken = bin2hex(random_bytes(64));
        $refreshExpires = $now->add(new DateInterval('P' . $refreshTtlDays . 'D'));

        return [
            'access_token' => $accessToken->toString(),
            'access_expires_at' => $accessToken->claims()->get(RegisteredClaims::EXPIRATION_TIME)->format(DateTimeImmutable::ATOM),
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $refreshExpires->format(DateTimeImmutable::ATOM),
            'jti' => $jti,
        ];
    }

    public function parseAndValidate(string $jwt)
    {
        $token = $this->config->parser()->parse($jwt);

        $constraints = [
            new SignedWith($this->config->signer(), $this->config->signingKey()),
            new LooseValidAt(SystemClock::fromUTC()),
        ];

        if (! $this->config->validator()->validate($token, ...$constraints)) {
            return null;
        }

        return $token;
    }
}
