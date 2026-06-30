<?php

namespace App\Database\Entity;

use App\Database\Repository\RegisteredStudentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RegisteredStudentRepository::class)]
#[ORM\Table(name: 'registered_student')]
#[ORM\UniqueConstraint(name: 'uniq_registration_student', columns: ['registration_id', 'student_id'])]
#[UniqueEntity(fields: ['registration', 'student'], message: 'Ten student jest już zapisany na te zajęcia.')]
class RegisteredStudent extends BaseEntity
{
    /**
     * @var Registration|null
     */
    #[ORM\ManyToOne(inversedBy: 'registeredStudents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Registration $registration = null {
        get {
            return $this->registration;
        }
        set {
            $this->registration = $value;
        }
    }

    /**
     * @var Student|null
     */
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

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $specialNeeds = null {
        get {
            return $this->specialNeeds;
        }
        set {
            $this->specialNeeds = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $meetingMode = null {
        get {
            return $this->meetingMode;
        }
        set {
            $this->meetingMode = $value;
        }
    }

    /**
     * @var \libphonenumber\PhoneNumber|null
     */
    #[ORM\Column(type: 'phone_number', nullable: true)]
    public ?\libphonenumber\PhoneNumber $phone = null {
        get {
            return $this->phone;
        }
        set {
            $this->phone = $value;
        }
    }

    /**
     * @var int|null
     */
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column(type: 'integer', length: 5, nullable: true)]
    public ?int $albumNumber = null {
        get {
            return $this->albumNumber;
        }
        set {
            $this->albumNumber = $value;
        }
    }

    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $language {
        get {
            return $this->language;
        }
        set {
            $this->language = $value;
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

}
