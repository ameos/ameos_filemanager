<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use TYPO3\CMS\Core\Resource\Event\BeforeFolderMovedEvent;

class BeforeFolderMovedEventListener extends AbstractFolderEventListener
{
    public function __invoke(BeforeFolderMovedEvent $event)
    {
        $folder = $event->getFolder();
        $targetParentFolder = $event->getTargetParentFolder();

        $folderParentRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $targetParentFolder->getStorage()->getUid(),
            $targetParentFolder->getIdentifier()
        );

        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );

        $newIdentifier = $targetParentFolder->getIdentifier() . $event->getTargetFolderName() . '/';
        $this->folderRepository->requestUpdate(
            $folderRecord['uid'],
            [
                'uid_parent' => $folderParentRecord['uid'],
                'title'      => $folder->getName(),
                'identifier' => $newIdentifier,
            ]
        );
    }
}
