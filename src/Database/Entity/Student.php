<?php

declare(strict_types=1);

namespace App\Database\Entity;

use App\Database\Entity\Dictionary\Item;
use App\Database\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use function mb_strtoupper;
use function mb_substr;

/**
 * Class Student
 * @package App\Database\Entity
 */
#[Entity(repositoryClass: StudentRepository::class)]
#[Table(name: 'students')]
class Student extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var string|null
     */
    #[Column(type: 'string', length: 100, nullable: true)]
    public ?string $azureId = null {
        get {
            return $this->azureId;
        }
        set {
            $this->azureId = $value;
        }
    }
    /**
     * @var string
     */
    #[Column(type: 'string', length: 100, unique: true)]
    public string $email {
        get {
            return $this->email;
        }
        set {
            $this->email = $value;
        }
    }
    /**
     * @var string
     */
    #[Column(type: 'string', length: 100)]
    public string $firstName {
        get {
            return $this->firstName;
        }
    }
    /**
     * @var int|null
     */
    #[Assert\GreaterThanOrEqual(0)]
    #[Column(type: 'integer', length: 100, nullable: true)]
    public ?int $albumNumber {
        get {
            return $this->albumNumber;
        }
        set {
            $this->albumNumber = $value;
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
    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $specialNeeds = null {
        get {
            return $this->specialNeeds;
        }
        set {
            $this->specialNeeds = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'study_mode', referencedColumnName: 'id', nullable: true)]
    public ?Item $studyMode {
        get {
            return $this->studyMode;
        }
        set {
            $this->studyMode = $value;
        }
    }
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    public ?Item $studyYear {
        get {
            return $this->studyYear;
        }
        set {
            $this->studyYear = $value;
        }
    }
    #[ORM\ManyToMany(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'student_profile_adaptations')]
    public Collection $adaptationType {
        get {
            return $this->adaptationType;
        }
        set {
            $this->adaptationType = $value;
        }
    }
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    public ?Item $studySemester {
        get {
            return $this->studySemester;
        }
        set {
            $this->studySemester = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'department', referencedColumnName: 'id', nullable: true)]
    public ?Item $department {
        get {
            return $this->department;
        }
        set {
            $this->department = $value;
        }
    }
    /**
     * @var Item|null
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'faculty', referencedColumnName: 'id', nullable: true)]
    public ?Item $faculty {
        get {
            return $this->faculty;
        }
        set {
            $this->faculty = $value;
        }
    }
    #[Column(type: 'text', nullable: true)]
    public ?string $kierunekVerbis = null {
        get {
            return $this->kierunekVerbis;
        }
        set {
            $this->kierunekVerbis = $value;
        }
    }
    #[Column(type: 'text', nullable: true)]
    public ?string $wydzialVerbis = null {
        get {
            return $this->wydzialVerbis;
        }
        set {
            $this->wydzialVerbis = $value;
        }
    }
    #[Column(type: 'integer', nullable: true)]
    public ?int $rokStudiowVerbis = null {
        get {
            return $this->rokStudiowVerbis;
        }
        set {
            $this->rokStudiowVerbis = $value;
        }
    }
    #[Column(type: 'integer', nullable: true)]
    public ?int $semestrVerbis = null {
        get {
            return $this->semestrVerbis;
        }
        set {
            $this->semestrVerbis = $value;
        }
    }
    #[Column(type: 'text', nullable: true)]
    public ?string $trybZajecVerbis = null {
        get {
            return $this->trybZajecVerbis;
        }
        set {
            $this->trybZajecVerbis = $value;
        }
    }
    /**
     * @var bool
     */
    #[Column(type: 'boolean')]
    public bool $isActive {
        get {
            return $this->isActive;
        }
        set {
            $this->isActive = $value;
        }
    }
    /**
     * @var string
     */
    #[Column(type: 'string', length: 100)]
    public string $lastName {
        get {
            return $this->lastName;
        }
    }
    /**
     * @var string|null
     */
    #[Column(type: 'string', length: 64, nullable: true)]
    public ?string $password = null {
        get {
            return $this->password;
        }
        set {
            $this->password = $value;
        }
    }
    /**
     * @var array
     */
    #[Column(type: 'json')]
    public array $roles {
        get {
            return $this->roles;
        }
        set {
            $this->roles = $value;
        }
    }
    #[Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $recommendedWebsite = null {
        get {
            return $this->recommendedWebsite;
        }
        set {
            $this->recommendedWebsite = $value;
        }
    }
    #[Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $cookiesAccept = null {
        get {
            return $this->cookiesAccept;
        }
        set {
            $this->cookiesAccept = $value;
        }
    }
    #[Column(type: 'string', nullable: true)]
    public ?string $notificationLanguage = null {
        get {
            return $this->notificationLanguage;
        }
        set {
            $this->notificationLanguage = $value;
        }
    }
    /**
     * @var \DateTimeImmutable|null
     */
    #[Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $lastLogin = null {
        get {
            return $this->lastLogin;
        }
        set {
            $this->lastLogin = $value;
        }
    }
    /**
     * @var \DateTimeImmutable|null
     */
    #[Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $announcementSeen = null {
        get {
            return $this->announcementSeen;
        }
        set {
            $this->announcementSeen = $value;
        }
    }
    #[ORM\Column(type: 'text')]
    public ?string $disabilityCountry = '' {
        get {
            return $this->disabilityCountry;
        }
        set {
            $this->disabilityCountry = $value;
        }
    }
    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $disabilityType = null {
        get {
            return $this->disabilityType;
        }
        set {
            $this->disabilityType = $value;
        }
    }
    #[ORM\Column(type: 'text')]
    public ?string $disabilityDegree = '' {
        get {
            return $this->disabilityDegree;
        }
        set {
            $this->disabilityDegree = $value;
        }
    }
    #[Column(type: 'date_immutable', nullable: true)]
    public ?\DateTimeImmutable $disabilityExpiration = null {
        get {
            return $this->disabilityExpiration;
        }
        set {
            $this->disabilityExpiration = $value;
        }
    }
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    public ?Item $adaptationExpiration = null {
        get {
            return $this->adaptationExpiration;
        }
        set {
            $this->adaptationExpiration = $value;
        }
    }
    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: RegisteredStudent::class, mappedBy: 'student', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $registrations {
        get {
            return $this->registrations;
        }
        set {
            $this->registrations = $value;
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->registrations = new ArrayCollection();
        $this->adaptationType = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        $firstInitial = mb_substr($this->firstName, 0, 1, 'UTF-8');
        $lastInitial = mb_substr($this->lastName, 0, 1, 'UTF-8');

        return mb_strtoupper($firstInitial . $lastInitial, 'UTF-8');
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
