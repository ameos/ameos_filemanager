<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\XClass;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\FileUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;



// XClassed to add an edit button for the folder in filelist
class FileList extends \TYPO3\CMS\FileList\FileList
{
    /**
     * indexFileOrFolder
     * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     */
    protected function indexFileOrFolder($fileOrFolderObject)
    {
        if (
            is_a($fileOrFolderObject, File::class)
            && $fileOrFolderObject->isIndexed()
            && $fileOrFolderObject->checkActionPermission('write')
        ) {
            $metaData = $fileOrFolderObject->getMetaData()->get();
            if ($metaData['folder_uid'] == 0) {
                $folder = $fileOrFolderObject
                    ->getStorage()
                    ->getFolder(
                        $fileOrFolderObject
                            ->getStorage()
                            ->getFolderIdentifierFromFileIdentifier($fileOrFolderObject->getIdentifier())
                    );
                if ($folder != null) {
                    FileUtility::add($fileOrFolderObject, $folder);
                }
            }
        }

        if (is_a($fileOrFolderObject, Folder::class)  && $fileOrFolderObject->checkActionPermission('write')) {
            $folderRecord = GeneralUtility::makeInstance(FolderRepository::class)->findRawByStorageAndIdentifier(
                $fileOrFolderObject->getStorage()->getUid(),
                $fileOrFolderObject->getIdentifier()
            );
            if (!$folderRecord) {
                FolderUtility::add($fileOrFolderObject);
            }
        }
    }

    /**
     * additionnal cells
     * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     */
    protected function addAdditionalCells($fileOrFolderObject)
    {
        $cells = [];
        if (
            is_a($fileOrFolderObject, File::class)
            && $fileOrFolderObject->isIndexed()
            && $fileOrFolderObject->checkActionPermission('write')
        ) {
            $metaData = $fileOrFolderObject->getMetaData()->get();
            $data = [
                'edit' => [
                    'sys_file_metadata' => [
                        (int)$metaData['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
            ];
            $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $data);
            $cells['editmetadata'] = '<a href="' . $editLink . '" class="btn btn-default" title="Edit Metadata of this file">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
        }

        if (is_a($fileOrFolderObject, Folder::class)  && $fileOrFolderObject->checkActionPermission('write')) {
            $row = GeneralUtility::makeInstance(FolderRepository::class)->findRawByStorageAndIdentifier(
                $fileOrFolderObject->getStorage()->getUid(),
                $fileOrFolderObject->getIdentifier()
            );
            if (
                $row
                && FilemanagerUtility::getFolderPathFromUid($row['uid']) . '/' == $fileOrFolderObject->getIdentifier()
            ) {
                $data = [
                    'edit' => [
                        'tx_ameosfilemanager_domain_model_folder' => [
                            (int)$row['uid'] => 'edit',
                        ],
                    ],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $data);
                $cells['editmetadata'] = '<a href="' . $editLink . '" class="btn btn-default" title="Edit Metadata of this folder">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }
        return $cells;
    }

    /**
     * Creates the edit control section
     *
     * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     * @return string HTML-table
     */
    public function makeEdit($fileOrFolderObject)
    {
        $this->indexFileOrFolder($fileOrFolderObject);
        $output = parent::makeEdit($fileOrFolderObject);
        $additionnalCells = $this->addAdditionalCells($fileOrFolderObject);
        return preg_replace('/(.*)<\/div>$/', '$1' . implode('', $additionnalCells) . '</div>', $output);
    }
}
