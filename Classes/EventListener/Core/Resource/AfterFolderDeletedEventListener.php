<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Resource\Event\AfterFolderDeletedEvent;
use TYPO3\CMS\Core\Resource\Folder as ResourceFolder;

class AfterFolderDeletedEventListener
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
     * @param AfterFolderDeletedEvent $event
     * @return void
     */
    public function __invoke(AfterFolderDeletedEvent $event): void
    {
        $this->unindex($event->getFolder());
    }

    /**
     * unindex
     *
     * @param ResourceFolder $folder
     * @return void
     */
    protected function unindex(ResourceFolder $folder): void
    {
        $this->folderService->unindex($folder);
        foreach ($folder->getSubfolders() as $subFolder) {
            $this->unindex($subFolder);
        }
        
        // todo unindex files
    }
}
