<?php

namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Domain\Model\Filedownload;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

class DownloadUtility
{
    /**
     * return files to add in zip
     * @param string $rootPath
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param int $rootFolder root folder uid
     * @param int $recursiveLimit recursive limit
     * @param int $recursiveOccurence recursive occurence
     */
    public static function getFilesToAdd(
        $rootPath,
        $folder,
        $rootFolderUid,
        $recursiveLimit = false,
        $recursiveOccurence = 1
    ) {
        $filesToAdd = [];
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $files = $fileRepository->findFilesForFolder($folder->getUid());
        foreach ($files as $file) {
            if (
                !$file->isRemote()
                && AccessUtility::userHasFileReadAccess($user, $file, ['folderRoot' => $rootFolderUid])
            ) {
                $localFilepath = Environment::getPublicPath()
                    . '/'
                    . trim($file->getOriginalResource()->getStorage()->getConfiguration()['basePath'], '/')
                    . '/'
                    . trim($file->getOriginalResource()->getIdentifier(), '/');
                $zipFilepath   = str_replace($rootPath, '', $localFilepath);
                $filesToAdd[$zipFilepath] =  trim($zipFilepath, '/');
            }
        }

        if ($recursiveOccurence < (int)$recursiveLimit || $recursiveLimit === false) {
            foreach ($folder->getFolders() as $subFolder) {
                if (AccessUtility::userHasFolderReadAccess($user, $subFolder, ['folderRoot' => $rootFolderUid])) {
                    $recursiveOccurence++;
                    $filesToAdd = array_merge(
                        $filesToAdd,
                        self::getFilesToAdd(
                            $rootPath,
                            $subFolder,
                            $zip,
                            $rootFolderUid,
                            $recursiveLimit,
                            $recursiveOccurence
                        )
                    );
                }
            }
        }
        return $filesToAdd;
    }

    /**
     * add folder to zip
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param \ZipArchive $zip zip archive
     * @param int $rootFolder root folder uid
     * @param int $recursiveLimit recursive limit
     * @param int $recursiveOccurence recursive occurence
     */
    public static function addFolderToZip(
        $rootPath,
        $folder,
        $zip,
        $rootFolderUid,
        $recursiveLimit = false,
        $recursiveOccurence = 1
    ) {
        $user = ($GLOBALS['TSFE']->fe_user->user);
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $files = $fileRepository->findFilesForFolder($folder->getUid());
        foreach ($files as $file) {
            if (
                !$file->isRemote()
                && AccessUtility::userHasFileReadAccess($user, $file, ['folderRoot' => $rootFolderUid])
            ) {
                $localFilepath =
                    Environment::getPublicPath() . '/' .
                    trim($file->getOriginalResource()->getStorage()->getConfiguration()['basePath'], '/') . '/' .
                    trim($file->getOriginalResource()->getIdentifier(), '/');
                $zipFilepath   = str_replace($rootPath, '', $localFilepath);
                $zip->addFile($localFilepath, $zipFilepath);
            }
        }

        if ($recursiveOccurence < (int)$recursiveLimit || $recursiveLimit === false) {
            foreach ($folder->getFolders() as $subFolder) {
                if (AccessUtility::userHasFolderReadAccess($user, $subFolder, ['folderRoot' => $rootFolderUid])) {
                    $recursiveOccurence++;
                    self::addFolderToZip(
                        $rootPath,
                        $subFolder,
                        $zip,
                        $rootFolderUid,
                        $recursiveLimit,
                        $recursiveOccurence
                    );
                }
            }
        }
    }

    /**
     * download the file and log the download in the DB
     * @param int $uidFile uid of the file
     */
    public static function downloadFile($uidFile, $folderRoot = null)
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $file = $fileRepository->findByUid($uidFile);
        $user = ($GLOBALS['TSFE']->fe_user->user);

        // We check if the user has access to the file.
        if (AccessUtility::userHasFileReadAccess($user, $file, ['folderRoot' => $folderRoot])) {
            if ($file) {
                // Remove '/' at the beginning of publicUrl added by TYPO3 11
                $filename = preg_replace('/^\//i', '', urldecode($file->getPublicUrl()));
            }

            if (
                ExtensionManagementUtility::isLoaded('fal_securedownload')
                && $file->getOriginalResource()->getStorage()->getStorageRecord()['is_public'] == 0
            ) {
                $filedownloadRepository = GeneralUtility::makeInstance(FiledownloadRepository::class);
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($user['uid']);
                $filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                header('Location: ' . $filename);
                exit;
            }
            if (file_exists($filename)) {
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
            $message = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_ameosfilemanager.']['settings.']['forbidden']
                ?: 'Access denied';
            exit($message);
        }
    }
}
