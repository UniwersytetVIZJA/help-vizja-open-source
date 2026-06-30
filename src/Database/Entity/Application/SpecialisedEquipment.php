<?php

namespace App\Database\Entity\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Inventory;
use App\Database\Repository\Application\SpecialisedEquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialisedEquipmentRepository::class)]
#[ORM\Table(name: 'application_specialised_equipment')]
class SpecialisedEquipment extends Application
{
    public function __construct()
    {
        parent::__construct();
        $this->equipment = new ArrayCollection();
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 1000, nullable: true)]
    public ?string $description = null {
        get {
            return $this->description;
        }
        set {
            $this->description = $value;
        }
    }

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Inventory::class)]
    #[ORM\JoinTable(name: 'application_specialised_equipment_rent')]
    public Collection $equipment {
        get {
            return $this->equipment;
        }
        set {
            $this->equipment = $value;
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
}
