<?php
namespace Ameos\AmeosFilemanager\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use Ameos\AmeosFilemanager\Domain\Model\Filedownload;

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
 
class DownloadUtility
{
    /**
     * return files to add in zip
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param ZipArchive $zip zip archive
     * @param int $rootFolder root folder uid
     * @param int $includeArchive include archive file
     * @param int $recursiveLimit recursive limit
     * @param int $recursiveOccurence recursive occurence
     * @return void
     */
    public static function getFilesToAdd($rootPath, $folder, $zip, $rootFolderUid, $includeArchive = true, $recursiveLimit = false, $recursiveOccurence = 1)
    {
        $filesToAdd = [];
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $files = $fileRepository->findFilesForFolder($folder->getUid());
        foreach ($files as $file) {
            if (AccessUtility::userHasFileReadAccess($user, $file, ['folderRoot' => $rootFolderUid])
                && ($includeArchive || $file->getRealstatus() != 2)) {
                    
                $localFilepath =
                    PATH_site .
                    trim($file->getOriginalResource()->getStorage()->getConfiguration()['basePath'], '/') . '/' .
                    trim($file->getOriginalResource()->getIdentifier(), '/');
                $zipFilepath   = str_replace($rootPath, '', $localFilepath);
                $filesToAdd[$zipFilepath] =  trim($zipFilepath, '/');
            }
        }

        if ($recursiveOccurence < (int)$recursiveLimit || $recursiveLimit === false) {
            foreach ($folder->getFolders() as $subFolder) {
                if (AccessUtility::userHasFolderReadAccess($user, $subFolder, ['folderRoot' => $rootFolderUid])
                    && ($includeArchive || $subFolder->getRealstatus() != 2)) {
                    $recursiveOccurence++;
                    $filesToAdd = array_merge(
                        $filesToAdd,
                        self::getFilesToAdd($rootPath, $subFolder, $zip, $rootFolderUid, $includeArchive, $recursiveLimit, $recursiveOccurence)
                    );
                }
            }
        }
        return $filesToAdd; 
    }
    
    /**
     * add folder to zip
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param ZipArchive $zip zip archive
     * @param int $rootFolder root folder uid
     * @param int $includeArchive include archive file
     * @param int $recursiveLimit recursive limit
     * @param int $recursiveOccurence recursive occurence
     * @return void
     */
    public static function addFolderToZip($rootPath, $folder, $zip, $rootFolderUid, $includeArchive = true, $recursiveLimit = false, $recursiveOccurence = 1)
    {
        $user = ($GLOBALS['TSFE']->fe_user->user);
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $files = $fileRepository->findFilesForFolder($folder->getUid());
        foreach ($files as $file) {
            if (AccessUtility::userHasFileReadAccess($user, $file, ['folderRoot' => $rootFolderUid])
                && ($includeArchive || $file->getRealstatus() != 2)) {
                    
                $localFilepath =
                    PATH_site .
                    trim($file->getOriginalResource()->getStorage()->getConfiguration()['basePath'], '/') . '/' .
                    trim($file->getOriginalResource()->getIdentifier(), '/');
                $zipFilepath   = str_replace($rootPath, '', $localFilepath);
                $zip->addFile($localFilepath, $zipFilepath);
            }
        }

        if ($recursiveOccurence < (int)$recursiveLimit || $recursiveLimit === false) {
            foreach ($folder->getFolders() as $subFolder) {
                if (AccessUtility::userHasFolderReadAccess($user, $subFolder, ['folderRoot' => $rootFolderUid])
                    && ($includeArchive || $subFolder->getRealstatus() != 2)) {
                    $recursiveOccurence++;
                    self::addFolderToZip($rootPath, $subFolder, $zip, $rootFolderUid, $includeArchive, $recursiveLimit, $recursiveOccurence);
                }
            }
        }
    }
    
    /**
     * download the file and log the download in the DB
     * @param integer $uidFile uid of the file
     * @return void
     */
    public static function downloadFile($uidFile, $folderRoot = null)
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $file = $fileRepository->findByUid($uidFile);
        $user = ($GLOBALS['TSFE']->fe_user->user);

        // We check if the user has access to the file.
        if (AccessUtility::userHasFileReadAccess($user, $file, array('folderRoot' => $folderRoot))) {
            if ($file) {
                $filename = urldecode($file->getPublicUrl());
            }

            if (ExtensionManagementUtility::isLoaded('fal_securedownload')
                && $file->getOriginalResource()->getStorage()->getStorageRecord()['is_public'] == 0) {

                $filedownloadRepository = GeneralUtility::makeInstance(FiledownloadRepository::class);
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($user['uid']);
                $filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                header('Location: ' . $filename);
                exit;
            
            } elseif (file_exists($filename)) {
                // We register who downloaded the file and when
                $filedownloadRepository = GeneralUtility::makeInstance(FiledownloadRepository::class);
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($user['uid']);
                $filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                // Download of the file
                header('Content-Description: File Transfer');
                header('Content-Type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                ob_clean();
                flush();
                readfile($filename);
                exit;
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            $message = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_ameosfilemanager.']['settings.']['forbidden'] ?: 'Access denied';
            exit($message);
        }
    }
}
