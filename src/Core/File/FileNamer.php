<?php

namespace App\Core\File;

use App\Database\Entity\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;
use function uniqid;

class FileNamer implements NamerInterface
{
    /**
     * @param $object
     * @param PropertyMapping $mapping
     * @return string
     */
    public function name($object, PropertyMapping $mapping): string
    {
        if (!$object instanceof File) {
            throw new \LogicException('Nie jest File');
        }

        $uploadedFile = $object->file;

        if (!$uploadedFile instanceof UploadedFile) {
            return $object->file ?? 'file.tmp';
        }

        $newFileName = $object->id ?? uniqid('file_', true);

        $file = $mapping->getFile($object);

        $extension = $file ? $file->guessExtension() : 'pdf';

        $object->fileSize = $uploadedFile->getSize();
        $object->extension = $uploadedFile->guessExtension();
        $object->originalExtension = $uploadedFile->getClientOriginalExtension();
        $object->originalName = $uploadedFile->getClientOriginalName();

        return $newFileName . '.' . $extension;
    }
}
