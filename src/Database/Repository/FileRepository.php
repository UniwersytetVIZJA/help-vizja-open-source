<?php

namespace App\Database\Repository;

use App\Database\Entity\File;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use function ceil;

class FileRepository extends ServiceEntityRepository
{

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, File::class);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function fileExtension(UploadedFile $file): string
    {
        return $file->guessExtension();
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function originalFileExtension(UploadedFile $file): string
    {
        return $file->getClientOriginalExtension();
    }

    /**
     * @param File $file
     * @return int
     */
    public function fileSize(File $file): int
    {
        return $file->fileSize;
    }

    /**
     * @param int $size
     * @return int
     */
    public function fileSizeInKB(int $size): int
    {
        return (int)ceil($size / 1024);
    }


}
