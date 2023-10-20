<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Enum\Access;
use TYPO3\CMS\Core\Resource\File as ResourceFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileService
{
    public function __construct(
        protected ResourceFactory $resourceFactory,
        protected FileRepository $fileRepository
    ) {
    }


    /**
     * load File
     *
     * @param int $identifier
     * @return ?File
     */
    public function load(int $identifier): ?File
    {
        return $this->fileRepository->findByUid($identifier) ?? null;
    }

    /**
     * return true if file is an image
     *
     * @param File $file
     * @return bool
     */
    public function isImage(File $file): bool
    {
        return $this->getOriginalFileResource($file)->getType() === ResourceFile::FILETYPE_IMAGE;
    }

    /**
     * return ResourceFile corresponding to File
     *
     * @param File $file
     * @return ResourceFile
     */
    private function getOriginalFileResource(File $file): ResourceFile
    {
        return $this->resourceFactory->getFileObject($file->getUid());
    }
}
