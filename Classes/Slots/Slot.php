<?php
namespace Ameos\AmeosFilemanager\Slots;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 
class Slot
{

    /**
     * Call after folder rename in filelist
     * Rename the correct folder in the database
     * @param Folder $folder 
     * @param string $newName
     * @return void
     */
    public function postFolderRename($folder, $newName)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        
        $oldIdentifier = $folder->getIdentifier();
        $newIdentifier = dirname($folder->getIdentifier()) == '/'
            ? '/' . $newName . '/'
            : dirname($folder->getIdentifier()) . '/' . $newName . '/';


        // renamed folders
        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier like \'' . $folder->getIdentifier() . '\''
        );
        $folderRepository->requestUpdate($folderRecord['uid'], [
            'title'      => $newName,
            'identifier' => $newIdentifier
        ]);

        // subfolders
        $folders = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'folder.uid, folder.identifier',
            'tx_ameosfilemanager_domain_model_folder folder',
            'folder.deleted = 0
                AND folder.storage = ' . $folder->getStorage()->getUid() . '
                AND folder.identifier like \'' . $folder->getIdentifier() . '%\''
        );
        while (($folderRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($folders)) !== false) {
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
    public function postFolderAdd($folder) {
        if ($folder->getParentFolder() && $folder->getParentFolder()->getName() != '') {
            $inserted = false;

            $folderParentRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'tx_ameosfilemanager_domain_model_folder.uid',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.deleted = 0
                    AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getParentFolder()->getStorage()->getUid() . '
                    AND tx_ameosfilemanager_domain_model_folder.identifier like \'' . $folder->getParentFolder()->getIdentifier() . '\''
            );
            if ($folderParentRecord) {
                $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
                $folderRepository->requestInsert([
                    'tstamp'     => time(),
                    'crdate'     => time(),
                    'cruser_id'  => 1,
                    'title'      => $folder->getName(),
                    'uid_parent' => $folderParentRecord['uid'],
                    'identifier' => $folder->getIdentifier(),
                    'storage'    => $folder->getStorage()->getUid(),
                ]);
                $inserted = true;
            }

            if (!$inserted) {            
                $this->postFolderAdd($folder->getParentFolder());
                $this->postFolderAdd($folder);
            }
        } else {
            $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
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
    public function postFolderMove($folder, $targetFolder, $newName)
    {   
        $folderParentRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $targetFolder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier like \'' . $targetFolder->getIdentifier() . '\''
        );

        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier like \'' . $folder->getIdentifier() . '\''
        );

        $newIdentifier = $targetFolder->getIdentifier() . $newName . '/';

        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
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
    public function postFolderCopy($folder, $targetFolder, $newName)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderParentRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $targetFolder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier like \'' . $targetFolder->getIdentifier() . '\''
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
        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier like \'' . $folder->getIdentifier() . '\''
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
            $meta = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*',  'sys_file_metadata', 'sys_file_metadata.file = ' . $file->getUid());
            if (isset($meta['uid'])) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                    'sys_file_metadata', 
                    'sys_file_metadata.file = ' . $file->getUid(), 
                    ['folder_uid' => $folderuid]
                );
            } else {                
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_metadata', [
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
    public function postFolderDelete($folder)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $folder->getIdentifier() . '\''
        );
        $folderRepository->requestDelete($folderRecord['uid']);
    }

    /**
     * Call after file addition in filelist
     * Add the file to the correct folder in the database
     * @param File $file 
     * @param Folder $targetFolder
     * @return void
     */
    public function postFileAdd($file, $targetFolder)
    {
        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $targetFolder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $targetFolder->getIdentifier() . '\''
        );

        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file = '. $file->getUid(), ['folder_uid' => $folderRecord['uid']]);

        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
            try {
                $textExtractor = $textExtractorRegistry->getTextExtractor($file);
                if (!is_null($textExtractor)) {
                    $fileContent = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                        'file',
                        'tx_ameosfilemanager_domain_model_filecontent',
                        'file = ' . $file->getUid()
                    );
                    if ($fileContent) {
                        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_ameosfilemanager_domain_model_filecontent', 'file = ' . $file->getUid(), [
                            'file'    => $file->getUid(),
                            'content' => $textExtractor->extractText($file)
                        ]); 
                    } else {
                        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_ameosfilemanager_domain_model_filecontent', [
                            'file'    => $file->getUid(),
                            'content' => $textExtractor->extractText($file)
                        ]);  
                    }
                }    
            } catch (\Exception $e) {
                
            }
        }
    }

    /**
     * Call after file copy in filelist
     * Copy the file to the correct folder in the database
     * @param File $file 
     * @param Folder $targetFolder
     * @return void
     */
    public function postFileCopy($file, $targetFolder)
    {
        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $targetFolder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $targetFolder->getIdentifier() . '\''
        );

        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file = '. $file->getUid(), ['folder_uid' => $folderRecord['uid']]);

        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
            try {
                $textExtractor = $textExtractorRegistry->getTextExtractor($file);
                if (!is_null($textExtractor)) {
                    $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_ameosfilemanager_domain_model_filecontent', [
                        'file'    => $file->getUid(),
                        'content' => $textExtractor->extractText($file)
                    ]);
                }    
            } catch (\Exception $e) {
                
            }
        }
    }

    /**
     * Call after file move in filelist
     * Move the file to the correct folder in the database
     * @param File $file 
     * @param Folder $targetFolder
     * @return void
     */
    public function postFileMove($file, $targetFolder)
    {
        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $targetFolder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $targetFolder->getIdentifier() . '\''
        );

        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file = '. $file->getUid(), ['folder_uid' => $folderRecord['uid']]);
    }
}
