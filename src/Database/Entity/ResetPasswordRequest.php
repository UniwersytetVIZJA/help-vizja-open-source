<?php

namespace App\Database\Entity;

use App\Database\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
class ResetPasswordRequest extends BaseEntity implements ResetPasswordRequestInterface
{
    public function __construct(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        parent::__construct();

        $this->user = $user;
        $this->expiresAt = \DateTimeImmutable::createFromInterface($expiresAt);
        $this->requestedAt = new \DateTimeImmutable('now');
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
    }

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $expiresAt {
        get {
            return $this->expiresAt;
        }
        set {
            $this->expiresAt = $value;
        }
    }

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $requestedAt {
        get {
            return $this->requestedAt;
        }
        set {
            $this->requestedAt = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $selector {
        get {
            return $this->selector;
        }
        set {
            $this->selector = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100)]
    public string $hashedToken {
        get {
            return $this->hashedToken;
        }
        set {
            $this->hashedToken = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Student::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Student $user {
        get {
            return $this->user;
        }
        set {
            $this->user = $value;
        }
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function isExpired(): bool
    {
        return (new \DateTimeImmutable()) > $this->expiresAt;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getUser(): object
    {
        return $this->user;
    }
}
