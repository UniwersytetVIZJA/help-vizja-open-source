<?php

namespace App\Database\Entity;

use App\Database\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'error_log')]
class ErrorLog extends BaseEntity {

    #[ORM\Column(length: 50)]
    public string $level = 'error'{
        get{
            return $this->level;
        }set{
            $this->level = $value;
        }

    }

    #[ORM\Column(type: 'text')]
    public string $message = '' {
        get {
            return $this->message;
        }
        set {
            $this->message = $value;
        }
    }

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $channel = null{
        get {
            return $this->channel;
        }
        set {
            $this->channel = $value;
        }
    }

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $exceptionClass = null {
        get {
            return $this->exceptionClass;
        }
        set {
            $this->exceptionClass = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $trace = null {
        get {
            return $this->trace;
        }
        set {
            $this->trace = $value;
        }
    }

    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $context = null {
        get {
            return $this->context;
        }
        set {
            $this->context = $value;
        }
    }

    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $extra = null {
        get {
            return $this->extra;
        }
        set {
            $this->extra = $value;
        }
    }

}
