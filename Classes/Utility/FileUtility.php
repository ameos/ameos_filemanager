<?php
namespace Ameos\AmeosFilemanager\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
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
 
class FileUtility
{
    /**
     * remove file
     * @param int $fid file id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function remove($fid, $sid, $folderRoot)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $storage = ResourceFactory::getInstance()->getStorageObject($sid);
        $file = $objectManager->get(FileRepository::class)->findByUid($fid);

        if ($file && AccessUtility::userHasFileWriteAccess($GLOBALS['TSFE']->fe_user->user, $file, ['folderRoot' => $folderRoot])) {
            $storage->deleteFile($file->getOriginalResource());
            return true;
        }
        return false;
    }

    /**
     * remove file
     * @param int $fid file id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function move($fid, $tfid, $sid, $folderRoot)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $storage = ResourceFactory::getInstance()->getStorageObject($sid);
        $file = $objectManager->get(FileRepository::class)->findByUid($fid);
        $folder = $objectManager->get(FolderRepository::class)->findByUid($tfid);

        if (
            $file && AccessUtility::userHasFileWriteAccess($GLOBALS['TSFE']->fe_user->user, $file, ['folderRoot' => $folderRoot])
            && $folder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $folder, ['folderRoot' => $folderRoot])
        ) {
            $storage->moveFile($file->getOriginalResource(), $storage->getFolder($folder->getGedPath()));

            $metaDataRepository = $objectManager->get(MetaDataRepository::class);
            $metaDataRepository->update($file->getUid(), ['folder_uid' => $tfid]);

            return true;
        }
        return false;
    }

    /**
     * remove file
     * @param int $fid file id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function copy($fid, $tfid, $sid, $folderRoot)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $storage = ResourceFactory::getInstance()->getStorageObject($sid);
        $file = $objectManager->get(FileRepository::class)->findByUid($fid);
        $folder = $objectManager->get(FolderRepository::class)->findByUid($tfid);

        if (
            $file && AccessUtility::userHasFileWriteAccess($GLOBALS['TSFE']->fe_user->user, $file, ['folderRoot' => $folderRoot])
            && $folder && AccessUtility::userHasFolderWriteAccess($GLOBALS['TSFE']->fe_user->user, $folder, ['folderRoot' => $folderRoot])
        ) {
            $newfile = $storage->copyFile($file->getOriginalResource(), $storage->getFolder($folder->getGedPath()));

            $meta = $file->getMeta();
            $meta['file'] = $newfile->getUid();
            $meta['folder_uid'] = $tfid;

            $metaDataRepository = $objectManager->get(MetaDataRepository::class);
            $metaDataRepository->update($newfile->getUid(), $meta);

            return true;
        }
        return false;
    }
}
