<?php
namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class FileManager
{
    private Filesystem $filesystem;

    public function __construct(
        private readonly string $projectDir
    )
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Deletes a file from the filesystem and resets the corresponding fields in the provided entity.
     *
     * @param object $entity The entity whose fields will be updated if the file is deleted.
     * @param string $fileField The name of the entity field representing the file.
     * @param string $filePath The file system path of the file to be deleted.
     *
     * @return bool Returns true if the file was successfully deleted and the fields were reset.
     *
     * @throws \RuntimeException If the file does not exist or cannot be deleted due to an IO error.
     */
    public function deleteFile(object $entity, string $fileField, string $filePath): bool
    {
        try {
            if ($this->filesystem->exists($filePath)) {
                $this->filesystem->remove($filePath);
                // Reset entity fields
                $setterFile = 'set' . ucfirst($fileField);
                $setterPath = 'set' . ucfirst($fileField) . 'Path';
                if (method_exists($entity, $setterFile)) $entity->$setterFile(null);
                if (method_exists($entity, $setterPath)) $entity->$setterPath(null);

                return true;
            }

            throw new \RuntimeException("File not found at path: {$filePath}");
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException("Unable to delete file: {$filePath}", 0, $e);
        }
    }
}
