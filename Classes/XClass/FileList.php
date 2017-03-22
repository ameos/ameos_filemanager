<?php
namespace Ameos\AmeosFilemanager\XClass;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Slots\Slot;

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
		if (is_a($fileOrFolderObject, File::class) && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('write')) {
			$metaData = $fileOrFolderObject->_getMetaData();
			if ($metaData['folder_uid'] == 0) {
				$folder = $fileOrFolderObject->getStorage()->getFolder($fileOrFolderObject->getStorage()->getFolderIdentifierFromFileIdentifier($fileOrFolderObject->getIdentifier()));
				if ($folder != null){
					$slot = GeneralUtility::makeInstance(Slot::class);
					$slot->postFileAdd($fileOrFolderObject, $folder);
				}
			}
		}
		
		if (is_a($fileOrFolderObject, Folder::class)  && $fileOrFolderObject->checkActionPermission('write')) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_ameosfilemanager_domain_model_folder.uid',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.deleted = 0
                    AND tx_ameosfilemanager_domain_model_folder.storage = ' . $fileOrFolderObject->getStorage()->getUid() . '
                    AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $fileOrFolderObject->getIdentifier() . '\''
            );
			if (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) === FALSE) {
				$slot = GeneralUtility::makeInstance(Slot::class);
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
		if (is_a($fileOrFolderObject, File::class) && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('write')) {
			$metaData = $fileOrFolderObject->_getMetaData();
			$data = array('sys_file_metadata' => array($metaData['uid'] => 'edit'));
			$editOnClick = BackendUtility::editOnClick(GeneralUtility::implodeArrayForUrl('edit', $data), $GLOBALS['BACK_PATH'], $this->listUrl());
			
			$cells['editmetadata'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="Edit Metadata of this file">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
		}
		
		if (is_a($fileOrFolderObject, Folder::class)  && $fileOrFolderObject->checkActionPermission('write')) {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_ameosfilemanager_domain_model_folder.uid',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.deleted = 0
                    AND tx_ameosfilemanager_domain_model_folder.storage = ' . $fileOrFolderObject->getStorage()->getUid() . '
                    AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $fileOrFolderObject->getIdentifier() . '\''
            );
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if(FilemanagerUtility::getFolderPathFromUid($row['uid']).'/' == $fileOrFolderObject->getIdentifier())
				{
					$folder = array('tx_ameosfilemanager_domain_model_folder' => array($row['uid'] => 'edit'));
					$editOnClick = BackendUtility::editOnClick(GeneralUtility::implodeArrayForUrl('edit', $folder), $GLOBALS['BACK_PATH'], $this->listUrl());
					
					$cells['editmetadata'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="Edit Metadata of this folder">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';					
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
        if (version_compare(TYPO3_version, '8', '>=')) {            
            return $this->makeEdit8($fileOrFolderObject);
		} elseif (version_compare(TYPO3_version, '7', '>=')) {
			return $this->makeEdit7($fileOrFolderObject);
		}
	}

    /**
     * Creates the edit control section
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     * @return string HTML-table
     */
    public function makeEdit8($fileOrFolderObject)
    {
        $cells = [];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();

        // Edit file content (if editable)
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('write') && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
            $url = BackendUtility::getModuleUrl('file_edit', ['target' => $fullIdentifier]);
            $editOnClick = 'top.list_frame.location.href=' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
            $cells['edit'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.editcontent') . '">'
                . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                . '</a>';
        } else {
            $cells['edit'] = $this->spaceIcon;
        }
        if ($fileOrFolderObject instanceof File) {
            $fileUrl = $fileOrFolderObject->getPublicUrl(true);
            if ($fileUrl) {
                $aOnClick = 'return top.openUrlInWindow(' . GeneralUtility::quoteJSvalue($fileUrl) . ', \'WebFile\');';
                $cells['view'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($aOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.view') . '">' . $this->iconFactory->getIcon('actions-document-view', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() . '</a>';
            } else {
                $cells['view'] = $this->spaceIcon;
            }
        } else {
            $cells['view'] = $this->spaceIcon;
        }

        // replace file
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('replace')) {
            $url = BackendUtility::getModuleUrl('file_replace', ['target' => $fullIdentifier, 'uid' => $fileOrFolderObject->getUid()]);
            $replaceOnClick = 'top.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
            $cells['replace'] = '<a href="#" class="btn btn-default" onclick="' . $replaceOnClick . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.replace') . '">' . $this->iconFactory->getIcon('actions-edit-replace', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() . '</a>';
        }

        // rename the file
        if ($fileOrFolderObject->checkActionPermission('rename')) {
            $url = BackendUtility::getModuleUrl('file_rename', ['target' => $fullIdentifier]);
            $renameOnClick = 'top.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
            $cells['rename'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($renameOnClick) . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.rename') . '">' . $this->iconFactory->getIcon('actions-edit-rename', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() . '</a>';
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
            $cells['info'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($infoOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.info') . '">' . $this->iconFactory->getIcon('actions-document-info', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['info'] = $this->spaceIcon;
        }

        // delete the file
        if ($fileOrFolderObject->checkActionPermission('delete')) {
            $identifier = $fileOrFolderObject->getIdentifier();
            if ($fileOrFolderObject instanceof Folder) {
                $referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.referencesToFolder'));
                $deleteType = 'delete_folder';
            } else {
                $referenceCountText = BackendUtility::referenceCount('sys_file', $fileOrFolderObject->getUid(), ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile'));
                $deleteType = 'delete_file';
            }

            if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
                $confirmationCheck = '1';
            } else {
                $confirmationCheck = '0';
            }

            $deleteUrl = BackendUtility::getModuleUrl('tce_file');
            $confirmationMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.delete'), $fileOrFolderObject->getName()) . $referenceCountText;
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.delete');
            $cells['delete'] = '<a href="#" class="btn btn-default t3js-filelist-delete" data-content="' . htmlspecialchars($confirmationMessage)
                . '" data-check="' . $confirmationCheck
                . '" data-delete-url="' . htmlspecialchars($deleteUrl)
                . '" data-title="' . htmlspecialchars($title)
                . '" data-identifier="' . htmlspecialchars($fileOrFolderObject->getCombinedIdentifier())
                . '" data-delete-type="' . $deleteType
                . '" title="' . htmlspecialchars($title) . '">'
                . $this->iconFactory->getIcon('actions-edit-delete', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['delete'] = $this->spaceIcon;
        }

        // Hook for manipulating edit icons.
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
            $cells['__fileOrFolderObject'] = $fileOrFolderObject;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof FileListEditIconHookInterface) {
                    throw new \UnexpectedValueException(
                        $classData . ' must implement interface ' . FileListEditIconHookInterface::class,
                        1235225797
                    );
                }
                $hookObject->manipulateEditIcons($cells, $this);
            }
            unset($cells['__fileOrFolderObject']);
        }
        // Compile items into a DIV-element:
        $cells = array_merge($cells, $this->addAdditionalCells($fileOrFolderObject));
        return '<div class="btn-group">' . implode('', $cells) . '</div>';
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
		$cells = [];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();

        // Edit file content (if editable)
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('write') && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
            $url = BackendUtility::getModuleUrl('file_edit', ['target' => $fullIdentifier]);
            $editOnClick = 'top.content.list_frame.location.href=' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
            $cells['edit'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.editcontent') . '">'
                . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                . '</a>';
        } else {
            $cells['edit'] = $this->spaceIcon;
        }
        if ($fileOrFolderObject instanceof File) {
            $fileUrl = $fileOrFolderObject->getPublicUrl(true);
            if ($fileUrl) {
                $aOnClick = 'return top.openUrlInWindow(' . GeneralUtility::quoteJSvalue($fileUrl) . ', \'WebFile\');';
                $cells['view'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($aOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.view') . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
            } else {
                $cells['view'] = $this->spaceIcon;
            }
        } else {
            $cells['view'] = $this->spaceIcon;
        }

        // replace file
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('replace')) {
            $url = BackendUtility::getModuleUrl('file_replace', ['target' => $fullIdentifier, 'uid' => $fileOrFolderObject->getUid()]);
            $replaceOnClick = 'top.content.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
            $cells['replace'] = '<a href="#" class="btn btn-default" onclick="' . $replaceOnClick . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.replace') . '">' . $this->iconFactory->getIcon('actions-edit-replace', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // rename the file
        if ($fileOrFolderObject->checkActionPermission('rename')) {
            $url = BackendUtility::getModuleUrl('file_rename', ['target' => $fullIdentifier]);
            $renameOnClick = 'top.content.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
            $cells['rename'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($renameOnClick) . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.rename') . '">' . $this->iconFactory->getIcon('actions-edit-rename', Icon::SIZE_SMALL)->render() . '</a>';
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
            $cells['info'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($infoOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.info') . '">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['info'] = $this->spaceIcon;
        }

        // delete the file
        if ($fileOrFolderObject->checkActionPermission('delete')) {
            $identifier = $fileOrFolderObject->getIdentifier();
            if ($fileOrFolderObject instanceof Folder) {
                $referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToFolder'));
            } else {
                $referenceCountText = BackendUtility::referenceCount('sys_file', $fileOrFolderObject->getUid(), ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToFile'));
            }

            if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
                $confirmationCheck = '1';
            } else {
                $confirmationCheck = '0';
            }

            $deleteUrl = BackendUtility::getModuleUrl('tce_file');
            $confirmationMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'), $fileOrFolderObject->getName()) . $referenceCountText;
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.delete');
            $cells['delete'] = '<a href="#" class="btn btn-default t3js-filelist-delete" data-content="' . htmlspecialchars($confirmationMessage)
                . '" data-check="' . $confirmationCheck
                . '" data-delete-url="' . htmlspecialchars($deleteUrl)
                . '" data-title="' . htmlspecialchars($title)
                . '" data-identifier="' . htmlspecialchars($fileOrFolderObject->getCombinedIdentifier())
                . '" data-veri-code="' . $this->getBackendUser()->veriCode()
                . '" title="' . htmlspecialchars($title) . '">'
                . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['delete'] = $this->spaceIcon;
        }

        // Hook for manipulating edit icons.
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
            $cells['__fileOrFolderObject'] = $fileOrFolderObject;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof FileListEditIconHookInterface) {
                    throw new \UnexpectedValueException(
                        $classData . ' must implement interface ' . FileListEditIconHookInterface::class,
                        1235225797
                    );
                }
                $hookObject->manipulateEditIcons($cells, $this);
            }
            unset($cells['__fileOrFolderObject']);
        }
		
		$cells = array_merge($cells, $this->addAdditionalCells($fileOrFolderObject));
		// Compile items into a DIV-element:
		return '<div class="btn-group">' . implode('', $cells) . '</div>';
	}
}
