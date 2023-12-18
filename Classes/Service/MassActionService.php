<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

class MassActionService
{
    /**
     * construct
     * @param FileService $fileService
     * @param FolderService $folderService
     */
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderService $folderService
    ) {
        
    }

    /**
     * move action
     *
     * @param array $folders
     * @param array $files
     * @param ?int $targetFolderId
     * @return void
     */
    public function move(array $folders, array $files, ?int $targetFolderId): void
    {
        $targetFolder = $this->folderService->load($targetFolderId);
        foreach ($folders as $folderId) {
            $folder = $this->folderService->load((int)$folderId);
            $this->folderService->move($folder, $targetFolder);
        }
        foreach ($files as $fileId) {
            $file = $this->fileService->load((int)$fileId);
            $this->fileService->move($file, $targetFolder);
        }
    }

    /**
     * copy action
     *
     * @param array $folders
     * @param array $files
     * @param ?int $targetFolderId
     * @return void
     */
    public function copy(array $folders, array $files, ?int $targetFolderId): void
    {
        $targetFolder = $this->folderService->load($targetFolderId);

        foreach ($folders as $folderId) {
            $folder = $this->folderService->load((int)$folderId);
            $this->folderService->copy($folder, $targetFolder);
        }

        foreach ($files as $fileId) {
            $file = $this->fileService->load((int)$fileId);
            $this->fileService->copy($file, $targetFolder);
        }
    }

    /**
     * remove action
     *
     * @param array $folders
     * @param array $files
     * @return void
     */
    public function remove(array $folders, array $files): void
    {
        foreach ($folders as $folderId) {
            $folder = $this->folderService->load((int)$folderId);
            $this->folderService->remove($folder);
        }

        foreach ($files as $fileId) {
            $file = $this->fileService->load((int)$fileId);
            $this->fileService->remove($file);
        }
    }
}