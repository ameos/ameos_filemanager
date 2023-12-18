<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FileService;
use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;

class AfterFileCopiedEventListener
{
    /**
     * @param FileService $fileService
     * @param FolderService $folderService
     */
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderService $folderService
    ) {
    }

    /**
     * invoke event
     *
     * @param AfterFileCopiedEvent $event
     * @return void
     */
    public function __invoke(AfterFileCopiedEvent $event): void
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $folder = $this->folderService->loadByResourceFolder($event->getFolder());
            $file = $this->fileService->add($event->getNewFile(), $folder);
            $this->fileService->indexContent($file);
        }
    }
}
