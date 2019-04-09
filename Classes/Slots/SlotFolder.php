<?php
namespace Ameos\AmeosFilemanager\Slots;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
 
class SlotFolder
{

    /**
     * Call after folder rename in filelist
     * Rename the correct folder in the database
     * @param Folder $folder 
     * @param string $newName
     * @return void
     */
    public function rename($folder, $newName)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        
        $oldIdentifier = $folder->getIdentifier();
        $newIdentifier = dirname($folder->getIdentifier()) == '/'
            ? '/' . $newName . '/'
            : dirname($folder->getIdentifier()) . '/' . $newName . '/';


        // renamed folders
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        $folderRepository->requestUpdate($folderRecord['uid'], [
            'title'      => $newName,
            'identifier' => $newIdentifier
        ]);

        // subfolders
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
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
            $folderRepository->requestUpdate($folderRecord['uid'], [
                'identifier' => $identifier
            ]);
        }
    }

    /**
     * Call after folder addition in filelist
     * Add the correct folder in the database
     * @param Folder $folder
     * @return void
     */
    public function add($folder) {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);

        if ($folder->getParentFolder() && $folder->getParentFolder()->getName() != '') {
            $inserted = false;

            $folderParentRecord = $folderRepository->findRawByStorageAndIdentifier(
                $folder->getParentFolder()->getStorage()->getUid(),
                $folder->getParentFolder()->getIdentifier()
            );
            if ($folderParentRecord) {
                $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
                    $folder->getStorage()->getUid(),
                    $folder->getIdentifier()
                );
                if (!$folderRecord) {
                    
                    $folderRepository->requestInsert([
                        'tstamp'     => time(),
                        'crdate'     => time(),
                        'cruser_id'  => 1,
                        'title'      => $folder->getName(),
                        'uid_parent' => $folderParentRecord['uid'],
                        'identifier' => $folder->getIdentifier(),
                        'storage'    => $folder->getStorage()->getUid(),
                    ]);
                }
                $inserted = true;
            }

            if (!$inserted) {            
                $this->postFolderAdd($folder->getParentFolder());
                $this->postFolderAdd($folder);
            }
        } else {
            $folderRepository->requestInsert([
                'tstamp'     => time(),
                'crdate'     => time(),
                'cruser_id'  => 1,
                'title'      => $folder->getName(),
                'uid_parent' => 0,
                'identifier' => $folder->getIdentifier(),
                'storage'    => $folder->getStorage()->getUid(),
            ]);
        }
    }

    /**
     * Call after folder move in filelist
     * Move the correct folder in the database
     * @param Folder $folder 
     * @param Folder $targetFolder 
     * @param string $newName
     * @return void
     */
    public function move($folder, $targetFolder, $newName)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderParentRecord = $folderRepository->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );

        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );

        $newIdentifier = $targetFolder->getIdentifier() . $newName . '/';
        $folderRepository->requestUpdate($row['uid'], [
            'uid_parent' => $folderParentRecord['uid'],
            'title'      => $newName,
            'identifier' => $newIdentifier,
        ]);
    }

    /**
     * Call after folder copy in filelist
     * Copy the correct folder in the database
     * @param Folder $folder 
     * @param Folder $targetFolder 
     * @param string $newName
     * @return void
     */
    public function copy($folder, $targetFolder, $newName)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderParentRecord = $folderRepository->findRawByStorageAndIdentifier(
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
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        
        if(!$folderRecord) {
            $afmFolder = GeneralUtility::makeInstance(Folder::class);
            $afmFolder->setTitle($folder->getName());
            $afmFolder->setPid(0);
            $afmFolder->setCruser($GLOBALS['BE_USER']->user['uid']);
            $afmFolder->setUidParent($uidParent);
            $afmFolder->setStorage($folder->getStorage()->getStorageRecord()['uid']);
            $afmFolder->setIdentifier($folder->getIdentifier());
            $folderRepository->add($afmFolder);
            GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
            $folderuid = $afmFolder->getUid();
        } else {
            $folderuid = $folderRecord['uid'];
        }

        foreach ($folder->getFiles() as $file) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_metadata');

            $meta = $queryBuilder
                ->select('*')
                ->from('sys_file_metadata')
                ->where(
                    $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($file->getUid()))
                )
                ->execute()
                ->fetch(); 

            if ($meta && isset($meta['uid'])) {
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('sys_file_metadata')
                    ->update(
                        'sys_file_metadata',
                        ['folder_uid' => $folderuid],
                        ['file' => $file->getUid()]
                    );
            } else {        
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('sys_file_metadata')
                    ->insert('sys_file_metadata', [
                        'file'       => $file->getUid(),
                        'folder_uid' => $folderuid,
                        'tstamp'     => time(),
                        'crdate'     => time()
                    ]);
            }
        }

        foreach ($folder->getSubfolders() as $subFolder) {
            $this->insertSubFolder($subFolder, $folderuid);
        }
    }

    /**
     * Call after folder delete in filelist
     * Delete the correct folder in the database
     * @param Folder $folder
     * @return void
     */
    public function delete($folder)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        $folderRepository->requestDelete($folderRecord['uid']);
    }
}
