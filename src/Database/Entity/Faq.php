<?php

declare(strict_types=1);

namespace App\Database\Entity;

use App\Database\Repository\FaqRepository;
use App\Form\LanguageEnum;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: FaqRepository::class)]
#[Table(name: 'faq')]
class Faq extends BaseEntity
{
    #[ORM\Column(type: 'string', length: 255)]
    public string $question {
        get {
            return $this->question;
        }
        set {
            $this->question = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $answer {
        get {
            return $this->answer;
        }
        set {
            $this->answer = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100, enumType: LanguageEnum::class)]
    public LanguageEnum $language {
        get {
            return $this->language;
        }
        set {
            $this->language = $value;
        }
    }
}
