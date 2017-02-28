<?php
namespace Ameos\AmeosFilemanager\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 
class DownloadUtility
{
    /**
	 * add folder to zip
	 * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
	 * @param ZipArchive $zip zip archive
	 * @return void
	 */
	public static function addFolderToZip($rootPath, $folder, $zip)
    {
        $fileRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FileRepository');
        $files = $fileRepository->findFilesForFolder($folder->getUid());
        foreach ($files as $file) {
            $localFilepath = PATH_site . $file->getOriginalResource()->getPublicUrl();
            $zipFilepath   = str_replace($rootPath, '', $localFilepath);
            $zip->addFile($localFilepath, $zipFilepath);
        }

        foreach ($folder->getFolders() as $subFolder) {
            self::addFolderToZip($rootPath, $subFolder, $zip);
        }
    }
    
	/**
	 * download the file and log the download in the DB
	 * @param integer $uidFile uid of the file
	 * @return void
	 */
	public static function downloadFile($uidFile, $folderRoot = null)
    {
		$fileRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FileRepository');
		$file = $fileRepository->findByUid($uidFile);
		$user = ($GLOBALS['TSFE']->fe_user->user);

        // We check if the user has access to the file.
        if (AccessUtility::userHasFileReadAccess($user, $file, array("folderRoot" => $folderRoot))) {
			if ($file) {
				$filename = urldecode($file->getPublicUrl());
			}

			if (file_exists($filename)) {
				// We register who downloaded the file and when
				$filedownloadRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository');
				$filedownload = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Model\Filedownload');
				$filedownload->setFile($file);
				$filedownload->setUserDownload($user['uid']);
				$filedownloadRepository->add($filedownload);
				$persitenceManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
				$persitenceManager->persistAll();

			    // Download of the file
			    header('Content-Description: File Transfer');
			    header('Content-Type: ' . mime_content_type($filename));
			    header('Content-Disposition: attachment; filename='.basename($filename));
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    // This line apparently causes trouble on some systems.
			    // TODO : see why and patch it.
			    //header('Content-Length: ' . filesize($filename));
			    ob_clean();
			    flush();
			    readfile($filename);
			    exit;
			}
		} else {
			header('HTTP/1.1 403 Forbidden');
			$message = $GLOBALS["TSFE"]->tmpl->setup["plugin."]["tx_ameosfilemanager."]["settings."]["forbidden"] ?: "Access denied";
			exit($message);
		}
	}
}
