<?php
namespace Ameos\AmeosFilemanager\Controller\Explorer;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;
use Ameos\AmeosFilemanager\Domain\Model\Folder;

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
    * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
    * @inject
    */
    protected $persistenceManager;

    /**
     * edit folder
     */
    protected function editAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        $isNewFolder = $this->request->getArgument('folder') == 'new';
        if ($isNewFolder) {
            $back   = $this->request->getArgument('parentfolder');
            $folder = $this->objectManager->get(Folder::class);
        } else {
            $back   = $this->request->getArgument('folder');
            $folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));
        }

        if ($this->request->getMethod() == 'POST') {
            $hasError = false;
            if (!$this->request->hasArgument('title') || $this->request->getArgument('title') == '') {
                $hasError = true;
                $this->addFlashMessage(LocalizationUtility::translate('titleRequired', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            }

            if (!$hasError) {
                $storage = ResourceFactory::getInstance()->getStorageObject($this->settings['storage']);
                $driver = $this->objectManager->get(LocalDriver::class);

                $title = $driver->sanitizeFileName($this->request->getArgument('title'));
                if ($isNewFolder) {      
                    $parent = $this->folderRepository->findByUid($this->request->getArgument('parentfolder'));
                    
                    $storageFolder = $storage->getFolder($parent->getGedPath() . '/');
                    $storageFolder->createFolder($this->request->getArgument('title'));

                    $folder->setUidParent($parent->getUid());
                    $folder->setIdentifier($parent->getGedPath() . '/' . $title . '/');
                    $folder->setStorage((int)$this->settings['storage']);
                } else {
                    $storageFolder = $storage->getFolder($folder->getGedPath() . '/');
                    $storageFolder->rename($this->request->getArgument('title'));

                    $folder->setIdentifier($folder->getGedPath() . '/');
                    $folder->setStorage((int)$this->settings['storage']);
                }

                $folder->setTitle($title);
                $folder->setDescription($this->request->getArgument('description'));
                $folder->setKeywords($this->request->getArgument('keywords'));                
                $folder->setNoReadAccess($this->request->getArgument('no_read_access') ? true : false);
                $folder->setNoWriteAccess($this->request->getArgument('no_write_access') ? true : false);
                $folder->setArrayFeGroupRead($this->request->getArgument('fe_group_read'));
                $folder->setArrayFeGroupWrite($this->request->getArgument('fe_group_write'));
                $folder->setArrayFeGroupAddfile($this->request->getArgument('fe_group_addfile'));
                $folder->setArrayFeGroupAddfolder($this->request->getArgument('fe_group_addfolder'));
                $folder->setCategories($this->request->getArgument('categories'));
                $folder->setOwnerHasReadAccess((isset($this->settings['newFolder']['owner_has_read_access']) ?
                    $this->settings['newFolder']['owner_has_read_access'] : 1
                ));
                $folder->setOwnerHasWriteAccess((isset($this->settings['newFolder']['owner_has_write_access']) ?
                    $this->settings['newFolder']['owner_has_write_access'] : 1
                ));

                $this->folderRepository->add($folder);
                $this->persistenceManager->persistAll();

                if ($isNewFolder) {
                    $this->addFlashMessage(
                        LocalizationUtility::translate(
                            'folderCreated',
                            'AmeosFilemanager',
                            [$this->request->getArgument('title')]
                        )
                    );
                } else {
                    $this->addFlashMessage(
                        LocalizationUtility::translate(
                            'folderUpdated',
                            'AmeosFilemanager',
                            [$this->request->getArgument('title')]
                        )
                    );
                }
                
                $this->redirect('index', 'Explorer\\Explorer', null, ['folder' => $folder->getUid()]);
            }
        }

        $this->view->assign('folder', $folder);
        $this->view->assign('back',   $back);
        $this->view->assign('parent', $this->request->hasArgument('parentfolder') ? $this->request->getArgument('parentfolder') : 0);
        $this->view->assign('usergroups', $this->getAvailableUsergroups());
        $this->view->assign('categories', $this->getAvailableCategories());
    }

    /**
     * download folder as zip
     */
    protected function downloadAction()
    {        
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        if (!$this->request->hasArgument('folder') || (int)$this->request->getArgument('folder') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFolderArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        $rootFolder = $this->folderRepository->findByUid((int)$this->settings['startFolder']);
        $folder = $this->folderRepository->findByUid((int)$this->request->getArgument('folder'));

        if (!$folder || !$folder->isChildOf((int)$this->settings['startFolder'])) {
            $this->addFlashMessage(LocalizationUtility::translate('accessDenied', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        $storage = ResourceFactory::getInstance()->getStorageObject($this->settings['storage']);

        $zipPath  = PATH_site . 'typo3temp/' . $folder->getTitle() . '_' . date('dmY_His') . '.zip';
        $filePath = PATH_site . trim($storage->getConfiguration()['basePath'], '/') . $folder->getGedPath();

        $configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);
        if ($configuration['use_ziparchive']) {
            if (!class_exists('ZipArchive')) {
                throw new \Exception('ZipArchive is not installed on your server : see http://php.net/ZipArchive');
            }
        
            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE);
            DownloadUtility::addFolderToZip(
                $filePath,
                $folder,
                $zip,
                $this->settings['startFolder'],
                ($this->settings['recursion'] == '' ? false : (int)$this->settings['recursion']),
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
            } else {
                $this->addFlashMessage(LocalizationUtility::translate('empty_folder', 'ameos_filemanager'), '', FlashMessage::WARNING);
                $this->forward('index', 'Explorer');
            }
        } else {
            $files = DownloadUtility::getFilesToAdd(
                $filePath,
                $folder,
                $zip,
                $this->settings['startFolder'],
                ($this->settings['recursion'] == '' ? false : (int)$this->settings['recursion']),
                FilemanagerUtility::calculRecursion($rootFolder, $folder)
            );
            $command = 'cd "' . $filePath . '"; zip  "' . $zipPath . '" "' . implode('" "', $files) . '";';
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
                $handle = fopen($zipPath, "rb");
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                }
                fclose($handle);
                unlink($zipPath);
                die();

            } else {
                $this->addFlashMessage(LocalizationUtility::translate('empty_folder', 'ameos_filemanager'), '', FlashMessage::WARNING);
                $this->forward('index', 'Explorer\\Explorer');
            }
        }
    }

    /**
     * Delete the folder
     */
    protected function removeAction()
    {
        if (!$this->request->hasArgument('folder') || (int)$this->request->getArgument('folder') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFolderArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }

        $folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));
        $parentFolder = $folder->getParent();

        FolderUtility::remove(
            $this->request->getArgument('folder'),
            $this->settings['storage'],
            $this->settings['startFolder']
        );

        $this->addFlashMessage(LocalizationUtility::translate('folderRemoved', 'AmeosFilemanager'));
        $this->redirect('index', 'Explorer\\Explorer', null, ['folder' => $parentFolder->getUid()]);
    }

    /**
     * info folder
     */
    protected function infoAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors', 'Explorer\\Explorer');
        }

        if (!$this->request->hasArgument('folder') || (int)$this->request->getArgument('folder') === 0) {
            $this->addFlashMessage(LocalizationUtility::translate('missingFolderArgument', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors', 'Explorer\\Explorer');
        }
        
        $folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));
        $this->view->assign('folder', $folder);
    }
}
