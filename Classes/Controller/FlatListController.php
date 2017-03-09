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
 
class FlatListController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
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
        // Check recursion
        $this->settings['recursion'] = trim($this->settings['recursion']) == '' ? false : (int)$this->settings['recursion'];
    }

    /**
     * index action
     *
     * @return void
     */
    protected function indexAction()
    {
        $contentUid = $this->configurationManager->getContentObject()->data['uid'];
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($configuration['view']['pluginNamespace'])) {
            $configuration['view']['pluginNamespace'] = 'tx_ameosfilemanager_fe_filemanager';
        }

        $args = $this->request->getArguments();
        if ($args['file']) {
            DownloadUtility::downloadFile($args['file'], $this->settings['startFolder']);
        }

        $folder = $this->folderRepository->findByUid($this->settings['startFolder']);
        $foldersIdentifiers = $this->getSubFolderIdentifiersRecursively($folder, $this->settings['recursion']);
        
        $this->settings['columnsTable'] = explode(',', $this->settings['columnsTable']);
        $this->settings['actionDetail'] = explode(',', $this->settings['actionDetail']);

        $this->view->assign('settings', $this->settings);
        $this->view->assign('folder',   $folder);
        $this->view->assign('files',    $this->fileRepository->findFilesForFolder($foldersIdentifiers, $configuration['view']['pluginNamespace']));
    }

    /**
     * return sub folders identifiers 
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param int $limitRecursion
     * @param int $currentRecursion
     * @return array
     */ 
    protected function getSubFolderIdentifiersRecursively($folder, $limitRecursion = false, $currentRecursion = 1)
    {
        $identifiers = [];
        $identifiers[$folder->getUid()] = $folder->getUid();
        if ($currentRecursion <= $limitRecursion || $limitRecursion === false) {
            foreach ($folder->getFolders() as $subFolder) {
                if ($this->settings['parseFolderInFE']) {
                    FilemanagerUtility::parseFolderForNewElements($this->storage, $folder->getGedPath(), $folder->getTitle());
                }
                $identifiers[$subFolder->getUid()] = $subFolder->getUid();
                $currentRecursion++;
                $identifiers = array_merge($identifiers, $this->getSubFolderIdentifiersRecursively($subFolder, $limitRecursion, $currentRecursion));
            }    
        }
        return $identifiers;
    }
}
