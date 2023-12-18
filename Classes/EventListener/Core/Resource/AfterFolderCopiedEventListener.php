<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\AfterFolderCopiedEvent;
use TYPO3\CMS\Core\Resource\Folder as ResourceFolder;

class AfterFolderCopiedEventListener
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
     * @param AfterFolderCopiedEvent $event
     * @return void
     */
    public function __invoke(AfterFolderCopiedEvent $event): void
    {
        $this->index($event->getTargetFolder());
    }


    /**
     * index folder in database
     *
     * @param ResourceFolder $folder
     * @return void
     */
    protected function index(ResourceFolder $folder): void
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $this->folderService->index($folder);
            foreach ($folder->getSubfolders() as $subFolder) {
                $this->index($subFolder);
            }

            // todo index files
        }
    }
}
