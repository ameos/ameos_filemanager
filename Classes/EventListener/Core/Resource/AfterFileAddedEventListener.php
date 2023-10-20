<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Utility\FileUtility;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;

class AfterFileAddedEventListener extends AbstractFileEventListener
{
    public function __invoke(AfterFileAddedEvent $event)
    {
        FileUtility::add($event->getFile(), $event->getFolder());
    }
}
