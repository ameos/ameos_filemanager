<?php

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\FileUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File as ResourceFile;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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

class FileController extends AbstractController
{
    /**
     * MetaDataRepository object
     *
     * @var MetaDataRepository
     * @Inject
     */
    protected $metaDataRepository;

    /**
     * Inject MetaDataRepository
     *
     * @param MetaDataRepository $metaDataRepository MetadataRepository object
     */
    public function injectMetaDataRepository(MetaDataRepository $metaDataRepository)
    {
        $this->metaDataRepository = $metaDataRepository;
    }

    /**
     * FiledownloadRepository object
     *
     * @var FiledownloadRepository
     * @Inject
     */
    protected $filedownloadRepository;

    /**
     * Inject FiledownloadRepository
     *
     * @param FiledownloadRepository $filedownloadRepository FiledownloadRepository object
     */
    public function injectFiledownloadRepository(
        FiledownloadRepository $filedownloadRepository
    ) {
        $this->filedownloadRepository = $filedownloadRepository;
    }

    /**
     * Errors array
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Edit file
     */
    protected function editAction()
    {
        $isNewFile = $this->request->getArgument(Configuration::FILE_ARGUMENT_KEY) == 'new';

        if ($isNewFile) {
            $folder = $this->folderRepository
                ->findByUid($this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY));
        } else {
            $file = $this->fileRepository->findByUid($this->request->getArgument(Configuration::FILE_ARGUMENT_KEY));
            $folder = $file->getParentFolder();
            $this->view->assign(Configuration::FILE_ARGUMENT_KEY, $file);
        }

        if ($this->request->getMethod() == 'POST') {
            $storage = $this->resourceFactory->getStorageObject($this->settings[Configuration::STORAGE_SETTINGS_KEY]);
            $arguments = $this->request->getArguments();

            $this->checkUploadedFileExistence($isNewFile, $arguments, Configuration::UPLOAD_ARGUMENT_KEY);
            $this->checkAllowedFileType($arguments, Configuration::UPLOAD_ARGUMENT_KEY);

            $newFilePath = $storage->getConfiguration()['basePath']
                . $folder->getGedPath()
                . '/'
                . $arguments[Configuration::UPLOAD_ARGUMENT_KEY]['name'];

            $this->checkFileAlreadyExists($arguments, Configuration::UPLOAD_ARGUMENT_KEY, $newFilePath);

            $newFileAdded = $this->uploadNewFile($arguments, Configuration::UPLOAD_ARGUMENT_KEY, $newFilePath);

            if (empty($this->errors)) {
                // create or update file
                $fileIdentifier = $folder->getGedPath() . '/' . $arguments[Configuration::UPLOAD_ARGUMENT_KEY]['name'];
                if ($isNewFile) {
                    $newfile = $storage->getFile($fileIdentifier);
                } elseif ($arguments[Configuration::UPLOAD_ARGUMENT_KEY]['name']) {
                    $originalResource = $this->getOriginalFileResource($file);
                    $storage->replaceFile($originalResource, $newFilePath);
                    $storage->renameFile(
                        $originalResource,
                        $arguments[Configuration::UPLOAD_ARGUMENT_KEY]['name']
                    );
                }

                $this->persistenceManager->persistAll();
                if ($isNewFile) {
                    $file = $this->fileRepository->findByUid($newfile->getUid());
                }

                // update file's properties
                $properties = array_merge([], $this->getFileProperties($isNewFile, $folder));
                $properties = array_merge($properties, $this->getFilePropertiesFromArguments($arguments));
                $properties = array_merge($properties, $this->getFilePropertiesFromSettings());

                $this->metaDataRepository->update($file->getUid(), $properties);
                $file->setCategories($arguments['categories']);

                $this->indexFileContent($newFileAdded, $file);

                $this->redirect(
                    Configuration::INDEX_ACTION_KEY,
                    Configuration::EXPLORER_CONTROLLER_KEY,
                    null,
                    [Configuration::FOLDER_ARGUMENT_KEY => $folder->getUid()]
                );
            } else {
                $this->queueErrorFlashMessages();
            }
        }

        $this->view->assign(Configuration::FOLDER_ARGUMENT_KEY, $folder->getUid());
        $this->view->assign('usergroups', $this->getAvailableUsergroups());
        $this->view->assign('categories', $this->getAvailableCategories());
        $this->view->assign('isUserLoggedIn', $this->isUserLoggedIn());
    }

    /**
     * Info file
     */
    protected function infoAction()
    {
        if (
            !$this->request->hasArgument(Configuration::FILE_ARGUMENT_KEY)
            || (int)$this->request->getArgument(Configuration::FILE_ARGUMENT_KEY) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFileArgument', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
        }

        $file = $this->fileRepository
            ->findByUid($this->request->getArgument(Configuration::FILE_ARGUMENT_KEY));
        $this->view->assign(Configuration::FILE_ARGUMENT_KEY, $file);
        $this->view->assign(
            'file_isimage',
            $this->getOriginalFileResource($file)->getType() == ResourceFile::FILETYPE_IMAGE
        );
        $this->view->assign('filemetadata_isloaded', ExtensionManagementUtility::isLoaded('filemetadata'));
    }

    /**
     * Upload files
     */
    protected function uploadAction()
    {
        $this->checkFolderArgumentExistence();

        // get folder
        $folder = $this->folderRepository->findByUid($this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY));

        // upload if POST
        if ($this->request->getMethod() === 'POST') {
            $storage = $this->resourceFactory->getStorageObject($this->settings[Configuration::STORAGE_SETTINGS_KEY]);

            $this->checkUploadedFileExistence(true, $_FILES, Configuration::FILE_ARGUMENT_KEY);
            $this->checkAllowedFileType($_FILES, Configuration::FILE_ARGUMENT_KEY);

            $newFilePath = $storage->getConfiguration()['basePath']
                . $folder->getGedPath()
                . '/'
                . $_FILES[Configuration::FILE_ARGUMENT_KEY]['name'];
            $this->checkFileAlreadyExists($_FILES, Configuration::FILE_ARGUMENT_KEY, $newFilePath);

            if (
                empty($this->errors)
                && $this->uploadNewFile($_FILES, Configuration::FILE_ARGUMENT_KEY, $newFilePath)
            ) {
                try {
                    // create or update file
                    $fileIdentifier = $folder->getGedPath() . '/' . $_FILES[Configuration::FILE_ARGUMENT_KEY]['name'];
                    $file = $storage->getFile($fileIdentifier);

                    $this->persistenceManager->persistAll();

                    $driver = GeneralUtility::makeInstance(LocalDriver::class);
                    $title = $driver->sanitizeFileName(
                        pathinfo($_FILES[Configuration::FILE_ARGUMENT_KEY]['name'], PATHINFO_FILENAME)
                    );
                    // update file's properties
                    $properties = array_merge([], $this->getFileProperties(true, $folder));
                    $properties = array_merge($properties, $this->getFilePropertiesFromSettings());
                    $properties['title'] = $title;

                    $this->metaDataRepository->update($file->getUid(), $properties);
                    $this->persistenceManager->persistAll();

                    $this->indexFileContent(true, $file);

                    $editUri = $this->uriBuilder->reset()
                        ->uriFor('edit', [Configuration::FILE_ARGUMENT_KEY => $file->getUid()]);
                    $infoUri = $this->uriBuilder->reset()
                        ->uriFor('info', [Configuration::FILE_ARGUMENT_KEY => $file->getUid()]);

                    header('Content-Type: text/json');
                    echo json_encode(
                        [
                            'success' => true,
                            Configuration::FILE_ARGUMENT_KEY => $file->getUid(),
                            'editUri' => $editUri,
                            'infoUri' => $infoUri,
                        ],
                        true
                    );
                    exit;
                } catch (\Exception $e) {
                    $errors[] = 'Error during file creation';
                }
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                echo implode("\n", $this->errors);
                exit;
            }
            header('Content-Type: text/json');
            echo json_encode(
                [
                    'success' => false,
                    'errors' => $errors,
                    'debug' => $_FILES,
                ],
                true
            );
            exit;
        }

        // assign to view
        $this->view->assign(Configuration::FOLDER_ARGUMENT_KEY, $folder);
        $this->view->assign('includeDropzone', true);
        $this->view->assign(
            'upload_uri',
            $this->uriBuilder->reset()
                ->uriFor(Configuration::UPLOAD_ARGUMENT_KEY, [Configuration::FOLDER_ARGUMENT_KEY => $folder->getUid()])
        );
        $this->view->assign(
            'upload_label_edit',
            LocalizationUtility::translate('edit', Configuration::EXTENSION_KEY)
        );
        $this->view->assign(
            'upload_label_detail',
            LocalizationUtility::translate('detail', Configuration::EXTENSION_KEY)
        );
    }

    /**
     * Download file
     */
    protected function downloadAction()
    {
        if (
            !$this->request->hasArgument(Configuration::FILE_ARGUMENT_KEY)
            || (int)$this->request->getArgument(Configuration::FILE_ARGUMENT_KEY) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFileArgument', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
        }

        DownloadUtility::downloadFile(
            (int)$this->request->getArgument(Configuration::FILE_ARGUMENT_KEY),
            $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
        );
    }

    /**
     * Remove the file
     */
    protected function removeAction()
    {
        if (
            !$this->request->hasArgument(Configuration::FILE_ARGUMENT_KEY)
            || (int)$this->request->getArgument(Configuration::FILE_ARGUMENT_KEY) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFileArgument', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
        }

        $file = $this->fileRepository->findByUid($this->request->getArgument(Configuration::FILE_ARGUMENT_KEY));
        $folder = $file->getParentFolder();

        FileUtility::remove(
            $this->request->getArgument(Configuration::FILE_ARGUMENT_KEY),
            $this->settings[Configuration::STORAGE_SETTINGS_KEY],
            $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
        );

        $this->addFlashMessage(LocalizationUtility::translate('fileRemoved', Configuration::EXTENSION_KEY));
        $this->redirect(
            Configuration::INDEX_ACTION_KEY,
            Configuration::EXPLORER_CONTROLLER_KEY,
            null,
            [Configuration::FOLDER_ARGUMENT_KEY => $folder->getUid()]
        );
    }

    private function checkUploadedFileExistence($isNewFile, $arguments, $argumentKey)
    {
        if ($isNewFile && $arguments[$argumentKey]['tmp_name'] == '') {
            $this->errors[] = LocalizationUtility::translate('fileMissing', Configuration::EXTENSION_KEY);
        }
    }

    private function checkAllowedFileType($arguments, $argumentKey)
    {
        $allowedFileExtension = explode(',', $this->settings['allowedFileExtension']);
        if (
            $arguments[$argumentKey]['name'] != ''
            && !in_array(
                strtolower(
                    pathinfo(
                        $arguments[$argumentKey]['name'],
                        PATHINFO_EXTENSION
                    )
                ),
                $allowedFileExtension
            )
        ) {
            $this->errors[] = LocalizationUtility::translate('notAllowedFileType', Configuration::EXTENSION_KEY);
        }
    }

    private function checkFileAlreadyExists($arguments, $argumentKey, $newFilePath)
    {
        if ($arguments[$argumentKey]['name'] != '' && file_exists($newFilePath)) {
            $this->errors[] = LocalizationUtility::translate('fileAlreadyExist', Configuration::EXTENSION_KEY);
        }
    }

    private function uploadNewFile($arguments, $argumentKey, $newFilePath)
    {
        if (empty($this->errors)) {
            if ($arguments[$argumentKey]['name'] != '') {
                if (move_uploaded_file($arguments[$argumentKey]['tmp_name'], $newFilePath)) {
                    return true;
                }
                $this->errors[] = LocalizationUtility::translate('fileUploadError', Configuration::EXTENSION_KEY);
            } else {
                return false;
            }
        }
        return false;
    }

    private function getFileProperties($isNewFile, $folder)
    {
        $properties = [];
        if ($isNewFile) {
            if ($this->isUserLoggedIn()) {
                $properties['fe_user_id'] = (int)$GLOBALS['TSFE']->fe_user->user['uid'];
            }
            $properties['folder_uid'] = $folder->getUid();
        }
        return $properties;
    }

    private function getFilePropertiesFromArguments($arguments)
    {
        $properties = [];

        if ($arguments[Configuration::TITLE_ARGUMENT_KEY]) {
            $driver = GeneralUtility::makeInstance(LocalDriver::class);
            $properties['title'] = $driver->sanitizeFileName($arguments[Configuration::TITLE_ARGUMENT_KEY]);
        }
        if (isset($arguments[Configuration::DESCRIPTION_ARGUMENT_KEY])) {
            $properties['description'] = $arguments[Configuration::DESCRIPTION_ARGUMENT_KEY];
        }
        if (isset($arguments[Configuration::KEYWORDS_ARGUMENT_KEY])) {
            $properties['keywords'] = $arguments[Configuration::KEYWORDS_ARGUMENT_KEY];
        }
        if (isset($arguments[Configuration::FE_GROUP_READ_ARGUMENT_KEY])) {
            if (is_array($arguments[Configuration::FE_GROUP_READ_ARGUMENT_KEY])) {
                $arguments[Configuration::FE_GROUP_READ_ARGUMENT_KEY]
                    = implode(',', $arguments[Configuration::FE_GROUP_READ_ARGUMENT_KEY]);
            }
            $properties[Configuration::FE_GROUP_READ_ARGUMENT_KEY]
                = $arguments[Configuration::FE_GROUP_READ_ARGUMENT_KEY];
        }
        if (isset($arguments[Configuration::FE_GROUP_WRITE_ARGUMENT_KEY])) {
            if (is_array($arguments[Configuration::FE_GROUP_WRITE_ARGUMENT_KEY])) {
                $arguments[Configuration::FE_GROUP_WRITE_ARGUMENT_KEY]
                    = implode(',', $arguments[Configuration::FE_GROUP_WRITE_ARGUMENT_KEY]);
            }
            $properties[Configuration::FE_GROUP_WRITE_ARGUMENT_KEY]
                = $arguments[Configuration::FE_GROUP_WRITE_ARGUMENT_KEY];
        }

        $properties['no_read_access'] = $arguments['no_read_access'] ? 1 : 0;
        $properties['no_write_access'] = $arguments['no_write_access'] ? 1 : 0;

        return $properties;
    }

    private function getFilePropertiesFromSettings()
    {
        $properties = [];
        $properties[Configuration::OWNER_HAS_READ_ACCESS_KEY]
            = isset($this->settings[Configuration::NEW_FILE_SETTINGS_KEY][Configuration::OWNER_HAS_READ_ACCESS_KEY])
                ? $this->settings[Configuration::NEW_FILE_SETTINGS_KEY][Configuration::OWNER_HAS_READ_ACCESS_KEY]
                : 1;
        $properties[Configuration::OWNER_HAS_WRITE_ACCESS_KEY]
            = isset($this->settings[Configuration::NEW_FILE_SETTINGS_KEY][Configuration::OWNER_HAS_WRITE_ACCESS_KEY])
                ? $this->settings[Configuration::NEW_FILE_SETTINGS_KEY][Configuration::OWNER_HAS_WRITE_ACCESS_KEY]
                : 1;
        return $properties;
    }

    private function indexFileContent($newFileAdded, $file)
    {
        if ($newFileAdded && FilemanagerUtility::fileContentSearchEnabled()) {
            $textExtractorRegistry = TextExtractorRegistry::getInstance();
            try {
                $originalResource = $this->getOriginalFileResource($file);
                $textExtractor = $textExtractorRegistry->getTextExtractor($originalResource);
                if (!is_null($textExtractor)) {
                    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                    $connectionPool->getConnectionForTable(Configuration::FILECONTENT_TABLENAME)
                        ->insert(
                            Configuration::FILECONTENT_TABLENAME,
                            [
                                Configuration::FILE_ARGUMENT_KEY => $file->getUid(),
                                'content' => $textExtractor
                                    ->extractText($originalResource),
                            ]
                        );
                }
            } catch (\Exception $e) {
                //
            }
        }
    }

    private function queueErrorFlashMessages()
    {
        foreach ($this->errors as $error) {
            $this->addFlashMessage(
                $error,
                '',
                FlashMessage::ERROR
            );
        }
    }

    private function getOriginalFileResource($file)
    {
        return $this->resourceFactory->getFileObject($file->getUid());
    }
}
