<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Package;

use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;

class AfterPackageActivationEventListener
{
    public function __invoke(AfterPackageActivationEvent $event): void
    {
        if ($event->getPackageKey() === 'ameos_filemanager') {
            \Ameos\AmeosFilemanager\Service\IndexationService::runForDefaultStorage();
        }
    }
}
