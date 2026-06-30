<?php

namespace App\Database\Entity;

use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfficeRegistrationRegisteredStudentRepository::class)]
#[ORM\Table(name: 'office_registration_registered_student')]
class OfficeRegistrationRegisteredStudent extends BaseEntity
{
    #[ORM\ManyToOne(inversedBy: 'registeredStudents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?OfficeRegistration $registration = null {
        get {
            return $this->registration;
        }
        set {
            $this->registration = $value;
        }
    }
    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Student $student = null {
        get {
            return $this->student;
        }
        set {
            $this->student = $value;
        }
    }
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $meetingMode = null {
        get {
            return $this->meetingMode;
        }
        set {
            $this->meetingMode = $value;
        }
    }
    #[ORM\Column(type: 'boolean', nullable: true)]
    public ?bool $confirmed = null {
        get {
            return $this->confirmed;
        }
        set {
            $this->confirmed = $value;
        }
    }
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get {
            return $this->description;
        }
        set {
            $this->description = $value;
        }
    }
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $reminderSentAt = null {
        get {
            return $this->reminderSentAt;
        }
        set {
            $this->reminderSentAt = $value;
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->confirmed = null;
    }

}
