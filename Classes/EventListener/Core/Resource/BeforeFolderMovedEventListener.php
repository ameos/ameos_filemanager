<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderMovedEvent;

class BeforeFolderMovedEventListener
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
     * @param BeforeFolderMovedEvent $event
     * @return void
     */
    public function __invoke(BeforeFolderMovedEvent $event): void
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $this->folderService->move($event->getFolder(), $event->getTargetParentFolder()->getIdentifier());
        }
    }
}
