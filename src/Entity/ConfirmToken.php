<?php

namespace App\Entity;

use App\Repository\ConfirmTokenRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfirmTokenRepository::class)
 */
class ConfirmToken
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $publicToken;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expiresAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getPublicToken(): ?string
    {
        return $this->publicToken;
    }

    public function setPublicToken(string $publicToken): self
    {
        $this->publicToken = $publicToken;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function encode(string $publicToken, string $identifier, DateTime $expireDate): string
    {
        return base64_encode(
            hash_hmac('sha256', json_encode([
                $publicToken,
                $identifier,
                $expireDate->getTimestamp(),
            ]),
                $_ENV['APP_SECRET'],
                true
            )
        );
    }

    public function setSecret(string $identifier): void
    {
        $secret = $this->encode(
            $this->publicToken,
            $identifier,
            $this->expiresAt
        );

        $this->token = $secret;
    }
}
