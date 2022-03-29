<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;

class AfterFileCopiedEventListener extends AbstractFileEventListener
{
    public function __invoke(AfterFileCopiedEvent $event)
    {
        $file = $event->getNewFile();
        $targetFolder = $event->getFolder();

        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );

        $this->connectionPool
            ->getConnectionForTable('sys_file_metadata')
            ->update(
                'sys_file_metadata',
                ['folder_uid' => $folderRecord['uid']],
                ['file' => $file->getUid() ]
            );

        if ($this->isFileContentSearchEnabled()) {
            $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
            try {
                $textExtractor = $textExtractorRegistry->getTextExtractor($file);
                if (!is_null($textExtractor)) {
                    $this->connectionPool
                        ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                        ->insert('tx_ameosfilemanager_domain_model_filecontent', [
                            'file'    => $file->getUid(),
                            'content' => $textExtractor->extractText($file),
                        ]);
                }
            } catch (\Exception $e) {
                //
            }
        }
    }
}
