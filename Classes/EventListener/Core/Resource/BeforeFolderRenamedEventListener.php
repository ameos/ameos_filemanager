<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderRenamedEvent;

class BeforeFolderRenamedEventListener
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
     * @param BeforeFolderRenamedEvent $event
     * @return void
     */
    public function __invoke(BeforeFolderRenamedEvent $event): void
    {
        $this->folderService->rename($event->getFolder(), $event->getTargetName());
    }
}
