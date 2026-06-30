<?php

namespace App\Database\Entity\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Dictionary\Item;
use App\Database\Repository\Application\TeachingAssistantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeachingAssistantRepository::class)]
#[ORM\Table(name: 'application_teaching_assistant')]
class TeachingAssistant extends Application
{
    public function __construct()
    {
        parent::__construct();
        $this->equipment = new ArrayCollection();
    }

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'text', length: 5, nullable: true)]
    public ?string $assistantHours = null {
        get {
            return $this->assistantHours;
        }
        set {
            $this->assistantHours = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $assistantTask = null {
        get {
            return $this->assistantTask;
        }
        set {
            $this->assistantTask = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $assistant = null {
        get {
            return $this->assistant;
        }
        set {
            $this->assistant = $value;
        }
    }

    #[ORM\ManyToMany(targetEntity: Item::class)]
    #[ORM\JoinTable(name: 'application_teaching_assistant_preferences')]
    public Collection $preferences {
        get {
            return $this->preferences;
        }
        set {
            $this->preferences = $value;
        }
    }
}
