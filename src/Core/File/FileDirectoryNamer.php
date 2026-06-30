<?php

namespace App\Core\File;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class FileDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * @param object|array $object
     * @param PropertyMapping $mapping
     * @return string
     */
    public function directoryName(object|array $object, PropertyMapping $mapping): string
    {
        return date('Y-m');
    }
}
