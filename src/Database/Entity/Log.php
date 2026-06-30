<?php

declare(strict_types=1);

namespace App\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * Class Log
 * @package App\Database\Entity
 */
#[Entity]
#[Table(name: 'log')]
class Log extends BaseEntity
{
    /**
     * @var DateTimeImmutable
     */
    #[Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $actionExecutedAt {
        get {
            return $this->actionExecutedAt;
        }
        set {
            $this->actionExecutedAt = $value;
        }
    }

    /**
     * @var string
     */
    #[Column(type: 'string', length: 20)]
    protected string $actionType {
        get {
            return $this->actionType;
        }
        set {
            $this->actionType = $value;
        }
    }

    /**
     * @var string
     */
    #[Column(type: 'string', length: 50)]
    protected string $entityClass {
        get {
            return $this->entityClass;
        }
        set {
            $this->entityClass = $value;
        }
    }

    /**
     * @var string
     */
    #[Column(type: 'string')]
    protected string $entityId {
        get {
            return $this->entityId;
        }
        set {
            $this->entityId = $value;
        }
    }

    /**
     * @var string
     */
    #[Column(type: 'string')]
    protected string $messageId {
        get {
            return $this->messageId;
        }
        set {
            $this->messageId = $value;
        }
    }

    /**
     * @var array|null
     */
    #[Column(type: 'json', nullable: true)]
    protected ?array $oldData = null {
        get {
            return $this->oldData;
        }
        set {
            $this->oldData = $value;
        }
    }

    /**
     * @var array|null
     */
    #[Column(type: 'json', nullable: true)]
    protected ?array $newData = null {
        get {
            return $this->newData;
        }
        set {
            $this->newData = $value;
        }
    }
}
