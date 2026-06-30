<?php

namespace App\Database\Entity;

use App\Database\Repository\AnnouncementsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnouncementsRepository::class)]
#[ORM\Table(name: 'announcements')]
class Announcements extends BaseEntity
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    public ?\DateTimeImmutable $startsAt = null
        {
            get {
                return $this->startsAt;
            }
            set {
                $this->startsAt = $value;
            }
        }

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    public ?\DateTimeImmutable $expiresAt = null
        {
            get {
                return $this->expiresAt;
            }
            set {
                $this->expiresAt = $value;
            }
        }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get {
            return $this->description;
        }
        set {
            $this->description = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $title = null {
        get {
            return $this->title;
        }
        set {
            $this->title = $value;
        }
    }

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    public bool $published {
        get {
            return $this->published;
        }
        set {
            $this->published = $value;
        }
    }

    /**
     * @param \DateTimeInterface $now
     * @return bool
     */
    public function isExpired(\DateTimeInterface $now = new \DateTimeImmutable()): bool
    {
        return $this->published
            && ($this->startsAt === null || $this->startsAt <= $now)
            && ($this->expiresAt === null || $this->expiresAt > $now);
    }
}
