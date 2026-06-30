<?php

declare(strict_types=1);

namespace App\Database\Entity\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Dictionary\Item;
use App\Database\Repository\Application\EducationalProcessRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EducationalProcessRepository::class)]
#[ORM\Table(name: 'application_educational_process')]
class EducationalProcess extends Application
{
    public function __construct()
    {
        parent::__construct();
        $this->adaptations = new ArrayCollection();
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $adaptation_another = null {
        get {
            return $this->adaptation_another;
        }
        set {
            $this->adaptation_another = $value;
        }
    }

    /**
     * @var Collection
     */
    #[ORM\ManyToMany(targetEntity: Item::class)]
    #[ORM\JoinTable(name: 'application_adaptations')]
    public Collection $adaptations {
        get {
            return $this->adaptations;
        }
        set {
            $this->adaptations = $value;
        }
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
}



