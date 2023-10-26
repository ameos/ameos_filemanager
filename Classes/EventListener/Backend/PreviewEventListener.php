<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Backend;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class PreviewEventListener
{
    /**
     * @param FlexFormService $flexFormService
     * @param StorageRepository $storageRepository
     * @param FolderRepository $folderRepository
     */
    public function __construct(
        private readonly FlexFormService $flexFormService,
        private readonly StorageRepository $storageRepository,
        private readonly FolderRepository $folderRepository
    ) {
    }

    /**
     * invoke listener
     *
     * @param PageContentPreviewRenderingEvent $event
     * @return void
     */
    public function __invoke(PageContentPreviewRenderingEvent $event): void
    {
        if ($event->getTable() !== 'tt_content') {
            return;
        }

        if ($event->getRecord()['CType'] === 'list'
            && $event->getRecord()['list_type'] === 'ameosfilemanager_fefilemanagerexplorer'
        ) {
            $flexform = $this->flexFormService->convertFlexFormContentToArray($event->getRecord()['pi_flexform']);

            try {
                $storage = $this->storageRepository
                    ->findByUid((int)$flexform['settings']['storage'])
                    ->getName();
            } catch (\Exception $e) {
                $storage = '';
            }

            try {
                $folder = $this->folderRepository
                    ->findByUid((int)$flexform['settings']['startFolder'])
                    ->getTitle();
            } catch (\Exception $e) {
                $folder = '';
            }
            

            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(
                'EXT:ameos_filemanager/Resources/Private/Templates/Backend/Preview.html'
            );
            $view->assign('folder', $folder);
            $view->assign('storage', $storage);

            $event->setPreviewContent($view->render());
        }
    }
}
