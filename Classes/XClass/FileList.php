<?php
namespace Ameos\AmeosFilemanager\XClass;

use TYPO3\CMS\FileList\FileList as CoreFileList;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Slots\SlotFile;
use Ameos\AmeosFilemanager\Slots\SlotFolder;
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

// XClassed to add an edit button for the folder in filelist
class FileList extends CoreFileList
{

    /**
     * indexFileOrFolder
     * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     */
    protected function indexFileOrFolder($fileOrFolderObject)
    {
        if (is_a($fileOrFolderObject, File::class)
            && $fileOrFolderObject->isIndexed()
            && $fileOrFolderObject->checkActionPermission('write')) {
            $metaData = $fileOrFolderObject->getMetaData()->get();
            if ($metaData['folder_uid'] == 0) {
                $folder = $fileOrFolderObject->getStorage()->getFolder($fileOrFolderObject->getStorage()->getFolderIdentifierFromFileIdentifier($fileOrFolderObject->getIdentifier()));
                if ($folder != null){
                    $slot = GeneralUtility::makeInstance(SlotFile::class);
                    $slot->add($fileOrFolderObject, $folder);
                }
            }
        }

        if (is_a($fileOrFolderObject, Folder::class)  && $fileOrFolderObject->checkActionPermission('write')) {
            $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
            $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
                $fileOrFolderObject->getStorage()->getUid(),
                $fileOrFolderObject->getIdentifier()
            );
            if (!$folderRecord) {
                $slot = GeneralUtility::makeInstance(SlotFolder::class);
                $slot->add($fileOrFolderObject);
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
            $metaData = $fileOrFolderObject->getMetaData()->get();
            $data = array('sys_file_metadata' => array($metaData['uid'] => 'edit'));
            $editOnClick = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit') . GeneralUtility::implodeArrayForUrl('edit', $data) . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));

            $cells['editmetadata'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="Edit Metadata of this file">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
        }

        if (is_a($fileOrFolderObject, Folder::class)  && $fileOrFolderObject->checkActionPermission('write')) {
            $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
            $row = $folderRepository->findRawByStorageAndIdentifier(
                $fileOrFolderObject->getStorage()->getUid(),
                $fileOrFolderObject->getIdentifier()
            );
            if($row && FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $fileOrFolderObject->getIdentifier()) {
                $folder = array('tx_ameosfilemanager_domain_model_folder' => array($row['uid'] => 'edit'));
                $editOnClick = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit') . GeneralUtility::implodeArrayForUrl('edit', $folder) . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));

                $cells['editmetadata'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="Edit Metadata of this folder">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
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
        $output = parent::makeEdit($fileOrFolderObject);
        $additionnalCells = $this->addAdditionalCells($fileOrFolderObject);
        return preg_replace('/(.*)<\/div>$/', '$1' . implode('', $additionnalCells) . '</div>', $output);
    }
}
