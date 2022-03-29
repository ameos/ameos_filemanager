<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use TYPO3\CMS\Core\Resource\Event\BeforeFolderRenamedEvent;

class BeforeFolderRenamedEventListener extends AbstractFolderEventListener
{
    public function __invoke(BeforeFolderRenamedEvent $event)
    {
        $folder = $event->getFolder();
        $newName = $event->getTargetName();

        $oldIdentifier = $folder->getIdentifier();
        $newIdentifier = dirname($folder->getIdentifier()) == '/'
            ? '/' . $newName . '/'
            : dirname($folder->getIdentifier()) . '/' . $newName . '/';

        // renamed folders
        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        $this->folderRepository->requestUpdate($folderRecord['uid'], [
            'title'      => $newName,
            'identifier' => $newIdentifier,
        ]);

        // subfolders
        $qb = $this->connectionPool->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
        $statement = $qb
            ->select('uid', 'identifier')
            ->from('tx_ameosfilemanager_domain_model_folder', 'folder')
            ->where(
                $qb->expr()->eq('storage', $qb->createNamedParameter($folder->getStorage()->getUid())),
                $qb->expr()->like('identifier', $qb->createNamedParameter($folder->getIdentifier() . '%'))
            )
            ->execute();
        while ($folderRecord = $statement->fetch()) {
            $identifier = $newIdentifier . substr($folderRecord['identifier'], strlen($oldIdentifier));
            $this->folderRepository->requestUpdate($folderRecord['uid'], [
                'identifier' => $identifier,
            ]);
        }
    }
}
