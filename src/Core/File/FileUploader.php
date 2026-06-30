<?php

namespace App\Core\File;

use App\Database\Entity\File;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    /**
     * @param FormInterface $form
     * @return array
     */
    public function getFiles(FormInterface $form): array
    {
        $found = [];
        $stack = [$form];

        while ($stack) {
            $current = array_pop($stack);

            foreach ($current->all() as $child) {
                if (\count($child->all()) > 0) {
                    $stack[] = $child;
                }

                $data = $child->getData();
                if ($data instanceof File) {
                    $hasUpload = $child->has('file') && $child->get('file')->getData() instanceof UploadedFile;
                    if ($hasUpload) {
                        $found[] = $data;
                    }
                }
            }
        }

        return $found;
    }
}
