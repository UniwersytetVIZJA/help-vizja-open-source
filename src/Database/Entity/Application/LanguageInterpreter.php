<?php

declare(strict_types=1);

namespace App\Database\Entity\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Dictionary\Item;
use App\Database\Repository\Application\LanguageInterpreterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LanguageInterpreterRepository::class)]
#[ORM\Table(name: 'application_language_interpreter')]
class LanguageInterpreter extends Application
{
    public function __construct()
    {
        parent::__construct();
        $this->request = new ArrayCollection();
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 1000, nullable: true)]
    public ?string $justification = null {
        get {
            return $this->justification;
        }
        set {
            $this->justification = $value;
        }
    }

    /**
     * @var Collection
     */
    #[ORM\ManyToMany(targetEntity: Item::class)]
    #[ORM\JoinTable(name: 'application_language_interpreter_request')]
    public Collection $request {
        get {
            return $this->request;
        }
        set {
            $this->request = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    public ?Item $preferences {
        get {
            return $this->preferences;
        }
        set {
            $this->preferences = $value;
        }
    }

    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $anotherPreferences = null {
        get {
            return $this->anotherPreferences;
        }
        set {
            $this->anotherPreferences = $value;
        }
    }
}



