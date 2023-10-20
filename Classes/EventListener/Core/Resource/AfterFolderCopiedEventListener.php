<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3\CMS\Core\Resource\Event\AfterFolderCopiedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class AfterFolderCopiedEventListener extends AbstractFolderEventListener
{
    public function __invoke(AfterFolderCopiedEvent $event)
    {
        $targetFolder = $event->getTargetFolder();
        $folderParentRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );
        $this->insertSubFolder($targetFolder->getSubfolder($newName), $folderParentRecord['uid']);
    }

    /**
     * insert folder in database
     * @param Folder $folder
     * @param int $uidParent
     */
    protected function insertSubFolder($folder, $uidParent = 0)
    {
        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );

        if (!$folderRecord) {
            $afmFolder = GeneralUtility::makeInstance(Folder::class);
            $afmFolder->setTitle($folder->getName());
            $afmFolder->setPid(0);
            $afmFolder->setCruser($GLOBALS['BE_USER']->user['uid']);
            $afmFolder->setUidParent($uidParent);
            $afmFolder->setStorage($folder->getStorage()->getStorageRecord()['uid']);
            $afmFolder->setIdentifier($folder->getIdentifier());
            $this->folderRepository->add($afmFolder);
            GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
            $folderuid = $afmFolder->getUid();
        } else {
            $folderuid = $folderRecord['uid'];
        }

        $sysFileMetadataTablename = 'sys_file_metadata';
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($sysFileMetadataTablename)
            ->select('*')
            ->from($sysFileMetadataTablename);

        foreach ($folder->getFiles() as $file) {
            $meta = $queryBuilder
                ->where(
                    $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($file->getUid()))
                )
                ->execute()
                ->fetch();

            if ($meta && isset($meta['uid'])) {
                $queryBuilder->getConnection()
                    ->update(
                        $sysFileMetadataTablename,
                        ['folder_uid' => $folderuid],
                        ['file' => $file->getUid()]
                    );
            } else {
                $queryBuilder->getConnection()
                    ->insert($sysFileMetadataTablename, [
                        'file'       => $file->getUid(),
                        'folder_uid' => $folderuid,
                        'tstamp'     => time(),
                        'crdate'     => time(),
                    ]);
            }
        }

        foreach ($folder->getSubfolders() as $subFolder) {
            $this->insertSubFolder($subFolder, $folderuid);
        }
    }
}
