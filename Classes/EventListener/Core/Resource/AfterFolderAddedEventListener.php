<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Utility\FolderUtility;
use TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent;

class AfterFolderAddedEventListener extends AbstractFolderEventListener
{
    public function __invoke(AfterFolderAddedEvent $event)
    {
        //FolderUtility::add($event->getFolder());
    }
}
