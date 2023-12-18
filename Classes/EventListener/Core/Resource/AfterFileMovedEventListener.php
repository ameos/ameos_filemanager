<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FileService;
use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;

class AfterFileMovedEventListener
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
     * @param AfterFileMovedEvent $event
     * @return void
     */
    public function __invoke(AfterFileMovedEvent $event): void
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $folder = $this->folderService->loadByResourceFolder($event->getFolder());
            $this->fileService->add($event->getFile(), $folder);
        }
    }
}
