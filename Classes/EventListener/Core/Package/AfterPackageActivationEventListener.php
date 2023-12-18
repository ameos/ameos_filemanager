<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Package;

use Ameos\AmeosFilemanager\Service\IndexationService;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;

class AfterPackageActivationEventListener
{
    /**
     * @param IndexationService $indexationService
     */
    public function __construct(private readonly IndexationService $indexationService)
    {
    }

    /**
     * invoke event
     *
     * @param AfterPackageActivationEvent $event
     * @return void
     */
    public function __invoke(AfterPackageActivationEvent $event): void
    {
        if ($event->getPackageKey() === 'ameos_filemanager') {
            $this->indexationService->runForDefaultStorage();
        }
    }
}
