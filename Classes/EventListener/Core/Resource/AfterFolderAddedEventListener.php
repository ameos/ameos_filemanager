<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent;

class AfterFolderAddedEventListener
{
    /**
     * @param FolderService $folderService
     */
    public function __construct(private readonly FolderService $folderService)
    {
    }

    /**
     * invoke event
     *
     * @param AfterFolderAddedEvent $event
     * @return void
     */
    public function __invoke(AfterFolderAddedEvent $event): void
    {
        $this->folderService->index($event->getFolder());
    }
}
