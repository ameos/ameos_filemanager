<?php

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

class FolderController extends AbstractController
{
    /**
     * Edit folder
     */
    protected function editAction()
    {
        $isNewFolder = $this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY) === 'new';
        $back = $this->getBackFolder();
        $folder = $this->getFolder($isNewFolder);
        $successFlashMessageKey = $this->getSuccessFlashMessageKey($isNewFolder);

        if ($this->request->getMethod() === 'POST') {
            if (
                !$this->request->hasArgument(Configuration::TITLE_ARGUMENT_KEY)
                || $this->request->getArgument(Configuration::TITLE_ARGUMENT_KEY) == ''
            ) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('titleRequired', Configuration::EXTENSION_KEY),
                    '',
                    FlashMessage::ERROR
                );
            } else {
                $storage = $this->resourceFactory
                    ->getStorageObject($this->settings[Configuration::STORAGE_SETTINGS_KEY]);
                $driver = GeneralUtility::makeInstance(LocalDriver::class);

                $title = $driver->sanitizeFileName($this->request->getArgument(Configuration::TITLE_ARGUMENT_KEY));
                if ($isNewFolder) {
                    $parent = $this->folderRepository->findByUid(
                        $this->request->getArgument(Configuration::PARENTFOLDER_ARGUMENT_KEY)
                    );
                    $storageFolder = $storage->getFolder($parent->getGedPath() . '/');
                    $title = $this->createFolder($storageFolder, $title);

                    $folder->setUidParent($parent->getUid());
                    $folder->setIdentifier($parent->getGedPath() . '/' . $title . '/');
                } else {
                    $storageFolder = $storage->getFolder($folder->getGedPath() . '/');
                    $title = $this->renameFolder($storageFolder, $title);

                    $folder->setIdentifier($folder->getGedPath() . '/');
                }

                $folder->setTitle($title);
                $folder = $this->setFolderDataFromRequest($folder);

                $this->folderRepository->add($folder);
                $this->persistenceManager->persistAll();

                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        $successFlashMessageKey,
                        Configuration::EXTENSION_KEY,
                        [$title]
                    )
                );

                $this->redirect(
                    Configuration::INDEX_ACTION_KEY,
                    Configuration::EXPLORER_CONTROLLER_KEY,
                    null,
                    [Configuration::FOLDER_ARGUMENT_KEY => $folder->getUid()]
                );
            }
        }

        $this->view->assign(Configuration::FOLDER_ARGUMENT_KEY, $folder);
        $this->view->assign('back', $back);
        $this->view->assign(
            'parent',
            $this->getParentFolderArgument()
        );
        $this->view->assign('usergroups', $this->getAvailableUsergroups());
        $this->view->assign('categories', $this->getAvailableCategories());
        $this->view->assign('isUserLoggedIn', $this->isUserLoggedIn());
    }

    /**
     * Download folder as zip
     */
    protected function downloadAction()
    {
        $this->checkFolderArgumentExistence();

        $rootFolder = $this->folderRepository
            ->findByUid((int)$this->settings[Configuration::START_FOLDER_SETTINGS_KEY]);
        $folder = $this->folderRepository
            ->findByUid((int)$this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY));

        if (!$folder || !$folder->isChildOf((int)$this->settings[Configuration::START_FOLDER_SETTINGS_KEY])) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
        }

        $storage = $this->resourceFactory->getStorageObject($this->settings[Configuration::STORAGE_SETTINGS_KEY]);

        $zipPath  = Environment::getPublicPath() . '/typo3temp/' . $folder->getTitle() . '_' . date('dmY_His') . '.zip';
        $filePath = Environment::getPublicPath()
            . '/'
            . trim($storage->getConfiguration()['basePath'], '/')
            . $folder->getGedPath();

        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ameos_filemanager');

        if ($configuration['use_ziparchive']) {
            $this->downloadFolderWithZipArchive($zipPath, $filePath, $folder, $rootFolder);
        } else {
            $this->downloadFolderWithoutZipArchive($zipPath, $filePath, $folder, $rootFolder);
        }
    }

    /**
     * Delete the folder
     */
    protected function removeAction()
    {
        $this->checkFolderArgumentExistence();

        $folder = $this->folderRepository->findByUid($this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY));
        $parentFolder = $folder->getParent();

        FolderUtility::remove(
            $this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY),
            $this->settings[Configuration::STORAGE_SETTINGS_KEY],
            $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
        );

        $this->addFlashMessage(LocalizationUtility::translate('folderRemoved', Configuration::EXTENSION_KEY));
        $this->redirect(
            Configuration::INDEX_ACTION_KEY,
            Configuration::EXPLORER_CONTROLLER_KEY,
            null,
            [Configuration::FOLDER_ARGUMENT_KEY => $parentFolder->getUid()]
        );
    }

    /**
     * Info folder
     */
    protected function infoAction()
    {
        $this->checkFolderArgumentExistence();

        $folder = $this->folderRepository->findByUid($this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY));
        $this->view->assign(Configuration::FOLDER_ARGUMENT_KEY, $folder);
    }

    protected function getBackFolder($isNewFolder = false)
    {
        $back = $this->getParentFolderArgument();
        if (empty($back)) {
            $back = $this->getFolderArgument();
        }
        return $back;
    }

    protected function getFolder($isNewFolder = false)
    {
        $folder = null;
        if ($isNewFolder === true) {
            $folder = GeneralUtility::makeInstance(Folder::class);
        } else {
            $folder = $this->folderRepository->findByUid($this->getFolderArgument());
        }
        return $folder;
    }

    protected function getSuccessFlashMessageKey($isNewFolder = false)
    {
        $key = '';
        if ($isNewFolder === true) {
            $key = 'folderCreated';
        } else {
            $key = 'folderUpdated';
        }
        return $key;
    }

    protected function downloadFolderWithZipArchive($zipPath, $filePath, $folder, $rootFolder)
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \Ameos\AmeosFilemanager\Exception\MissingPackageException(
                'ZipArchive is not installed on your server : see http://php.net/ZipArchive'
            );
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        DownloadUtility::addFolderToZip(
            $filePath,
            $folder,
            $zip,
            $this->settings[Configuration::START_FOLDER_SETTINGS_KEY],
            (
                $this->settings[Configuration::RECURSION_SETTINGS_KEY] == ''
                    ? false
                    : (int)$this->settings[Configuration::RECURSION_SETTINGS_KEY]
            ),
            FilemanagerUtility::calculRecursion($rootFolder, $folder)
        );
        $zip->close();
        if (file_exists($zipPath)) {
            $filesize = filesize($zipPath);
            header('Content-Type: ' . mime_content_type($zipPath));
            header('Content-Transfer-Encoding: Binary');
            header('Content-Length: ' . $filesize);
            header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            ob_clean();
            flush();
            readfile($zipPath);
            unlink($zipPath);
            die();
        }
        $this->addFlashMessage(
            LocalizationUtility::translate('empty_folder', Configuration::EXTENSION_KEY),
            '',
            FlashMessage::WARNING
        );
        $this->forward(Configuration::INDEX_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
    }

    protected function downloadFolderWithoutZipArchive($zipPath, $filePath, $folder, $rootFolder)
    {
        $files = DownloadUtility::getFilesToAdd(
            $filePath,
            $folder,
            $this->settings[Configuration::START_FOLDER_SETTINGS_KEY],
            ($this->settings[Configuration::RECURSION_SETTINGS_KEY] == ''
                ? false
                : (int)$this->settings[Configuration::RECURSION_SETTINGS_KEY]),
            FilemanagerUtility::calculRecursion($rootFolder, $folder)
        );
        $command = 'cd "' . $filePath . '";';
        $command .= ' zip  "' . $zipPath . '" "' . implode('" "', $files) . '";';
        exec($command, $output);
        if (file_exists($zipPath)) {
            $filesize = filesize($zipPath);
            header('Content-Type: ' . mime_content_type($zipPath));
            header('Content-Transfer-Encoding: Binary');
            header('Content-Length: ' . $filesize);
            header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            if (ob_get_level()) {
                ob_end_clean();
            }
            $handle = fopen($zipPath, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
            unlink($zipPath);
            die();
        }
        $this->addFlashMessage(
            LocalizationUtility::translate('empty_folder', Configuration::EXTENSION_KEY),
            '',
            FlashMessage::WARNING
        );
        $this->forward(Configuration::INDEX_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
    }

    private function getFolderArgument()
    {
        return (int)(
            $this->request->hasArgument(Configuration::FOLDER_ARGUMENT_KEY)
                ? $this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY)
                : 0
        );
    }

    private function getParentFolderArgument()
    {
        return (int)(
            $this->request->hasArgument(Configuration::PARENTFOLDER_ARGUMENT_KEY)
                ? $this->request->getArgument(Configuration::PARENTFOLDER_ARGUMENT_KEY)
                : 0
        );
    }

    private function setFolderDataFromRequest($folder)
    {
        $folder->setStorage((int)$this->settings[Configuration::STORAGE_SETTINGS_KEY]);
        $folder->setDescription($this->request->getArgument(Configuration::DESCRIPTION_ARGUMENT_KEY));
        $folder->setKeywords($this->request->getArgument('keywords'));
        if ($this->request->hasArgument('no_read_access')) {
            $folder->setNoReadAccess((bool)$this->request->getArgument('no_read_access'));
        }
        if ($this->request->hasArgument('no_write_access')) {
            $folder->setNoWriteAccess((bool)$this->request->getArgument('no_write_access'));
        }
        if ($this->request->hasArgument(Configuration::FE_GROUP_READ_ARGUMENT_KEY)) {
            $folder->setArrayFeGroupRead($this->request->getArgument(Configuration::FE_GROUP_READ_ARGUMENT_KEY));
        }
        if ($this->request->hasArgument(Configuration::FE_GROUP_WRITE_ARGUMENT_KEY)) {
            $folder->setArrayFeGroupWrite($this->request->getArgument(Configuration::FE_GROUP_WRITE_ARGUMENT_KEY));
        }
        if ($this->request->hasArgument('fe_group_addfile')) {
            $folder->setArrayFeGroupAddfile($this->request->getArgument('fe_group_addfile'));
        }
        if ($this->request->hasArgument('fe_group_addfolder')) {
            $folder->setArrayFeGroupAddfolder($this->request->getArgument('fe_group_addfolder'));
        }
        if ($this->request->hasArgument('categories')) {
            $folder->setCategories($this->request->getArgument('categories'));
        }
        $folder->setOwnerHasReadAccess(
            (
                isset(
                    $this->settings[Configuration::NEW_FOLDER_SETTINGS_KEY]
                        [Configuration::OWNER_HAS_READ_ACCESS_KEY]
                )
                    ? $this->settings[Configuration::NEW_FOLDER_SETTINGS_KEY]
                        [Configuration::OWNER_HAS_READ_ACCESS_KEY]
                    : 1
            )
        );
        $folder->setOwnerHasWriteAccess(
            (
                isset(
                    $this->settings[Configuration::NEW_FOLDER_SETTINGS_KEY]
                        [Configuration::OWNER_HAS_WRITE_ACCESS_KEY]
                )
                    ? $this->settings[Configuration::NEW_FOLDER_SETTINGS_KEY]
                        [Configuration::OWNER_HAS_WRITE_ACCESS_KEY]
                    : 1
            )
        );
        return $folder;
    }

    private function createFolder($storageFolder, $title)
    {
        try {
            $storageFolder->createFolder($title);
            return $title;
        } catch (\TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException $e) {
            $title = $this->appendSuffixToFolderName($title);
            return $this->createFolder($storageFolder, $title);
        }
    }

    private function renameFolder($storageFolder, $title)
    {
        $newFolderPath = Environment::getPublicPath()
            . '/'
            . preg_replace('/\/$/i', '', $storageFolder->getStorage()->getConfiguration()['basePath'])
            . preg_replace(
                '/\/' . $storageFolder->getName() . '\/$/',
                '/' . $title . '/',
                $storageFolder->getIdentifier()
            );
        if (is_dir($newFolderPath)) {
            $title = $this->appendSuffixToFolderName($title);
            return $this->renameFolder($storageFolder, $title);
        }
        $storageFolder->rename($title);
        return $title;

        return $title;
    }

    private function appendSuffixToFolderName($name)
    {
        if (preg_match('/(\d+)$/i', $name, $matches)) {
            return preg_replace('/\d+$/i', (int)$matches[1] + 1, $name);
        }
        return $name . '_1';
    }
}
