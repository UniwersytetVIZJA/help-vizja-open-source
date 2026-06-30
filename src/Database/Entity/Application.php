<?php

declare(strict_types=1);

namespace App\Database\Entity;

use AllowDynamicProperties;
use App\Database\Entity\Application\EducationalProcess;
use App\Database\Entity\Application\LanguageInterpreter;
use App\Database\Entity\Application\SpecialisedEquipment;
use App\Database\Entity\Application\TeachingAssistant;
use App\Database\Entity\Dictionary\Item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Application
 * @package App\Database\Entity
 */
#[AllowDynamicProperties]
#[ORM\Entity(repositoryClass: \App\Core\Application\ApplicationRepository::class)]
#[ORM\Table(name: 'application')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'educational_process' => EducationalProcess::class,
    'language_interpreter' => LanguageInterpreter::class,
    'specialised_equipment' => SpecialisedEquipment::class,
    'teaching_assistant' => TeachingAssistant::class,
])]
abstract class Application extends BaseEntity
{
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
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    public ?string $applicationNumber = null {
        get {
            return $this->applicationNumber;
        }
        set {
            $this->applicationNumber = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\Column(name: 'department')]
    public string $department = '' {
        get {
            return $this->department;
        }
        set {
            $this->department = $value;
        }
    }
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    public ?Item $adaptationCard {
        get {
            return $this->adaptationCard;
        }
        set {
            $this->adaptationCard = $value;
        }
    }
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    public ?\DateTimeImmutable $adaptationCardIssueDate = null {
        get {
            return $this->adaptationCardIssueDate;
        }
        set {
            $this->adaptationCardIssueDate = $value;
        }
    }
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $employeeComment = null {
        get {
            return $this->employeeComment;
        }
        set {
            $this->employeeComment = $value;
        }
    }
    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', length: 255, nullable: true)]
    public ?\DateTimeImmutable $employeeCommentDate = null {
        get {
            return $this->employeeCommentDate;
        }
        set {
            $this->employeeCommentDate = $value;
        }
    }
    /**
     * @var \DateTimeImmutable|null
     */
    #[Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $applicationDetailsSeen = null {
        get {
            return $this->applicationDetailsSeen;
        }
        set {
            $this->applicationDetailsSeen = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\Column(name: 'faculty')]
    public string $faculty = '' {
        get {
            return $this->faculty;
        }
        set {
            $this->faculty = $value;
        }
    }
    #[ORM\Column(name: 'dean')]
    public string $dean = '' {
        get {
            return $this->dean;
        }
        set {
            $this->dean = $value;
        }
    }
    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'application', cascade: ['persist'], orphanRemoval: true)]
    public Collection $files {
        get {
            return $this->files;
        }
        set {
            $this->files = $value;
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
     * @var Item|null
     */
    #[ORM\Column(name: 'study_mode')]
    public string $studyMode = '' {
        get {
            return $this->studyMode;
        }
        set {
            $this->studyMode = $value;
        }
    }
    /**
     * @var Student
     */
    #[ORM\ManyToOne(targetEntity: Student::class)]
    public Student $student {
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
    #[ORM\Column(type: 'text', length: 36, nullable: true)]
    public ?string $status = null {
        get {
            return $this->status;
        }
        set {
            $this->status = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'type', referencedColumnName: 'id', nullable: false)]
    public ?Item $type {
        get {
            return $this->type;
        }
        set {
            $this->type = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\Column(name: 'year')]
    public int $year = 0 {
        get {
            return $this->year;
        }
        set {
            $this->year = $value;
        }
    }
    #[ORM\Column(name: 'semester')]
    public int $semester = 0 {
        get {
            return $this->semester;
        }
        set {
            $this->semester = $value;
        }
    }

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $studentSubmitEmailSentAt = null {
        get {
            return $this->studentSubmitEmailSentAt;
        }
        set {
            $this->studentSubmitEmailSentAt = $value;
        }
    }


    public function __construct()
    {
        parent::__construct();

        $this->adaptation_dictionary = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->status = 'Nowy';
    }

    public function addFile(File $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->application = $this;
        }

        return $this;
    }
}

