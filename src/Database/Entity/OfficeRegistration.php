<?php

namespace App\Database\Entity;

use App\Database\Repository\OfficeRegistrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfficeRegistrationRepository::class)]
#[ORM\Table(name: 'registration_office')]
class OfficeRegistration extends BaseEntity
{
    #[ORM\OneToMany(targetEntity: OfficeRegistrationRegisteredStudent::class, mappedBy: 'registration', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $registeredStudents {
        get {
            return $this->registeredStudents;
        }
        set {
            $this->registeredStudents = $value;
        }
    }
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $startAt = null {
        get {
            return $this->startAt;
        }
        set {
            $this->startAt = $value;
        }
    }
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $endAt = null {
        get {
            return $this->endAt;
        }
        set {
            $this->endAt = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $teamsMeetingUrl = null {
        get {
            return $this->teamsMeetingUrl;
        }
        set {
            $this->teamsMeetingUrl = $value;
        }
    }
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $eventId = null {
        get {
            return $this->eventId;
        }
        set {
            $this->eventId = $value;
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->registeredStudents = new ArrayCollection();
    }

}
