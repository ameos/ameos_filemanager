<?php
namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 
class FolderRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

	protected $defaultOrderings = array(
		'crdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	);
	
	/**
	 * Initialization
	 */
	public function initializeObject()
    {
		$querySettings = $this->createQuery()->getQuerySettings();
		$querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
	}

	/**
	 * Update the folder
	 * @param integer $uid uid of the folder
	 * @param array $field_values values to update
	 */
	public function requestUpdate($uid,$field_values)
    {
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_ameosfilemanager_domain_model_folder', 
			'tx_ameosfilemanager_domain_model_folder.uid = '.$uid, 
			$field_values, 
			$no_quote_fields=FALSE
		);
	}

	/**
	 * Delete a folder and all of it's content
	 * @param integer $uid folder uid
	 */
	public function requestDelete($uid)
    {
		$update = array(
			"deleted" => 1,
		);
		
		// Deleting files in the folder
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'sys_file_reference', 
			'sys_file_reference.uid_foreign = '.(int)$uid, 
			$update, 
			$no_quote_fields=FALSE
		);
		
		// Deleting the folder itself
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_ameosfilemanager_domain_model_folder', 
			'tx_ameosfilemanager_domain_model_folder.uid = '.(int)$uid, 
			$update, 
			$no_quote_fields=FALSE
		);

	}

	/**
	 * insert new Folder
	 * @param array $insertArray values of the folder
	 */
	public function requestInsert($insertArray)
    {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_ameosfilemanager_domain_model_folder', $insertArray);
	}

	public function countFilesForFolder($folderUid)
    {
		if (empty($folderUid)) {
			return 0;
		}
		$where = "sys_file_metadata.file = sys_file.uid AND sys_file_metadata.folder_uid = ".(int)$folderUid;
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as count','sys_file, sys_file_metadata', $where)['count'];

	}

	public function countFoldersForFolder($folderUid)
    {
		if (empty($folderUid)) {
			return 0;
		}	
		$where = "uid_parent = ".(int)$folderUid;
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as count','tx_ameosfilemanager_domain_model_folder', $where)['count'];
	}

	public function getSubFolderFromFolder($folderUid)
    {
		if (empty($folderUid)) {
			return 0;
		}
		$query = $this->createQuery();		
		$where = 'tx_ameosfilemanager_domain_model_folder.uid_parent = ' . (int)$folderUid;
		$where .= $this->getModifiedEnabledFields();
		$query->statement
		(	'	SELECT tx_ameosfilemanager_domain_model_folder.* 
				FROM tx_ameosfilemanager_domain_model_folder 
				WHERE '.$where.' 
				ORDER BY tx_ameosfilemanager_domain_model_folder.title ASC 
			',
			array()
		);
        $res = $query->execute();
		return $res;
	}

	public function getModifiedEnabledFields($writeMode = false)
    {
		$pageRepository = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');
		$enableFieldsWithFeGroup = $pageRepository->enableFields('tx_ameosfilemanager_domain_model_folder');
		$enableFieldsWithoutFeGroup = $pageRepository->enableFields('tx_ameosfilemanager_domain_model_folder',0,array('fe_group' => 1));

		$ownerOnlyField = $writeMode ? 'no_write_access':'no_read_access';

		if ($GLOBALS['TSFE']->fe_user->user) {
			$where = " AND (( 1=1" . $enableFieldsWithFeGroup . " AND (".$ownerOnlyField." = 0) ) OR ( 1=1".$enableFieldsWithoutFeGroup." AND tx_ameosfilemanager_domain_model_folder.fe_user_id = ".$GLOBALS['TSFE']->fe_user->user["uid"]."))";
		} else {
			$where = " AND (( 1=1" . $enableFieldsWithFeGroup . " AND (".$ownerOnlyField." = 0) ) AND ( 1=1".$enableFieldsWithoutFeGroup."))";
		}
		return $where;
	}

	public function findByUid($folderUid, $accessMode = 'read')
    {
		if (empty($folderUid)) {
			return 0;
		}
		// if write mode is set, we change the fegroup enablecolumns value to match the write column in the bdd
		switch ($accessMode) {
            case 'read':      $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_read';      break;
            case 'write':     $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_write';     break;
            case 'addfile':   $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_addfile';   break;
            case 'addfolder': $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_addfolder'; break;
		}
		$query = $this->createQuery();		
		$where = 'tx_ameosfilemanager_domain_model_folder.uid = ' . (int)$folderUid;
		$where .= $this->getModifiedEnabledFields($writeRight);
		$query->statement
		(	'	SELECT tx_ameosfilemanager_domain_model_folder.* 
				FROM tx_ameosfilemanager_domain_model_folder 
				WHERE '.$where.'
			',
			array()
		);

		// Don't forget to change back to read right once the deed is done
        $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_read';

        $res = $query->execute()->getFirst();
		return $res;
	}
}
