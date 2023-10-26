<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class UploadService
{
    /**
     * @param ResourceFactory $resourceFactory
     */
    public function __construct(private readonly ResourceFactory $resourceFactory)
    {
    }

    /**
     * update a file in a folder
     *
     * @param Folder $folder
     * @param array<UploadedFile> $fileInfo
     * @param bool $isNew
     * @return ResponseInterface
     */
    public function upload(Folder $folder, array $uploadedFiles, bool $isNew = true): ResponseInterface
    {
        $storage = $this->resourceFactory->getStorageObject($folder->getStorage());
        $resourceFolder = $storage->getFolder($folder->getGedPath());
        foreach ($uploadedFiles as $uploadedFile) {
            $file = $storage->addFile(
                $uploadedFile->getTemporaryFileName(),
                $resourceFolder,
                $uploadedFile->getClientFilename()
            );

            //if ($isNew) {
            //    if ($this->isUserLoggedIn()) {
            //        $properties['fe_user_id'] = (int)$GLOBALS['TSFE']->fe_user->user['uid'];
            //    }
            //    $properties['folder_uid'] = $folder->getUid();
            //}
        }

        if (isset($file) && $file) {
            return new JsonResponse([
                'success' => true,
                'file' => $file->getIdentifier()
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
            ]);
        }
    }
}
