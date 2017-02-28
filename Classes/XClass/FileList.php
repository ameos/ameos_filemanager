<?php
namespace Ameos\AmeosFilemanager\XClass;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;

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

// XClassed to add an edit button for the folder in filelist
class FileList extends \TYPO3\CMS\FileList\FileList
{

	/**
	 * indexFileOrFolder
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	 */
	protected function indexFileOrFolder($fileOrFolderObject)
    {
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File') && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('write')) {
			$metaData = $fileOrFolderObject->_getMetaData();
			if($metaData['folder_uid'] == 0){
				$folder = $fileOrFolderObject->getStorage()->getFolder($fileOrFolderObject->getStorage()->getFolderIdentifierFromFileIdentifier($fileOrFolderObject->getIdentifier()));
				if($folder != null){
					$slot = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Slots\Slot');
					$slot->postFileAdd($fileOrFolderObject, $folder);
				}
			}
		}
		
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\Folder')  && $fileOrFolderObject->checkActionPermission('write')) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.title like '".$fileOrFolderObject->getName()."'" );
			if (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) === FALSE) {
				$slot = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Slots\Slot');
				$slot->postFolderAdd($fileOrFolderObject);
			}
		}
	}
	
	/**
	 * additionnal cells
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	 */
	protected function addAdditionalCells($fileOrFolderObject)
    {
		$cells = array();
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File') && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('write')) {
			$metaData = $fileOrFolderObject->_getMetaData();
			$data = array('sys_file_metadata' => array($metaData['uid'] => 'edit'));
			$editOnClick = BackendUtility::editOnClick(GeneralUtility::implodeArrayForUrl('edit', $data), $GLOBALS['BACK_PATH'], $this->listUrl());
			
			if (version_compare(TYPO3_version, '7', '>=')) {
				$cells['editmetadata'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="Edit Metadata of this file">' . IconUtility::getSpriteIcon('actions-page-open') . '</a>';
			} else {
				$cells['editmetadata'] = '<a href="#" onclick="' . $editOnClick . '" title="Edit Metadata of this file">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
			}
		}
		
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\Folder')  && $fileOrFolderObject->checkActionPermission('write')) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.title like '".$fileOrFolderObject->getName()."'" );
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if(FilemanagerUtility::getFolderPathFromUid($row['uid']).'/' == $fileOrFolderObject->getIdentifier())
				{
					$folder = array('tx_ameosfilemanager_domain_model_folder' => array($row['uid'] => 'edit'));
					$editOnClick = BackendUtility::editOnClick(GeneralUtility::implodeArrayForUrl('edit', $folder), $GLOBALS['BACK_PATH'], $this->listUrl());
					
					if (version_compare(TYPO3_version, '7', '>=')) {
						$cells['editmetadata'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="Edit Metadata of this folder">' . IconUtility::getSpriteIcon('actions-page-open') . '</a>';
					} else {
						$cells['editmetadata'] = '<a href="#" onclick="' . $editOnClick . '" title="Edit Metadata of this folder">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
					}
					
				}
			}
		}
		return $cells;
	}

	/**
	 * Creates the edit control section
	 *
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	 * @return string HTML-table
	 * @todo Define visibility
	 */
	public function makeEdit($fileOrFolderObject)
    {
		$this->indexFileOrFolder($fileOrFolderObject);
		if (version_compare(TYPO3_version, '7', '>=')) {
			return $this->makeEdit7($fileOrFolderObject);
		} else {
			return $this->makeEdit62($fileOrFolderObject);
		}
	}
	
	/**
	 * Creates the edit control section
	 *
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	 * @return string HTML-table
	 * @todo Define visibility
	 */
	protected function makeEdit7($fileOrFolderObject)
    {
		$cells = array();
		$fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
		// Edit file content (if editable)
		if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('write') && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
			$url = BackendUtility::getModuleUrl('file_edit', array('target' => $fullIdentifier));
			$editOnClick = 'top.content.list_frame.location.href=' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['edit'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.editcontent') . '">' . IconUtility::getSpriteIcon('actions-page-open') . '</a>';
		} else {
			$cells['edit'] = $this->spaceIcon;
		}
		if ($fileOrFolderObject instanceof File) {
			$fileUrl = $fileOrFolderObject->getPublicUrl(TRUE);
			if ($fileUrl) {
				$aOnClick = 'return top.openUrlInWindow(' . GeneralUtility::quoteJSvalue($fileUrl) . ', \'WebFile\');';
				$cells['view'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($aOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.view') . '">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			} else {
				$cells['view'] = $this->spaceIcon;
			}
		} else {
			$cells['view'] = $this->spaceIcon;
		}
		// rename the file
		if ($fileOrFolderObject->checkActionPermission('rename')) {
			$url = BackendUtility::getModuleUrl('file_rename', array('target' => $fullIdentifier));
			$renameOnClick = 'top.content.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['rename'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($renameOnClick) . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.rename') . '">' . IconUtility::getSpriteIcon('actions-edit-rename') . '</a>';
		} else {
			$cells['rename'] = $this->spaceIcon;
		}
		if ($fileOrFolderObject->checkActionPermission('read')) {
			$infoOnClick = '';
			if ($fileOrFolderObject instanceof Folder) {
				$infoOnClick = 'top.launchView( \'_FOLDER\', ' . GeneralUtility::quoteJSvalue($fullIdentifier) . ');return false;';
			} elseif ($fileOrFolderObject instanceof File) {
				$infoOnClick = 'top.launchView( \'_FILE\', ' . GeneralUtility::quoteJSvalue($fullIdentifier) . ');return false;';
			}
			$cells['info'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($infoOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.info') . '">' . IconUtility::getSpriteIcon('status-dialog-information') . '</a>';
		} else {
			$cells['info'] = $this->spaceIcon;
		}

		// delete the file
		if ($fileOrFolderObject->checkActionPermission('delete')) {
			$identifier = $fileOrFolderObject->getIdentifier();
			if ($fileOrFolderObject instanceof Folder) {
				$referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, ' (There are %s reference(s) to this folder!)');
			} else {
				$referenceCountText = BackendUtility::referenceCount('sys_file', $fileOrFolderObject->getUid(), ' (There are %s reference(s) to this file!)');
			}

			if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
				$confirmationCheck = 'confirm(' . GeneralUtility::quoteJSvalue(sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'), $fileOrFolderObject->getName()) . $referenceCountText) . ')';
			} else {
				$confirmationCheck = '1 == 1';
			}

			$removeOnClick = 'if (' . $confirmationCheck . ') { top.content.list_frame.location.href=' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_file') .'&file[delete][0][data]=' . rawurlencode($fileOrFolderObject->getCombinedIdentifier()) . '&vC=' . $this->getBackendUser()->veriCode() . BackendUtility::getUrlToken('tceAction') . '&redirect=') . '+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);};';

			$cells['delete'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($removeOnClick) . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.delete') . '">' . IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
		} else {
			$cells['delete'] = $this->spaceIcon;
		}

		// Hook for manipulating edit icons.
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof FileListEditIconHookInterface) {
					throw new \UnexpectedValueException(
						'$hookObject must implement interface \\TYPO3\\CMS\\Filelist\\FileListEditIconHookInterface',
						1235225797
					);
				}
				$hookObject->manipulateEditIcons($cells, $this);
			}
		}
		
		$cells = array_merge($cells, $this->addAdditionalCells($fileOrFolderObject));
		// Compile items into a DIV-element:
		return '<div class="btn-group">' . implode('', $cells) . '</div>';
	}
	
	/**
	 * Creates the edit control section
	 *
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	 * @return string HTML-table
	 * @todo Define visibility
	 */
	protected function makeEdit62($fileOrFolderObject)
    {
		$cells = array();
		$fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
		// Edit metadata of file
		try {
			if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File') && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('write')) {
				$metaData = $fileOrFolderObject->_getMetaData();
				$data = array(
					'sys_file_metadata' => array($metaData['uid'] => 'edit')
				);
				$editOnClick = BackendUtility::editOnClick(GeneralUtility::implodeArrayForUrl('edit', $data), $GLOBALS['BACK_PATH'], $this->listUrl());
				$cells['editmetadata'] = '<a href="#" onclick="' . $editOnClick . '" title="Edit Metadata of this file">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
			} else {
				$cells['editmetadata'] = IconUtility::getSpriteIcon('empty-empty');
			}
		} catch (\Exception $e) {
			$cells['editmetadata'] = IconUtility::getSpriteIcon('empty-empty');
		}
		// Edit file content (if editable)
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File') && $fileOrFolderObject->checkActionPermission('write') && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
			$editOnClick = 'top.content.list_frame.location.href=top.TS.PATH_typo3+\'file_edit.php?target=' . rawurlencode($fullIdentifier) . '&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['edit'] = '<a href="#" onclick="' . $editOnClick . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.editcontent') . '">' . IconUtility::getSpriteIcon('actions-page-open') . '</a>';
		} else {
			$cells['edit'] = IconUtility::getSpriteIcon('empty-empty');
		}
		// rename the file
		if ($fileOrFolderObject->checkActionPermission('rename')) {
			$renameOnClick = 'top.content.list_frame.location.href = top.TS.PATH_typo3+\'file_rename.php?target=' . rawurlencode($fullIdentifier) . '&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['rename'] = '<a href="#" onclick="' . $renameOnClick . '"  title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.rename') . '">' . IconUtility::getSpriteIcon('actions-edit-rename') . '</a>';
		} else {
			$cells['rename'] = IconUtility::getSpriteIcon('empty-empty');
		}
		if ($fileOrFolderObject->checkActionPermission('read')) {
			if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\Folder')) {
				$infoOnClick = 'top.launchView( \'_FOLDER\', \'' . $fullIdentifier . '\');return false;';
			} elseif (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File')) {
				$infoOnClick = 'top.launchView( \'_FILE\', \'' . $fullIdentifier . '\');return false;';
			}
			$cells['info'] = '<a href="#" onclick="' . $infoOnClick . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.info') . '">' . IconUtility::getSpriteIcon('status-dialog-information') . '</a>';
		} else {
			$cells['info'] = IconUtility::getSpriteIcon('empty-empty');
		}

		// delete the file
		if ($fileOrFolderObject->checkActionPermission('delete')) {
			$identifier = $fileOrFolderObject->getIdentifier();
			if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, ' (There are %s reference(s) to this folder!)');
			} else {
				$referenceCountText = BackendUtility::referenceCount('sys_file', $identifier, ' (There are %s reference(s) to this file!)');
			}

			if ($GLOBALS['BE_USER']->jsConfirmation(4)) {
				$confirmationCheck = 'confirm(' . GeneralUtility::quoteJSvalue(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'), $fileOrFolderObject->getName()) . $referenceCountText) . ')';
			} else {
				$confirmationCheck = '1 == 1';
			}

			$removeOnClick = 'if (' . $confirmationCheck . ') { top.content.list_frame.location.href=top.TS.PATH_typo3+\'tce_file.php?file[delete][0][data]=' . rawurlencode($fileOrFolderObject->getCombinedIdentifier()) . '&vC=' . $GLOBALS['BE_USER']->veriCode() . BackendUtility::getUrlToken('tceAction') .  '&redirect=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);};';

			$cells['delete'] = '<a href="#" onclick="' . htmlspecialchars($removeOnClick) . '"  title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.delete') . '">' . IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
		} else {
			$cells['delete'] = IconUtility::getSpriteIcon('empty-empty');
		}

		// Hook for manipulating edit icons.
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof \TYPO3\CMS\Filelist\FileListEditIconHookInterface) {
					throw new \UnexpectedValueException(
						'$hookObject must implement interface \\TYPO3\\CMS\\Filelist\\FileListEditIconHookInterface',
						1235225797
					);
				}
				$hookObject->manipulateEditIcons($cells, $this);
			}
		}
		
		$cells = array_merge($cells, $this->addAdditionalCells($fileOrFolderObject));
		// Compile items into a DIV-element:
		return '							<!-- EDIT CONTROLS: -->
											<div class="typo3-editCtrl">
												' . implode('
												', $cells) . '
											</div>';
	}
}
