<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use TYPO3\CMS\Core\Resource\Event\AfterFolderDeletedEvent;

class AfterFolderDeletedEventListener extends AbstractFolderEventListener
{
    public function __invoke(AfterFolderDeletedEvent $event)
    {
        $folder = $event->getFolder();
        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        $this->deleteRecursive($folderRecord['uid']);
    }

    /**
     * Delete subfolders recursively
     * @param int $folderUid
     */
    protected function deleteRecursive($folderUid)
    {
        $subFolders = $this->folderRepository->getSubFolderFromFolder($folderUid);
        if (!empty($subFolders)) {
            foreach ($subFolders as $subFolder) {
                $this->deleteRecursive($subFolder->getUid());
            }
        }
        $this->folderRepository->requestDelete($folderUid);
    }
}
