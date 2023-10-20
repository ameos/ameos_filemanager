<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Domain\Model\Filedownload;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

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
}
