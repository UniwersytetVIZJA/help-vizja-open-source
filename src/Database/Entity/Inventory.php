<?php

namespace App\Database\Entity;

use App\Database\Repository\InventoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
#[ORM\Table(name: 'inventory')]
class Inventory extends BaseEntity
{
    public function __construct()
    {
        parent::__construct();
        $this->status = 'Dostępny';
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
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 255, nullable: true)]
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
    public ?string $serialNumber = null {
        get {
            return $this->serialNumber;
        }
        set {
            $this->serialNumber = $value;
        }
    }

    /**
     * @var InventoryType
     */
    #[ORM\ManyToOne(targetEntity: InventoryType::class)]
    #[ORM\JoinColumn(name: 'inventory_id', referencedColumnName: 'id', nullable: false)]
    public InventoryType $equipment {
        get {
            return $this->equipment;
        }
        set {
            $this->equipment = $value;
        }
    }

    /**
     * @var Student|null
     */
    #[ORM\ManyToOne(targetEntity: Student::class)]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', nullable: true)]
    public ?Student $student = null {
        get {
            return $this->student;
        }
        set {
            $this->student = $value;
        }
    }

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $rentStart = null {
        get {
            return $this->rentStart;
        }
        set {
            $this->rentStart = $value;
        }
    }

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $rentEnd = null {
        get {
            return $this->rentEnd;
        }
        set {
            $this->rentEnd = $value;
        }
    }
}
