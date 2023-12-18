<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class UploadService
{
    /**
     * @param ResourceFactory $resourceFactory
     * @param UserService $userService
     * @param MetaDataRepository $metaDataRepository
     */
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly UserService $userService,
        private readonly MetaDataRepository $metaDataRepository
    ) {
    }

    /**
     * update a file in a folder
     *
     * @param Folder $folder
     * @param array<UploadedFile> $fileInfo
     * @param bool $isNew
     * @return array
     */
    public function upload(Folder $folder, array $uploadedFiles, bool $isNew = true): array
    {
        $storage = $this->resourceFactory->getStorageObject($folder->getStorage());
        $resourceFolder = $storage->getFolder($folder->getIdentifier());
        foreach ($uploadedFiles as $uploadedFile) {
            /** @var File */
            $file = $storage->addFile(
                $uploadedFile->getTemporaryFileName(),
                $resourceFolder,
                $uploadedFile->getClientFilename()
            );

            if ($isNew) {
                if ($this->userService->isUserLoggedIn()) {
                    $properties['fe_user_id'] = $this->userService->getUserId();
                }
                $properties['folder_uid'] = $folder->getUid();

                $this->metaDataRepository->update($file->getUid(), $properties);
            }
        }

        if (isset($file) && $file) {
            return [
                'success' => true,
                'file' => $file->getUid()
            ];
        } else {
            return [
                'success' => false,
            ];
        }
    }
}
