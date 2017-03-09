<?php
namespace Ameos\AmeosFilemanager\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;

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
 
class FileManagerController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository
     * @inject
     */
    protected $folderRepository;

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository;

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository
     * @inject
     */
    protected $filedownloadRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository
     * @inject
     */
    protected $feGroupRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $feUserRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
     * @inject
     */
    protected $beUserRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
     * @inject
     */
    protected $categoryRepository;

    /**
     * Handles the path resolving for *rootPath(s)
     *
     * numerical arrays get ordered by key ascending
     *
     * @param array $extbaseFrameworkConfiguration
     * @param string $setting parameter name from TypoScript
     *
     * @return array
     */
    protected function getViewProperty($extbaseFrameworkConfiguration, $setting)
    {
        $values = [];
        if (
            !empty($extbaseFrameworkConfiguration['view'][$setting])
            && is_array($extbaseFrameworkConfiguration['view'][$setting])
        ) {
            $values = ArrayUtility::sortArrayWithIntegerKeys($extbaseFrameworkConfiguration['view'][$setting]);
            $values = array_reverse($values, true);
        } elseif (
            !empty($extbaseFrameworkConfiguration['view'][$setting])
            && is_string($extbaseFrameworkConfiguration['view'][$setting])
        ) {
            $values = [$extbaseFrameworkConfiguration['view'][$setting]];
        }
        return $values;
    }
    
    /**
     * Initialization of all actions.
     * Check if the plugin is correctly configured and set the basic variables.
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->user = ($GLOBALS['TSFE']->fe_user->user);

        if ($this->settings['startFolder'] != '') {
            $this->startFolder = $this->settings['startFolder'];
        } elseif ($this->request->hasArgument('rootFolder')) {
            $this->settings['startFolder'] = $this->request->getArgument('rootFolder');
            $this->startFolder = $this->settings['startFolder'];
        } else {
            throw new \Exception('The root folder was not configured. Please add it in plugin configuration.');
        }
        // Setting feUser Repository
        if ($this->settings['stockageGroupPid'] != '') {
            $querySettings = $this->feGroupRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds(array($this->settings['stockageGroupPid']));
            $this->feGroupRepository->setDefaultQuerySettings($querySettings);
        } else {
            throw new \Exception('The user folder was not configured. Please add it in plugin configuration.');
        }
        // Setting storage folder, return error if not set or not found.
        if($this->settings['storage']) {
            $this->storageUid = $this->settings['storage'];
        } else {
            throw new \Exception('The storage folder was not configured. Please add it in plugin configuration.');
        }
        $storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
        $this->storage = $storageRepository->findByUid($this->storageUid);
        if ($this->storage == null) {
            throw new \Exception('Storage folder not found. Please check configuration');
        }
        // Setting list of usergroups to send to form actions
        if ($this->settings['authorizedGroups']) {
            $this->authorizedGroups = $this->settings['authorizedGroups'];
        }
        // Setting list of categories to send to form actions
        if ($this->settings['authorizedCategories']) {
            $this->authorizedCategories = $this->settings['authorizedCategories'];
        }
    }

    /**
     * Download file if file uid is set
     * Display the files/folders of the current folder otherwise
     *
     * @return void
     */
    protected function indexAction()
    {
        if ($this->request->getMethod() == 'POST') {
            if ($this->request->hasArgument('keyword') && $this->request->getArgument('keyword') != '') {
                $this->redirect('list', null, null, ['keyword' => $this->request->getArgument('keyword')]);
            }
        }
        
        $contentUid = $this->configurationManager->getContentObject()->data['uid'];
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($configuration['view']['pluginNamespace'])) {
            $configuration['view']['pluginNamespace'] = 'tx_ameosfilemanager_fe_filemanager';
        }
        
        $args = $this->request->getArguments();
        if ($args['file']) {
            DownloadUtility::downloadFile($args['file'], $this->settings['startFolder']);
        }
        $startFolder = $args['folder'] ?: $this->settings['startFolder'];
        $rootFolder = $this->folderRepository->findByUid($this->settings['startFolder']);
        $folder = $this->folderRepository->findByUid($startFolder);
        if (!$folder || !$folder->isChildOf($this->startFolder)) {
            return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
        }

        if (FilemanagerUtility::hasTooMuchRecursion($rootFolder, $folder, $this->settings['recursion'])) {
            return LocalizationUtility::translate('tooMuchRecursion', 'ameos_filemanager');
        }
        
        if ($this->settings['parseFolderInFE']) {
            FilemanagerUtility::parseFolderForNewElements($this->storage, $folder->getGedPath(), $folder->getTitle());
        }
        $this->settings['columnsTable'] = explode(',', $this->settings['columnsTable']);
        $this->settings['actionDetail'] = explode(',', $this->settings['actionDetail']);
        $this->view->assign('settings', $this->settings);
        $this->view->assign('folder', $folder);
        $this->view->assign('is_last_recursion', FilemanagerUtility::isTheLastRecursion($rootFolder, $folder, $this->settings['recursion']));
        $this->view->assign('files', $this->fileRepository->findFilesForFolder($startFolder, $configuration['view']['pluginNamespace']));
        $this->view->assign('content_uid', $contentUid);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
            && $contentUid == GeneralUtility::_POST('ameos_filemanager_content')) {
            header('Content-Type: text/json; charset=utf8;');
            echo json_encode(['html' => $this->view->render()]);
            die();
        }
    }

    /**
     * mass download action
     */
    protected function massDownloadAction()
    {
        $rootFolder = $this->folderRepository->findByUid((int)$this->settings['startFolder']);
        $folderId = $this->request->hasArgument('folder') ? $this->request->getArgument('folder') : $this->settings['startFolder'];
        $folder = $this->folderRepository->findByUid((int)$folderId);
        if (!$folder || !$folder->isChildOf($this->startFolder)) {
            return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
        }

        $zipPath  = PATH_site . 'typo3temp/' . $folder->getTitle() . '_' . uniqid() . '.zip';
        $filePath = PATH_site . 'fileadmin' . $folder->getGedPath();

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
                (bool)$this->settings['displayArchive'],
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
                $this->redirect('index');
            }
        } else {
            $files = DownloadUtility::getFilesToAdd(
                $filePath,
                $folder,
                $zip,
                $this->settings['startFolder'],
                (bool)$this->settings['displayArchive'],
                ($this->settings['recursion'] == '' ? false : (int)$this->settings['recursion']),
                FilemanagerUtility::calculRecursion($rootFolder, $folder)
            );
            $command = 'cd ' . $filePath . '; zip  ' . $zipPath . ' ' . implode(' ', $files) . ';';
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
                $this->redirect('index');
            }
        }
    }

    /**
     * File form
     *
     * @return void
     */
    protected function formFileAction()
    {
        $args = $this->request->getArguments();
        $folder = $args['folder'] ?: $this->settings['startFolder'];
        $editFileUid = $args['newFile'];
        if ($editFileUid != '' && $newFile = $this->fileRepository->findByUid($editFileUid)) {
            if (!AccessUtility::userHasFileWriteAccess($this->user, $newFile, array('folderRoot' => $this->settings['startFolder']))) {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
            $metaDataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
            $meta = $metaDataRepository->findByFileUid($newFile->getUid());

            $fileArgs = array();
            $fileArgs['title'] = $meta['title'];
            $fileArgs['arrayFeGroupRead'] = explode(',', $meta['fe_group_read']);
            $fileArgs['arrayFeGroupWrite'] = explode(',', $meta['fe_group_write']);
            $fileArgs['description'] = $meta['description'];
            $fileArgs['keywords'] = $meta['keywords'];
            $fileArgs['noReadAccess'] = $meta['no_read_access'];
            $fileArgs['noWriteAccess'] = $meta['no_write_access'];

            $this->view->assign('properties', $fileArgs);
            $this->view->assign('file', $newFile);
            $this->view->assign('parentFolder', $newFile->getParentFolder()->getUid());
            $this->view->assign('uidFile', $newFile->getUid());
        } else {
            if (!AccessUtility::userHasAddFileAccess($this->user, $this->folderRepository->findByUid($folder))) {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
            $this->view->assign('parentFolder', $folder);
        }
        // Setting userGroup list
        if ($this->authorizedGroups!='') {
            $feGroup = FilemanagerUtility::getByUids($this->feGroupRepository, $this->authorizedGroups)->toArray();
            if (GeneralUtility::inList($this->authorizedGroups,-2)) {
                $temp = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
                $temp->_setProperty('uid', -2);
                $temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
                $feGroup[] = $temp;    
            }
        } else {
            $feGroup = $this->feGroupRepository->findAll()->toArray();
            $temp = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
            $temp->_setProperty('uid', -2);
            $temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
            $feGroup[] = $temp;
        }
        // Setting category list
        if ($this->authorizedCategories != '') {
            $categorieUids = explode(',', $this->authorizedCategories);
            $categories = FilemanagerUtility::getByUids($this->categoryRepository,$this->authorizedCategories);
        } else {
            $categories = $this->categoryRepository->findAll();
        }
        // if errors, display them.
        if ($args['errors']) {
            $this->view->assign('errors',$args['errors']);
            $this->view->assign('properties',$args['properties']);
        }
        $this->view->assign('feGroup', $feGroup);
        $this->view->assign('categories', $categories);

        $controllerBack = $this->request->getPluginName() == 'fe_filemanager_flat' ? 'FlatList' : 'FileManager';
        $this->view->assign('controller_back', $controllerBack);
    }

    /**
     * Creates or update a file then redirect to the parent directory
     *
     * @return void
     */
    protected function createFileAction()
    {
        // Check if request is POST / only logged in user can upload files
        if ($this->request->getMethod() != 'POST' || !$this->user){
            return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
        }
        $fileArgs = $this->request->getArguments();
        $folder = $this->folderRepository->findByUid($fileArgs['uidParent']);
        $allowedFileExtension = explode(',', $this->settings["allowedFileExtension"]);
        $storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
        $storage = $storageRepository->findByUid($this->storageUid);
        $properties = array();
        $errors = array();
        //if an uid is sent, we update an existing file, if not we create a new one.
        if ($fileArgs['uidFile'] != '') {
            $fileModel = $this->fileRepository->findByUid($fileArgs['uidFile']);
            if (!AccessUtility::userHasFileWriteAccess($this->user, $fileModel,array('folderRoot' => $this->settings['startFolder']))) {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
            if ($fileArgs['file']['tmp_name'] != '') {
                if (file_exists($storage->getConfiguration()['basePath'].$fileModel->getParentFolder()->getGedPath().'/'.$fileArgs['file']['name'])) {
                    $errors['file'] = LocalizationUtility::translate('fileAlreadyExist', 'ameos_filemanager');    
                } elseif (!in_array(pathinfo($fileArgs['file']['name'], PATHINFO_EXTENSION), $allowedFileExtension)) {
                    $errors['file'] = LocalizationUtility::translate('fileUploadError', 'ameos_filemanager');
                } elseif (!move_uploaded_file($fileArgs['file']['tmp_name'], $storage->getConfiguration()['basePath'].$folder->getGedPath().'/'.$fileArgs['file']['name'])) {
                    $errors['file'] = LocalizationUtility::translate('fileUploadError', 'ameos_filemanager');
                } else {
                    $someFileIdentifier = $folder->getGedPath().'/'.$fileArgs['file']['name']; 
                    $storage->replaceFile($fileModel->getOriginalResource(),'fileadmin/'.$someFileIdentifier);
                    $storage->renameFile($fileModel->getOriginalResource(), $fileArgs['file']['name']);    
                }
            }
        } else {
            if (!AccessUtility::userHasAddFileAccess($this->user, $folder,array('folderRoot' => $this->settings['startFolder']))) {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
            $properties['fe_user_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
            if ($fileArgs['file']['tmp_name'] == '') {
                $errors['file'] = LocalizationUtility::translate('fileNotUploaded', 'ameos_filemanager');
            } elseif (!in_array(pathinfo($fileArgs['file']['name'], PATHINFO_EXTENSION), $allowedFileExtension)) {
                $errors['file'] = LocalizationUtility::translate('fileUploadError', 'ameos_filemanager');
            } elseif (file_exists($storage->getConfiguration()['basePath'].$folder->getGedPath().'/'.$fileArgs['file']['name'])) {
                $errors['file'] = LocalizationUtility::translate('fileAlreadyExist', 'ameos_filemanager');
            } elseif (!move_uploaded_file($fileArgs['file']['tmp_name'], $storage->getConfiguration()['basePath'].$folder->getGedPath().'/'.$fileArgs['file']['name'])) {
                $errors['file'] = LocalizationUtility::translate('fileUploadError', 'ameos_filemanager');
            }
            $someFileIdentifier = $folder->getGedPath().'/'.$fileArgs['file']['name'];
            $fileObj = $storage->getFile($someFileIdentifier);
        }

        // If errors, redirect to form with array erros.
        if (!empty($errors)) {
            $resultUri = $this->uriBuilder
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->setArguments(array('tx_ameos_filemanager' => array('newFile' => $fileArgs['uidFile'], 'errors' => $errors,'folder' => $fileArgs['uidParent'], 'properties' => $fileArgs)))
                ->uriFor('formFile');

            $this->redirectToUri($resultUri);
        } else {
            $persitenceManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
            $persitenceManager->persistAll();
            if(!isset($fileModel)) {
                $fileModel = $this->fileRepository->findByUid($fileObj->getUid());
            }
        }

        // Setting metadatas
        if ($fileArgs['title']) {             $properties['title'] = $fileArgs['title']; }
        if ($fileArgs['arrayFeGroupRead']) {  $properties['fe_group_read'] = implode(',', $fileArgs['arrayFeGroupRead']); }
        if ($fileArgs['arrayFeGroupWrite']) { $properties['fe_group_write'] = implode(',', $fileArgs['arrayFeGroupWrite']); }
        if ($fileArgs['description']) {       $properties['description'] = $fileArgs['description']; }
        if ($fileArgs['keywords']) {          $properties['keywords'] = $fileArgs['keywords']; }
        if ($fileArgs['noReadAccess']) {
            $properties['no_read_access'] = $fileArgs['noReadAccess'];
        } else {
            $properties['no_read_access'] = 0;
        }
        if($fileArgs['noWriteAccess']) {
            $properties['no_write_access'] = $fileArgs['noWriteAccess'];
        } else {
            $properties['no_write_access'] = 0;
        }
        $properties['owner_has_read_access']  = isset($this->settings['newFile']['owner_has_read_access'])  ? $this->settings['newFile']['owner_has_read_access']  : 1;
        $properties['owner_has_write_access'] = isset($this->settings['newFile']['owner_has_write_access']) ? $this->settings['newFile']['owner_has_write_access'] : 1;

        if ($fileArgs['uidFile'] != '') {
            $metaDataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
                $metaDataRepository->update($fileModel->getUid(),$properties);
                if ($fileArgs['categories']) {
                    $fileModel->setCategories($fileArgs['categories']);
                }
        } else {
            $properties['folder_uid'] = $folder->getUid();
            $metaDataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
            $metaDataRepository->update($fileObj->getUid(),$properties);
            $persitenceManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
            $persitenceManager->persistAll();
            
            if (!isset($fileModel)) {
                $fileModel = $this->fileRepository->findByUid($fileObj->getUid());
            }
            if ($fileArgs['categories']) {
                $fileModel->setCategories($fileArgs['categories']);
            }
        }
        
        $resultUri = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setArguments(array('tx_ameos_filemanager' => array('folder' => $folder)))
            ->uriFor('index');
        
        $this->redirectToUri($resultUri);
    }

    /**
     * Folder form
     *
     * @return void
     */
    protected function formFolderAction()
    {
        $args = $this->request->getArguments();
        $editFolderUid = $args['newFolder'];
        
        // We are editing a folder
        if ($editFolderUid != '') {
            if ($newFolder = $this->folderRepository->findByUid($editFolderUid,$writeRight=true)) {
                $this->view->assign('folder',$newFolder);
                if ($newFolder->getParent()){
                    $this->view->assign('parentFolder',$newFolder->getParent()->getUid());
                } else {
                    return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
                }
            } else {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
        } else { // We are creating a folder
            $folderUid = $args['folder'] ?: $this->settings['startFolder'];
            if ($folderParent = $this->folderRepository->findByUid($folderUid ,$writeRight=true)) {
                $this->view->assign('parentFolder',$folderParent->getUid());
            } else {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
        }

        if ($this->authorizedCategories != '') {
            $categorieUids = explode(',', $this->authorizedCategories);
            $categories = FilemanagerUtility::getByUids($this->categoryRepository,$this->authorizedCategories);
        } else {
            $categories = $this->categoryRepository->findAll();
        }

        if ($this->authorizedGroups!='') {
            $feGroup = FilemanagerUtility::getByUids($this->feGroupRepository,$this->authorizedGroups)->toArray();
            if (GeneralUtility::inList($this->authorizedGroups,-2)) {
                $temp = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
                $temp->_setProperty('uid',-2);
                $temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
                $feGroup[] = $temp;    
            }
        } else {
            $feGroup = $this->feGroupRepository->findAll()->toArray();
            $temp = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
            $temp->_setProperty('uid',-2);
            $temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
            $feGroup[] = $temp;
        }

        if ($args['errors']) {
            $this->view->assign('errors',$args['errors']);
            $this->view->assign('folder',$args['currentState']);
            $this->view->assign('parentFolder',$args['currentState']['uidParent']);
        }
        
        $this->view->assign('categories',$categories);
        $this->view->assign('feGroup',$feGroup);
        $this->view->assign('returnFolder', $args['returnfolder']);

    }

    /**
     * Creates or update a folder then redirect to the parent directory
     *
     * @return void
     */
    protected function createFolderAction()
    {
        // Check if request is POST / only logged in user can upload files
        if ($this->request->getMethod() != 'POST' || !$this->user){
            return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
        }
        $fileArgs = $this->request->getArguments();
        $parent = $this->folderRepository->findByUid($fileArgs['uidParent']);
        $errors = array();

        // No uid so we are in create mode
        if ($fileArgs['uidFolder'] == '') {
            if (!AccessUtility::userHasAddFolderAccess($this->user, $parent,array('folderRoot' => $this->settings['startFolder']))) {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
            $newFolder = GeneralUtility::makeInstance('Ameos\\AmeosFilemanager\\Domain\\Model\\Folder');
            $newFolder->setFeUser($GLOBALS['TSFE']->fe_user->user['uid']);
            // Needed if an error is detected.
            $fileArgs['feUser'] = $GLOBALS['TSFE']->fe_user->user['uid'];
            if ($parent->hasFolder($newFolder->getTitle())) {
                $errors['title'] = "Folder already exists";
            }
        } else { // edit mode
            $exFolderQuery = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow("*", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.uid = ".$fileArgs['uidFolder'] );
            
            //checking if user had the right to update this folder BEFORE the edition.
            $newFolder = $this->folderRepository->findByUid($fileArgs['uidFolder']);
            if (!AccessUtility::userHasFolderWriteAccess($this->user, $newFolder,array('folderRoot' => $this->settings['startFolder']))) {
                return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
            }
            if ($parent->hasFolder($newFolder->getTitle(),$newFolder->getUid())) {
                $errors['title'] = "Folder already exists";
            }
        }
        
        if (empty($fileArgs['title'])) {
            $errors['title'] = 'Folder title cannot be empty';
        }

        if (!empty($errors)) {
            $resultUri = $this->uriBuilder
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->setArguments(['tx_ameos_filemanager' => [
                    'newFolder'    => $fileArgs['uidFile'],
                    'errors'       => $errors,
                    'folder'       => $fileArgs['returnFolder'],
                    'currentState' => $fileArgs
                ]])->uriFor('formFolder');
            
            $this->redirectToUri($resultUri);
        }

        $storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
        $storage = $storageRepository->findByUid($this->storageUid);
        $localDriver = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver');

        // Editing folder
        $newFolder->setTitle($localDriver->sanitizeFileName($fileArgs['title']));
        $newFolder->setDescription($fileArgs['description']);
        $newFolder->setKeywords($fileArgs['keywords']);
        $newFolder->setNoReadAccess($fileArgs['noReadAccess']);
        $newFolder->setNoWriteAccess($fileArgs['noWriteAccess']);
        $newFolder->setArrayFeGroupRead($fileArgs['arrayFeGroupRead']);
        $newFolder->setArrayFeGroupWrite($fileArgs['arrayFeGroupWrite']);
        $newFolder->setArrayFeGroupAddfile($fileArgs['arrayFeGroupAddfile']);
        $newFolder->setArrayFeGroupAddfolder($fileArgs['arrayFeGroupAddfolder']);
        $newFolder->setCategories($fileArgs['categories']);
        $newFolder->setUidParent($parent);
        $newFolder->setOwnerHasReadAccess((isset($this->settings['newFolder']['owner_has_read_access']) ? $this->settings['newFolder']['owner_has_read_access'] : 1));
        $newFolder->setOwnerHasWriteAccess((isset($this->settings['newFolder']['owner_has_write_access']) ? $this->settings['newFolder']['owner_has_write_access'] : 1));
        
        $this->folderRepository->add($newFolder);

        if ($fileArgs['uidFolder'] != '') {
            $storageFolder = $storage->getFolder($newFolder->getParent()->getGedPath().'/'.$exFolderQuery['title'].'/');
            $storageFolder->rename($newFolder->getTitle());

            $returnfolder = $fileArgs['returnFolder'] != '' ? $fileArgs['returnFolder'] : $fileArgs['uidFolder'];
        } else {
            $storageFolder = $storage->getFolder($parent->getGedPath().'/');
            $storageFolder->createFolder($newFolder->getTitle());

            $returnfolder = $fileArgs['returnFolder'] != '' ? $fileArgs['returnFolder'] : $parent->getUid();
        }
        
        $this->redirect('index', null, null, ['folder' => $returnfolder]);
    }

    /**
     * Display a list of files matching the given arguments
     *
     * @return void
     */
    protected function listAction()
    {
        $contentUid = $this->configurationManager->getContentObject()->data['uid'];
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($configuration['view']['pluginNamespace'])) {
            $configuration['view']['pluginNamespace'] = 'tx_ameosfilemanager_fe_filemanager';
        }

        $this->settings['columnsTable'] = explode(',', $this->settings['columnsTable']);
        $this->settings['actionDetail'] = explode(',', $this->settings['actionDetail']);
        $this->view->assign('settings', $this->settings);

        $args = $this->request->getArguments();
        $t = $this->fileRepository->findBySearchCriterias($args, $this->settings['startFolder'], $configuration['view']['pluginNamespace'], $this->settings['recursion']);
        $this->view->assign('files', $t);
        $this->view->assign('value', $args);        
        $this->view->assign('content_uid', $contentUid);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
            && $contentUid == GeneralUtility::_POST('ameos_filemanager_content')) {
            header('Content-Type: text/json; charset=utf8;');
            echo json_encode(['html' => $this->view->render()]);
            die();
        }
    }

    /**
     * Details of a file
     *
     * @return void
     */
    protected function detailAction()
    {
        if ($this->request->hasArgument('file')) {
            $file = $this->fileRepository->findByUid((int)$this->request->getArgument('file'));
            if ($file) {
                $this->view->assign('file', $file);    
            } else {
                $this->view->assign('error', LocalizationUtility::translate('fileNotFound', 'ameos_filemanager'));
            }
            
        } else {
            $this->view->assign('error', LocalizationUtility::translate('fileNotFound', 'ameos_filemanager'));
        }
        $controllerBack = $this->request->getPluginName() == 'fe_filemanager_flat' ? 'FlatList' : 'FileManager';
        $this->view->assign('controller_back', $controllerBack);
    }


    /**
     * Delete the folder given in arguments
     *
     * @return void
     */
    protected function deleteFolderAction()
    {
        if ($this->request->hasArgument('folder')) {
            $folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));
            if ($folder && AccessUtility::userHasFolderWriteAccess($this->user, $folder, array('folderRoot' => $this->startFolder))) {
                if ($folder->getGedPath()) {
                    $ebFolder = $this->storage->getFolder($folder->getGedPath());
                    $this->storage->deleteFolder($ebFolder);
                    $this->folderRepository->remove($folder);
                }
            }
        }
        $resultUri = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setArguments(array('tx_ameos_filemanager' => array('folder' => $folder->getParent())))
            ->uriFor('index');
        
        $this->redirectToUri($resultUri);
    }

    /**
     * Delete the file given in arguments
     *
     * @return void
     */
    protected function deleteFileAction()
    {
        if ($this->request->hasArgument('file')) {
            $file = $this->fileRepository->findByUid($this->request->getArgument('file'));
            if ($file && AccessUtility::userHasFileWriteAccess($this->user, $file, array('folderRoot' => $this->startFolder))) {
                $this->storage->deleteFile($file->getOriginalResource());
            }
        }
        $resultUri = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setArguments(array('tx_ameos_filemanager' => array('folder' => $file->getParentFolder())))
            ->uriFor('index');
        
        $this->redirectToUri($resultUri);
    }
}
