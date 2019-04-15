<?php
namespace Ameos\AmeosFilemanager\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Utility\AccessUtility;

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
 
class FolderUtility
{
    /**
     * remove folder
     * @param int $fid folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function remove($fid, $sid, $folderRoot)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $folderRepository = $objectManager->get(FolderRepository::class);
        
        $storage = ResourceFactory::getInstance()->getStorageObject($sid);
        $folder = $folderRepository->findByUid($fid);
        
        if ($folder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $folder, ['folderRoot' => $folderRoot])) {
            $storage->deleteFolder($storage->getFolder($folder->getGedPath()), true);
            $folderRepository->remove($folder);
            return true;
        }
        return false;
    }

    /**
     * remove file
     * @param int $fid folder id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function move($fid, $tfid, $sid, $folderRoot)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $folderRepository = $objectManager->get(FolderRepository::class);

        $storage = ResourceFactory::getInstance()->getStorageObject($sid);
        $folder = $folderRepository->findByUid($fid);
        $tfolder = $folderRepository->findByUid($tfid);

        if (
            $folder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $folder, ['folderRoot' => $folderRoot])
            && $tfolder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $tfolder, ['folderRoot' => $folderRoot])
        ) {
            $storage->moveFolder($storage->getFolder($folder->getGedPath()), $storage->getFolder($tfolder->getGedPath()));

            $folder->setUidParent($tfid);
            $folder->setIdentifier($tfolder->getIdentifier() . $folder->getTitle() . '/');
            $folderRepository->add($folder);

            return true;
        }
        return false;
    }

    /**
     * remove file
     * @param int $fid folder id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function copy($fid, $tfid, $sid, $folderRoot)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $folderRepository = $objectManager->get(FolderRepository::class);

        $storage = ResourceFactory::getInstance()->getStorageObject($sid);
        $folder = $folderRepository->findByUid($fid);
        $tfolder = $folderRepository->findByUid($tfid);

        if (
            $folder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $folder, ['folderRoot' => $folderRoot])
            && $tfolder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $tfolder, ['folderRoot' => $folderRoot])
        ) {
            $newfolder = $storage->copyFolder($storage->getFolder($folder->getGedPath()), $storage->getFolder($tfolder->getGedPath()));

            $gedNewFolder = $objectManager->get(Folder::class);

            $gedNewFolder->setTitle($folder->getTitle());
            $gedNewFolder->setDescription($folder->getDescription());
            $gedNewFolder->setKeywords($folder->getKeywords());
            $gedNewFolder->setNoReadAccess($folder->getNoReadAccess());
            $gedNewFolder->setNoWriteAccess($folder->getNoWriteAccess());
            $gedNewFolder->setArrayFeGroupRead($folder->getArrayFeGroupRead());
            $gedNewFolder->setArrayFeGroupWrite($folder->getArrayFeGroupWrite());
            $gedNewFolder->setArrayFeGroupAddfile($folder->getArrayFeGroupAddfile());
            $gedNewFolder->setArrayFeGroupAddfolder($folder->getArrayFeGroupAddfolder());
            $gedNewFolder->setOwnerHasReadAccess($folder->getOwnerHasReadAccess());
            $gedNewFolder->setOwnerHasWriteAccess($folder->getOwnerHasWriteAccess());
            
            $gedNewFolder->setUidParent($tfolder);
            $gedNewFolder->setIdentifier($tfolder->getGedPath() . '/' . $newfolder->getName() . '/');
            $gedNewFolder->setStorage($sid);
            $folderRepository->add($gedNewFolder);

            $objectManager->get(PersistenceManager::class)->persistAll();
            
            static::indexingAfterCopy($folder, $gedNewFolder, $sid);

            return true;
        }
        return false;
    }

    /**
     * indexing new folder after copy
     * @param Ameos\AmeosFilemanager\Domain\Model\Folder $sourceFolder source folder
     * @param Ameos\AmeosFilemanager\Domain\Model\Folder $targetFolder target folder
     * @param int $sid storage id
     */
    protected static function indexingAfterCopy($sourceFolder, $targetFolder, $sid)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $persistenceManager = $objectManager->get(PersistenceManager::class);
        $folderRepository = $objectManager->get(FolderRepository::class);
        $fileRepository = $objectManager->get(FileRepository::class);
        
        $storage = ResourceFactory::getInstance()->getStorageObject($sid);

        $files = $storage->getFolder($sourceFolder->getGedPath())->getFiles();
        if ($files) {
            foreach ($files as $file) {
                $fileIdentifier = $targetFolder->getGedPath() . '/' . $file->getName();
                $newfile = $storage->getFile($fileIdentifier);                

                $meta = $fileRepository->findByUid($file->getProperty('uid'))->getMeta();
                $meta['file'] = $newfile->getUid();
                $meta['folder_uid'] = $targetFolder->getUid();

                $metaDataRepository = $objectManager->get(MetaDataRepository::class);
                $metaDataRepository->update($newfile->getUid(), $meta);
            }
        }
        
        if ($sourceFolder->getFolders()) {
            foreach ($sourceFolder->getFolders() as $folder) {
                
                $gedNewFolder = $objectManager->get(Folder::class);

                $gedNewFolder->setTitle($folder->getTitle());
                $gedNewFolder->setDescription($folder->getDescription());
                $gedNewFolder->setKeywords($folder->getKeywords());
                $gedNewFolder->setNoReadAccess($folder->getNoReadAccess());
                $gedNewFolder->setNoWriteAccess($folder->getNoWriteAccess());
                $gedNewFolder->setArrayFeGroupRead($folder->getArrayFeGroupRead());
                $gedNewFolder->setArrayFeGroupWrite($folder->getArrayFeGroupWrite());
                $gedNewFolder->setArrayFeGroupAddfile($folder->getArrayFeGroupAddfile());
                $gedNewFolder->setArrayFeGroupAddfolder($folder->getArrayFeGroupAddfolder());
                $gedNewFolder->setOwnerHasReadAccess($folder->getOwnerHasReadAccess());
                $gedNewFolder->setOwnerHasWriteAccess($folder->getOwnerHasWriteAccess());            
                $gedNewFolder->setUidParent($targetFolder);
                $gedNewFolder->setIdentifier($targetFolder->getGedPath() . '/' . $folder->getTitle() . '/');
                $gedNewFolder->setStorage($folder->getStorage());

                $folderRepository->add($gedNewFolder);

                $persistenceManager->persistAll();

                static::indexingAfterCopy($folder, $gedNewFolder, $sid);
            }
        }
    }
}
