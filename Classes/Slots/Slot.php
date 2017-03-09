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
	public function postFolderRename($folder,$newName)
    {
		$folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $folder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $folder->getIdentifier()) {
				$folderRepository->requestUpdate($row['uid'], ['title' => $newName]);
				break;	
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
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
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $folder->getParentFolder()->getName() . '\'');
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
				if (FilemanagerUtility::getFolderPathFromUid($row['uid']).'/' == $folder->getParentFolder()->getIdentifier()) {
					$folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
					$folderRepository->requestInsert([
					    'tstamp'     => time(),
					    'crdate'     => time(),
					    'cruser_id'  => 1,
					    'title'      => $folder->getName(),
					    'uid_parent' => $row['uid'],
					    'identifier' => $folder->getIdentifier()
					]);
					$inserted = true;
					break;
				}
			}
			if (!$inserted) {			
				$this->postFolderAdd($folder->getParentFolder());
				$this->postFolderAdd($folder);
			}
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $folder->getName() . '\'');
			if (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) === false) {
                $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
                $folderRepository->requestInsert([
                    'tstamp'     => time(),
                    'crdate'     => time(),
                    'cruser_id'  => 1,
                    'title'      => $folder->getName(),
                    'uid_parent' => 0,
                    'identifier' => $folder->getIdentifier()
                ]);
			}
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
		$folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $targetFolder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $targetFolder->getIdentifier()) {
				$uid_parent = $row['uid'];
				break;
			}
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $folder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $folder->getIdentifier()) {
				$folderRepository->requestUpdate($row['uid'], ['uid_parent' => $uid_parent, 'title' => $newName]);
				break;	
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $targetFolder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $targetFolder->getIdentifier()) {
				$uid_parent = $row['uid'];
				break;
			}
		}
		self::setDatabaseForFolder($targetFolder->getSubfolder($newName), $uid_parent);
	}

    /**
     * insert folder in database
     * @param Folder $folder
     * @param int $uidParent
     */ 
	public function setDatabaseForFolder($folder, $uidParent = 0)
    {
		$folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $folder->getName() . '\'');
		$exist = false;
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			// Si il n'existe on ne fait rien
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $folder->getIdentifier()) {
				$exist = true;
				break;
			}
		}
		if(!$exist) {
			$afmFolder = GeneralUtility::makeInstance(Folder::class);
			$afmFolder->setTitle($folder->getName());
			$afmFolder->setPid(0);
			$afmFolder->setCruser($GLOBALS['BE_USER']->user['uid']);
			$afmFolder->setUidParent($uidParent);
			$folderRepository->add($afmFolder);
			GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
			$uid = $afmFolder->getUid();
		}

		foreach ($folder->getFiles() as $file) {
			$meta = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*',  'sys_file_metadata', 'sys_file_metadata.file = ' . $file->getUid());
			if (isset($meta['uid'])) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'sys_file_metadata', 
					'sys_file_metadata.file = ' . $file->getUid(), 
					['folder_uid' => $uid]
				);
			} else {				
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_metadata', [
                    'file'       => $file->getUid(),
                    'folder_uid' => $uid,
                    'tstamp'     => time(),
                    'crdate'     =>time()
                ]);
			}
		}

		foreach ($folder->getSubfolders() as $subFolder) {
			self::setDatabaseForFolder($subFolder, $uid);
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $folder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $folder->getIdentifier()) {
				$folderRepository->requestDelete($row['uid']);
				break;
			}
		}
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $targetFolder->getName() . '\'');

		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $targetFolder->getIdentifier()) {
				$meta = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_metadata', 'file = ' . $file->getUid());
				if (isset($meta['uid'])) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file = '.$file->getUid(), ['folder_uid' => $row['uid']]);
				} else {
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_metadata', [
                        'file'       => $file->getUid(),
                        'folder_uid' => $row['uid'],
                        'tstamp'     => time(),
                        'crdate'     => time()
                    ]);
				}
				break;
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $targetFolder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $targetFolder->getIdentifier()) {
				$newFile = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid', 'sys_file', 'identifier = \'' . $targetFolder->getIdentifier().$file->getName() . '\'');
				$meta = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_metadata', 'file = ' . $newFile['uid']);
				if (isset($meta['uid'])){
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file = ' . $newFile['uid'], ['folder_uid' => $row['uid']]);
				} else {					
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_metadata', [
                        'file'       => $newFile['uid'],
                        'folder_uid' => $row['uid'],
                        'tstamp'     => time(),
                        'crdate'     =>time()
                    ]);
				}
				break;
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND title like \'' . $targetFolder->getName() . '\'');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== false) {
			if (FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $targetFolder->getIdentifier()) {
				$meta = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_metadata', 'file = ' . $file->getUid());
				if (isset($meta['uid'])){
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'file = '.$file->getUid(), ['folder_uid' => $row['uid']]);
				} else {					
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_metadata', [
                        'file'       => $file->getUid(),
                        'folder_uid' => $row['uid'],
                        'tstamp'     => time(),
                        'crdate'     =>time()
                    ]);
				}
				break;
			}
		}
	}
}
