<?php

declare(strict_types=1);

namespace App\Database\Entity\Dictionary;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\BaseEntity;
use App\Database\Entity\Dictionary;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Class Item
 * @package App\Database\Entity\Dictionary
 */
#[Entity(repositoryClass: DictionaryItemRepository::class)]
#[Table(name: 'dictionary_item')]
class Item extends BaseEntity
{
    public function __construct()
    {
        parent::__construct();

        $this->isActive = 1;
    }

    /**
     * @var Dictionary|null
     */
    #[ManyToOne(targetEntity: Dictionary::class, inversedBy: 'items')]
    #[JoinColumn(name: 'dictionary_id', referencedColumnName: 'id', nullable: false)]
    public ?Dictionary $dictionary = null {
        get {
            return $this->dictionary;
        }
        set {
            $this->dictionary = $value;
        }
    }

    /**
     * @var string|null
     */
    #[Column(type: 'text', nullable: true)]
    public ?string $hiddenValue {
        get {
            return $this->hiddenValue;
        }
        set {
            $this->hiddenValue = $value;
        }
    }

    /**
     * @var string|null
     */
    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $secondaryValue {
        get {
            return $this->secondaryValue;
        }
        set {
            $this->secondaryValue = $value;
        }
    }

    /**
     * @var string|null
     */
    #[Column(type: 'string', length: 255)]
    public ?string $value {
        get {
            return $this->value;
        }
        set {
            $this->value = $value;
        }
    }

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $valueEnglish {
        get {
            return $this->valueEnglish;
        }
        set {
            $this->valueEnglish = $value;
        }
    }

    #[Column(type: 'integer', length: 255, nullable: true)]
    public ?int $displayOrder = 1 {
        get {
            return $this->displayOrder;
        }
        set {
            $this->displayOrder = $value;
        }
    }

    /**
     * @var string|null
     */
    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $valueKey = null {
        get {
            return $this->valueKey;
        }
        set {
            $this->valueKey = $value;
        }
    }

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $valueKeyEng = null {
        get {
            return $this->valueKeyEng;
        }
        set {
            $this->valueKeyEng = $value;
        }
    }

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $valueKey2 = null {
        get {
            return $this->valueKey2;
        }
        set {
            $this->valueKey2 = $value;
        }
    }

    #[Column(type: 'string', length: 255, nullable: true)]
    public ?string $valueKey2Eng = null {
        get {
            return $this->valueKey2Eng;
        }
        set {
            $this->valueKey2Eng = $value;
        }
    }

    /**
     * @var int
     */
    #[Column(type: 'integer', nullable: false)]
    public int $isActive {
        get {
            return $this->isActive;
        }
        set {
            $this->isActive = $value;
        }
    }
}
