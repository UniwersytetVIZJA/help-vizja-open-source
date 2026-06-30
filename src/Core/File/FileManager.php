<?php

declare(strict_types=1);

namespace App\Core\File;

use App\Core\BaseManager;
use App\Database\Entity\File;
use App\Database\Repository\FileRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class FileManager extends BaseManager
{

    /**
     * ApplicationManager constructor
     */
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly Filesystem $filesystem,
        private readonly FileRepository $fileRepository,
        private readonly UploadHandler $uploadHandler,
        private readonly UploaderHelper $uploaderHelper,
    ) {}

    /**
     * @param UploadedFile $file
     * @return File
     * @throws \Exception
     */
    public function handleApplicationFile(UploadedFile $file): File
    {
        $extension = $this->fileRepository->fileExtension($file);
        $originalExtension = $this->fileRepository->originalFileExtension($file);
        $fileSize = $this->fileRepository->fileSize($file);

        $fileEntity = new File();

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileEntity->extension = $extension;
        $fileEntity->originalExtension = $originalExtension;
        $fileEntity->fileSize = $fileSize;

        return $fileEntity;
    }

    /**
     * @param File $file
     * @return void
     */
    public function create(File $file): void
    {
        $this->basePersister->create($file, true);

        $this->uploadHandler->upload($file, 'file');
    }

    public function update(File $file): void
    {
        $this->basePersister->update($file, true);

        $this->uploadHandler->upload($file, 'file');
    }

    public function getFilePath(File $file): string
    {
        $filePath = $this->uploaderHelper->asset($file, 'file');

        return $_SERVER['DOCUMENT_ROOT'] . $filePath;
    }

    public function delete(File $file): void
    {
        $this->basePersister->delete($file, true);
    }

}
