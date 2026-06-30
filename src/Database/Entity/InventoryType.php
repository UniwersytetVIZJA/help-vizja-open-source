<?php

namespace App\Database\Entity;

use App\Database\Entity\Dictionary\Item;
use App\Database\Repository\InventoryTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryTypeRepository::class)]
#[ORM\Table(name: 'inventory_type')]
class InventoryType extends BaseEntity
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @var Item
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'dictionary_id', referencedColumnName: 'id', nullable: false)]
    public Item $inv {
        get {
            return $this->inv;
        }
        set {
            $this->inv = $value;
        }
    }

    /**
     * @var string
     */
    #[ORM\Column(type: 'text', length: 255, nullable: false)]
    public string $type {
        get {
            return $this->type;
        }
        set {
            $this->type = $value;
        }
    }
}
