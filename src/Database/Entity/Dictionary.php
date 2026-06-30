<?php

declare(strict_types=1);

namespace App\Database\Entity;

use App\Core\Dictionary\DictionaryRepository;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * Class Dictionary
 * @package App\Database\Entity
 */
#[Entity(repositoryClass: DictionaryRepository::class)]
#[Table(name: 'dictionary')]
class Dictionary extends BaseEntity
{
    /**
     * @var DictionaryNameEnum
     */
    #[ORM\Column(type: 'string', length: 100, enumType: DictionaryNameEnum::class)]
    public DictionaryNameEnum $name {
        get {
            return $this->name;
        }
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    public ?string $nameEnglish = null {
        get {
            return $this->nameEnglish;
        }
        set {
            $this->nameEnglish = $value;
        }
    }

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'dictionary')]
    public Collection $items {
        get {
            return $this->items;
        }
        set {
            $this->items = $value;
        }
    }

    /**
     * Dictionary constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->items = new ArrayCollection();
    }
}
