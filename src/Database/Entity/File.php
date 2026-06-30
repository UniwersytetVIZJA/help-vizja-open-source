<?php

namespace App\Database\Entity;

use AllowDynamicProperties;
use App\Database\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Class Application
 * @package App\Database\Entity
 */
#[AllowDynamicProperties]
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[Vich\Uploadable]
class File extends BaseEntity
{
    public function __construct() {
        parent::__construct();
    }

    /**
     * @var Application|null
     */
    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public ?Application $application = null {
        get {
            return $this->application;
        }
        set {
            $this->application = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50)]
    public string $category = '' {
        get {
            return $this->category;
        }
        set {
            $this->category = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $category2 = null {
        get {
            return $this->category2;
        }
        set {
            $this->category2 = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 50, nullable: true)]
    public ?string $extension = null {
        get {
            return $this->extension;
        }
        set {
            $this->extension = $value;
        }
    }

    /**
     * @var SymfonyFile|null
     */
    #[Vich\UploadableField(mapping: 'application_file', fileNameProperty: 'fileName')]
    public ?SymfonyFile $file = null {
        get {
            return $this->file;
        }
        set {
            $this->file = $value;

            if ($value instanceof UploadedFile) {
                $this->updatedAt = new \DateTimeImmutable();
            }
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 50, nullable: true)]
    public ?string $fileName = null {
        get {
            return $this->fileName;
        }
        set {
            $this->fileName = $value;
        }
    }

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'bigint', length: 50, nullable: true)]
    public ?int $fileSize = null {
        get {
            return $this->fileSize;
        }
        set {
            $this->fileSize = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 50, nullable: true)]
    public ?string $originalExtension = null {
        get {
            return $this->originalExtension;
        }
        set {
            $this->originalExtension = $value;
        }
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    public ?string $originalName = null {
        get {
            return $this->originalName;
        }
        set {
            $this->originalName = $value;
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fileName ?? 'new file';
    }
}
