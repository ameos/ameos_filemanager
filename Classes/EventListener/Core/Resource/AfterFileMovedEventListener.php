<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;

class AfterFileMovedEventListener extends AbstractFileEventListener
{
    public function __invoke(AfterFileMovedEvent $event)
    {
        $file = $event->getFile();
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
                ['file' => $file->getUid()]
            );
    }
}
