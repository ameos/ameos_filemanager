<?php
namespace Ameos\AmeosFilemanager\Controller\Explorer;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\File as ResourceFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Utility\FileUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Domain\Model\File;

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
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository
     */
    protected $filedownloadRepository;

    /**
     * edit file
     */
    protected function editAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        $allowedFileExtension = explode(',', $this->settings['allowedFileExtension']);
        $isNewFile = $this->request->getArgument('file') == 'new';

        if ($isNewFile) {
            $folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));
        } else {
            $file   = $this->fileRepository->findByUid($this->request->getArgument('file'));
            $folder = $file->getParentFolder();
            $this->view->assign('file', $file);
        }

        if ($this->request->getMethod() == 'POST') {
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($this->settings['storage']);
            $driver = $this->objectManager->get(LocalDriver::class);

            $arguments = $this->request->getArguments();

            $hasError = false;
            if ($isNewFile && $arguments['upload']['tmp_name'] == '') {
                $hasError = true;
                $this->addFlashMessage(LocalizationUtility::translate('fileMissing', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            }

            if ($arguments['upload']['name'] != ''
                && !in_array(strtolower(pathinfo($arguments['upload']['name'], PATHINFO_EXTENSION)), $allowedFileExtension)) {
                $hasError = true;
                $this->addFlashMessage(LocalizationUtility::translate('notAllowedFileType', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            }

            if ($arguments['upload']['name'] != ''
                && file_exists($storage->getConfiguration()['basePath'] . $folder->getGedPath() . '/' . $arguments['upload']['name'])) {
                $hasError = true;
                $this->addFlashMessage(LocalizationUtility::translate('fileAlreadyExist', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            }

            $newFileAdded = false;
            if (!$hasError) {
                $fileIsMoved = move_uploaded_file(
                    $arguments['upload']['tmp_name'],
                    $storage->getConfiguration()['basePath'] . $folder->getGedPath() . '/' . $arguments['upload']['name']
                );
                if ($arguments['upload']['name'] != '' && !$fileIsMoved) {
                    $hasError = true;
                    $this->addFlashMessage(LocalizationUtility::translate('fileUploadError', 'AmeosFilemanager'), '', FlashMessage::ERROR);
                } else {
                    $newFileAdded = true;
                }
            }

            if (!$hasError) {
                // create or update file
                $fileIdentifier = $folder->getGedPath() . '/' . $arguments['upload']['name'];
                if ($isNewFile) {
                    $newfile = $storage->getFile($fileIdentifier);
                } elseif ($arguments['upload']['name']) {
                    $storage->renameFile($file->getOriginalResource(), $arguments['upload']['name']);
                }

                $this->persistenceManager->persistAll();
                if ($isNewFile) {
                    $file = $this->fileRepository->findByUid($newfile->getUid());
                }

                // update file's properties
                $properties = [];
                if ($isNewFile && GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
                    $properties['fe_user_id'] = (int)$GLOBALS['TSFE']->fe_user->user['uid'];
                }
                if ($arguments['title']) { $properties['title'] = $arguments['title']; }
                if ($arguments['description']) { $properties['description'] = $arguments['description']; }
                if ($arguments['keywords']) { $properties['keywords'] = $arguments['keywords']; }
                if ($arguments['fe_group_read']) { $properties['fe_group_read'] = implode(',', $arguments['fe_group_read']); }
                if ($arguments['fe_group_write']) { $properties['fe_group_write'] = implode(',', $arguments['fe_group_write']); }

                $properties['no_read_access']  = $arguments['no_read_access'] ? 1 : 0;
                $properties['no_write_access'] = $arguments['no_write_access'] ? 1 : 0;
                $properties['owner_has_read_access']  = isset($this->settings['newFile']['owner_has_read_access'])
                    ? $this->settings['newFile']['owner_has_read_access']  : 1;
                $properties['owner_has_write_access'] = isset($this->settings['newFile']['owner_has_write_access'])
                    ? $this->settings['newFile']['owner_has_write_access'] : 1;

                if ($isNewFile) {
                    $properties['folder_uid'] = $folder->getUid();
                }
                $metaDataRepository = $this->objectManager->get(MetaDataRepository::class);
                $metaDataRepository->update($file->getUid(), $properties);
                $file->setCategories($arguments['categories']);

                if ($newFileAdded && FilemanagerUtility::fileContentSearchEnabled()) {
                    $textExtractorRegistry = TextExtractorRegistry::getInstance();
                    try {
                        $textExtractor = $textExtractorRegistry->getTextExtractor($file->getOriginalResource());
                        if (!is_null($textExtractor)) {
                            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                            $connectionPool
                                ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                                ->insert('tx_ameosfilemanager_domain_model_filecontent', [
                                    'file'    => $file->getUid(),
                                    'content' => $textExtractor->extractText($file->getOriginalResource())
                                ]);
                        }
                    } catch (\Exception $e) {

                    }
                }

                $this->redirect('index', 'Explorer\\Explorer', null, ['folder' => $folder->getUid()]);
            }
        }

        $this->view->assign('folder', $folder->getUid());
        $this->view->assign('usergroups', $this->getAvailableUsergroups());
        $this->view->assign('categories', $this->getAvailableCategories());
    }

    /**
     * info file
     */
    protected function infoAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        if (!$this->request->hasArgument('file') || (int)$this->request->getArgument('file') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFileArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        /** @var File $file */
        $file = $this->fileRepository->findByUid($this->request->getArgument('file'));
        $this->view->assign('file', $file);
        $this->view->assign('file_isimage', $file->getOriginalResource()->getType() == ResourceFile::FILETYPE_IMAGE);
        $this->view->assign('filemetadata_isloaded', ExtensionManagementUtility::isLoaded('filemetadata'));
    }

    /**
     * upload files
     */
    protected function uploadAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        if (!$this->request->hasArgument('folder') || (int)$this->request->getArgument('folder') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFolderArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        // get folder
        $folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));

        // upload if POST
        if ($this->request->getMethod() === 'POST') {
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($this->settings['storage']);
            $driver = $this->objectManager->get(LocalDriver::class);

            $errors = [];
            if ($_FILES['file']['tmp_name'] == '') {
                $errors[] = LocalizationUtility::translate('fileMissing', 'AmeosFilemanager');
            }

            $allowedFileExtension = explode(',', $this->settings['allowedFileExtension']);
            if (!in_array(strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)), $allowedFileExtension)) {
                $errors[] = LocalizationUtility::translate('notAllowedFileType', 'AmeosFilemanager');
            }

            if (file_exists($storage->getConfiguration()['basePath'] . $folder->getGedPath() . '/' . $_FILES['file']['name'])) {
                $errors[] = LocalizationUtility::translate('fileAlreadyExist', 'AmeosFilemanager');
            }

            if (empty($errors)) {
                $fileIsMoved = move_uploaded_file(
                    $_FILES['file']['tmp_name'],
                    $storage->getConfiguration()['basePath'] . $folder->getGedPath() . '/' . $_FILES['file']['name']
                );
                if (!$fileIsMoved) {
                    $errors[] = LocalizationUtility::translate('missingFolderArgument', 'AmeosFilemanager');
                }
            }

            if (empty($errors)) {
                try {
                    // create or update file
                    $fileIdentifier = $folder->getGedPath() . '/' . $_FILES['file']['name'];
                    $file = $storage->getFile($fileIdentifier);

                    $this->persistenceManager->persistAll();

                    // update file's properties
                    $properties = [];
                    if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
                        $properties['fe_user_id'] = (int)$GLOBALS['TSFE']->fe_user->user['uid'];
                    }
                    $properties['owner_has_read_access']  = isset($this->settings['newFile']['owner_has_read_access'])
                        ? $this->settings['newFile']['owner_has_read_access']  : 1;
                    $properties['owner_has_write_access'] = isset($this->settings['newFile']['owner_has_write_access'])
                        ? $this->settings['newFile']['owner_has_write_access'] : 1;

                    $properties['title'] = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
                    $properties['folder_uid'] = $folder->getUid();

                    $metaDataRepository = $this->objectManager->get(MetaDataRepository::class);
                    $metaDataRepository->update($file->getUid(), $properties);
                    $this->persistenceManager->persistAll();

                    if (FilemanagerUtility::fileContentSearchEnabled()) {
                        $textExtractorRegistry = TextExtractorRegistry::getInstance();
                        try {
                            $textExtractor = $textExtractorRegistry->getTextExtractor($file);
                            if (!is_null($textExtractor)) {
                                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                                $connectionPool
                                    ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                                    ->insert('tx_ameosfilemanager_domain_model_filecontent', [
                                        'file'    => $file->getUid(),
                                        'content' => $textExtractor->extractText($file)
                                    ]);
                            }
                        } catch (\Exception $e) {

                        }
                    }

                    $editUri = $this->uriBuilder->reset()->uriFor('edit', ['file' => $file->getUid()]);
                    $infoUri = $this->uriBuilder->reset()->uriFor('info', ['file' => $file->getUid()]);

                    header('Content-Type: text/json');
                    echo json_encode([
                        'success' => true,
                        'file'    => $file->getUid(),
                        'editUri' => $editUri,
                        'infoUri' => $infoUri,
                    ], true);
                    exit;
                } catch (\Exception $e) {
                    $errors[] = 'Error during file creation';
                }
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                echo implode("\n", $errors);
                exit;
            }
            header('Content-Type: text/json');
            echo json_encode([
                'success' => false,
                'errors'  => $errors,
                'debug'   => $_FILES
            ], true);
            exit;
        }

        // assign to view
        $this->view->assign('folder', $folder);
        $this->view->assign('includeDropzone', true);
        $this->view->assign('upload_uri', $this->uriBuilder->reset()->uriFor('upload', ['folder' => $folder->getUid()]));
        $this->view->assign('upload_label_edit', LocalizationUtility::translate('edit', 'AmeosFilemanager'));
        $this->view->assign('upload_label_detail', LocalizationUtility::translate('detail', 'AmeosFilemanager'));
    }

    /**
     * download file
     */
    protected function downloadAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        if (!$this->request->hasArgument('file') || (int)$this->request->getArgument('file') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFileArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        DownloadUtility::downloadFile((int)$this->request->getArgument('file'), $this->settings['startFolder']);
    }

    /**
     * Remove the file
     */
    protected function removeAction()
    {
        if (!$this->request->hasArgument('file') || (int)$this->request->getArgument('file') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFileArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        $file = $this->fileRepository->findByUid($this->request->getArgument('file'));
        $folder = $file->getParentFolder();

        FileUtility::remove(
            $this->request->getArgument('file'),
            $this->settings['storage'],
            $this->settings['startFolder']
        );

        $this->addFlashMessage(LocalizationUtility::translate('fileRemoved', 'AmeosFilemanager'));
        $this->redirect('index', 'Explorer\\Explorer', null, ['folder' => $folder->getUid()]);
    }

    public function injectPersistenceManager(PersistenceManager $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function injectFiledownloadRepository(FiledownloadRepository $filedownloadRepository): void
    {
        $this->filedownloadRepository = $filedownloadRepository;
    }
}
