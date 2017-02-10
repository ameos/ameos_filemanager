<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Ivan Kartolo <ivan at kartolo dot de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class for updating Direct Mail to version 3
 *
 * @author		Ivan Kartolo <ivan at kartolo dot de>
 * @package 	TYPO3
 * @subpackage 	tx_directmail
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Ameos\AmeosFilemanager\Tools\Tools;

class ext_update  {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		if (!GeneralUtility::_GP('do_initialize')) {
			$content = $this->displayWarning();
			$onClick = "document.location='".GeneralUtility::linkThisScript(array('do_initialize'=>1))."'; return false;";
			$content .= htmlspecialchars($GLOBALS['LANG']->getLL('update_convert_now')).'
				<br /><br />
				<form action=""><input type="submit" value="'.LocalizationUtility::translate('doInitialize', 'ameos_filemanager').'" onclick="'.htmlspecialchars($onClick).'"></form>
			';
		} else {
			$this->countFolder = 0;
			$this->countAddedFolder = 0;
			
			$updated = $this->initializeDatabase();
			$content .= $updated.'<br/>'.LocalizationUtility::translate('initializeCountFolder', 'ameos_filemanager').' : '.$this->countFolder. ' - ' . $this->countAddedFolder .'<br/>'.LocalizationUtility::translate('adviceInitialize', 'ameos_filemanager');

		}
		return $content;
	}

	/**
	 * Checks if extension is loaoed
	 *
	 * @return	boolean		true if user have access, otherwise false
	 */
	function access() {
			// We cannot update before the extension is installed: required tables are not yet in TCA
		if (ExtensionManagementUtility::isLoaded('ameos_filemanager')) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Go through the DB and initialize the basic settings to use the extension. 
	 *
	 * @return	string	indication about the success or failure of the task
	 */
	function initializeDatabase() {
		$contenu = '';
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$storageRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storages = $storageRepository->findAll();
		$i=0;
		foreach ($storages as $storage) {
			$this->currentStorage = $storage;
			$storagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $storage->getConfiguration()['basePath'];
			$this->setDatabaseForFolder(substr($storagePath, 0,-1), $storagePath);
		}
		return LocalizationUtility::translate('initializeSuccess', 'ameos_filemanager');
	}

	/**
	 *
	 * Display the description of the task
	 * @return string
	 */
	function displayWarning() {
		$out = '
			<div style="padding:15px 15px 20px 0;">
				<div class="typo3-message message-warning">
						<div class="message-header">'.LocalizationUtility::translate('warning', 'ameos_filemanager').'</div>
						<div class="message-body">'.LocalizationUtility::translate('warningInitialize', 'ameos_filemanager').'</div>
					</div>
				</div>
			</div>';

		return $out;
	}

	/**
	 *
	 * Parse a folder and add the necessary folder/file into the database
	 *
	 * @param Folder $folder the folder currently in treatment
	 * @param integer $uidParent his parent's uid
	 * @return string
	 */
	function setDatabaseForFolder($storageFolderPath, $folder, $uidParent = 0){
		$this->countFolder ++;
		if ($handle = opendir($folder)) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != "..") {
		            if(is_dir($folder . $entry)){
		            	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.title like '". $entry ."'" );
						$exist = false;
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							if(Tools::getFolderPathFromUid($row['uid']) == str_replace($storageFolderPath,'',$folder . $entry))
							{
								$exist = true;
								$uid = $row['uid'];
								break;
							}
						}
						if(!$exist)
						{
							$this->countAddedFolder ++;
							
							$GLOBALS['TYPO3_DB']->exec_INSERTquery(
								'tx_ameosfilemanager_domain_model_folder', 
								array(
									'title' => $entry,
									'pid' => 0,
									'cruser_id' => $GLOBALS["BE_USER"]->user["uid"],
									'uid_parent' => $uidParent,
									'crdate' => time(),
									'tstamp' => time(),
									'deleted' => 0,
									'hidden' => 0
								)
							);
							
							$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
							$this->countFolder++;
							
						}
		            	$this->setDatabaseForFolder($storageFolderPath, $folder . $entry . '/', $uid);
		            }
		            else{
		            	$this->setDatabaseForFile($storageFolderPath, $folder, $entry, $uidParent);
		            }
		        }
		    }
		    closedir($handle);
		}
	}

	function setDatabaseForFile($storageFolderPath, $folderPath, $entry , $folderParentUid){
		$filePath = $folderPath . $entry;
		$fileIdentifier = str_replace($storageFolderPath, '', $filePath);
		$infoFile = $this->currentStorage->getFileInfoByIdentifier($fileIdentifier, array());

		// Add file into sys_file if it doesn't exist
		$file = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid','sys_file', "identifier LIKE '" . $fileIdentifier . "'" );
		if($file){
			$fileUid = $file['uid'];

			// adding metadatas.
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'sys_file_metadata', 
				'sys_file_metadata.file = '.$fileUid, 
				array('folder_uid' => $folderParentUid), 
				$no_quote_fields=FALSE
			);
		}
		else{
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'sys_file', 
				array(
					'pid' => 0,
					'tstamp' => time(),
					'storage' => $infoFile['storage'],
					'identifier' => $infoFile['identifier'],
					'identifier_hash' => $infoFile['identifier_hash'],
					'folder_hash' => $infoFile['folder_hash'],
					'extension' =>  pathinfo($filePath)['extension'] ?: '',
					'mime_type' =>  $infoFile['mimetype'],
					'name' =>  $entry,
					//'sha1' => $this->currentStorage->hashFile($file, 'sha1'),
					'size' => $infoFile['size'],
					'creation_date' => $infoFile['ctime'],
					'modification_date' => $infoFile['mtime']
				)
			);

			$fileUid = $GLOBALS['TYPO3_DB']->sql_insert_id();

			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'sys_file_metadata', 
				array(
					'pid' => 0,
					'tstamp' => time(),
					'crdate' => time(),
					'file' => $fileUid,
				)
			);
		}	
	}
}

?>
