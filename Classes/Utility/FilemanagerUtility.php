<?php
namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Slots\SlotFolder;
use Ameos\AmeosFilemanager\Slots\SlotFile;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;

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
 
class FilemanagerUtility
{
    /**
     * check recursion
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $rootFolder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $childFolder
     * @param int $recursion
     */
    public static function hasTooMuchRecursion($rootFolder, $childFolder, $recursion)
    {
        if (!$recursion) {
            return false;
        }        
        return self::calculRecursion($rootFolder, $childFolder) > $recursion;
    }

    /**
     * check is is the last recursion
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $rootFolder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $childFolder
     * @param int $recursion
     */
    public static function isTheLastRecursion($rootFolder, $childFolder, $recursion)
    {
        if (!$recursion) {
            return false;
        }        
        return self::calculRecursion($rootFolder, $childFolder) >= $recursion;
    }
    
    /**
     * calcul recursion
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $rootFolder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $childFolder
     * @return int $recursion
     */
    public static function calculRecursion($rootFolder, $childFolder)
    {
        if ($rootFolder->getGedPath() == $childFolder->getGedPath()) {
            return 0;
        }
        $deltaPath = trim(str_replace($rootFolder->getGedPath(), '', $childFolder->getGedPath()), '/');
        $deltaPart = GeneralUtility::trimExplode('/', $deltaPath);
        return count($deltaPart);
    }

    /**
     * return the image corresponding to the given extension
     * @param string $type extension of the file
     * @return string
     */
    public static function getImageIconeTagForType($type)
    {
        switch (strtolower($type)) {
            case 'folder':
                return '<i class="fa fa-2x fa-folder" aria-hidden="true"></i>';
                break;
            case 'previous_folder':
                return '<i class="fa fa-2x fa-folder" aria-hidden="true"></i>';
                break;
            case 'pdf':
                return '<i class="fa fa-2x fa-file-pdf-o" aria-hidden="true"></i>';
                break;
            case 'xls':
            case 'xlsx':
            case 'ods':
                return '<i class="fa fa-2x fa-file-excel-o" aria-hidden="true"></i>';
                break;
            case 'doc':
            case 'docx':
            case 'odt':
                return '<i class="fa fa-2x fa-file-word-o" aria-hidden="true"></i>';
                break;
            case 'ppt':
            case 'pptx':
            case 'odp':
                return '<i class="fa fa-2x fa-file-powerpoint-o" aria-hidden="true"></i>';
                break;
            case 'avi':
            case 'mpeg':
            case 'mp4':
            case 'mov':
            case 'flv':
            case 'youtube':
            case 'vimeo':
            case 'dailymotion':
                return '<i class="fa fa-2x fa-file-video-o" aria-hidden="true"></i>';
                break;
            case 'jpg':
            case 'jpeg':
            case 'svg':
            case 'png':
            case 'bmp':
            case 'gif':
            case 'eps':
            case 'tiff':
                return '<i class="fa fa-2x fa-file-image-o" aria-hidden="true"></i>';
                break;
            case 'mp3':
            case 'oga':
            case 'ogg':
            case 'midi':
                return '<i class="fa fa-2x fa-file-audio-o" aria-hidden="true"></i>';
                break;
            default:
                return '<i class="fa fa-2x fa-file-text-o" aria-hidden="true"></i>';
                break;
        }
    }

    
    /**
     * return objects of $repo where uid in $uids
     * @param Repository $repo
     * @param array $uids
     * @return object
     */
    public static function getByUids($repo, $uids)
    {
        if (!is_array($uids)) {
            $uids = explode(',', $uids);
        }
        $query = $repo->createQuery();
        $query->matching($query->in('uid', $uids));
        return $query->execute();
    }

    /**
     * return folder parent
     * @param integer $uid uid of the child folder
     * @return string
     */
    public static function getFolderPathFromUid($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
        $folder = $queryBuilder
            ->select('uid_parent', 'title')
            ->from('tx_ameosfilemanager_domain_model_folder', 'folder')
            ->where($queryBuilder->expr()->eq('uid', (int)$uid))
            ->execute()
            ->fetch();
        if ($folder && $folder['uid_parent'] > 0) {
            return self::getFolderPathFromUid($folder['uid_parent']) . '/' . $folder['title'];
        }
        return '/' . $folder['title'];
    }

    /**
     * parse folder for indexing new content
     */ 
    public static function parseFolderForNewElements($storage, $folderIdentifier, $folderName)
    {
        if (is_numeric($storage)) {
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($storage);
        }
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $slotFolder = GeneralUtility::makeInstance(SlotFolder::class);
        $slotFile = GeneralUtility::makeInstance(SlotFile::class);
        $falFolder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Folder::class, $storage, $folderIdentifier, $folderName);
        $subfolders = $falFolder->getSubfolders();
        foreach ($subfolders as $folder) {
            $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
                $folder->getStorage()->getUid(),
                $folder->getIdentifier()
            );
            if (!$folderRecord) {
                $slotFolder->add($folder);
            }
        }
        
        $files = $falFolder->getFiles();
        foreach ($files as $file) {
            $slotFile->add($file, $falFolder);
        }
    }

    /**
     * return true if file content search is enable and tika installed
     */
    public static function fileContentSearchEnabled()
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ameos_filemanager');
        return $configuration['enable_filecontent_search'] == 1;
    }
}
