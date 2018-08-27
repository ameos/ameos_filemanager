<?php
namespace Ameos\AmeosFilemanager\Controller\Explorer;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\ExplorerUtility;

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
 
class ExplorerController extends AbstractController
{
    /**
     * initialize action
     */
    protected function initializeAction()
    {
        $pageRenderer = $this->objectManager->get(PageRenderer::class);
        $pageRenderer->addJsFooterFile('EXT:ameos_filemanager/Resources/Public/JavaScript/Explorer.js');
    }

    /**
     * Main explorer action
     */
    protected function indexAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors');
        }

        // get configuration
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($configuration['view']['pluginNamespace'])) {
            $configuration['view']['pluginNamespace'] = 'tx_ameosfilemanager_fe_filemanager_explorer';
        }

        // get folders (root and current)
        $currentFolderidentifier = $this->request->hasArgument('folder') ? $this->request->getArgument('folder') : $this->settings['startFolder'];
        $currentFolder = $this->folderRepository->findByUid($currentFolderidentifier);
        $rootFolder = $this->folderRepository->findByUid($this->settings['startFolder']);

        // check if current folder is a child of root folder
        if (!$currentFolder || !$currentFolder->isChildOf($rootFolder->getUid())) {
            $this->addFlashMessage(LocalizationUtility::translate('accessDenied', 'AmeosFilemanager'), '', FlashMessage::ERROR);            
            $this->forward('errors');
        }

        // check recursion
        if (FilemanagerUtility::hasTooMuchRecursion($rootFolder, $folder, $this->settings['recursion'])) {
            $this->addFlashMessage(LocalizationUtility::translate('tooMuchRecursion', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors');
        }

        // parse folder if needed
        if ($this->settings['parseFolderInFE']) {
            FilemanagerUtility::parseFolderForNewElements(
                $this->settings['storage'],
                $currentFolder->getGedPath(),
                $currentFolder->getTitle()
            );
        }

        $availableMode = is_array($this->settings['availableMode']) ? $this->settings['availableMode'] : GeneralUtility::trimExplode(',', $this->settings['availableMode']);
        if (is_array($this->settings['availableMode'])) {
            $this->settings['availableMode'] = implode(',', $this->settings['availableMode']);
        }
        
        // get current display mode
        $displayMode = ExplorerUtility::getDisplayMode($availableMode, $this->configurationManager->getContentObject()->data['uid']);

        // assign data to the view
        $this->view->assign('root_folder', $rootFolder);
        $this->view->assign('current_folder', $currentFolder);
        $this->view->assign('files', $this->fileRepository->findFilesForFolder($currentFolder->getUid(), $configuration['view']['pluginNamespace']));
        $this->view->assign('has_many_display_mode', (count($availableMode) > 1));
        $this->view->assign('display_mode', $displayMode);        
        $this->view->assign('columns_table', GeneralUtility::trimExplode(',', $this->settings['columnsTable']));
        $this->view->assign('allowed_actions_files', GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFiles']));
        $this->view->assign('allowed_actions_folders', GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFolders']));
        $this->view->assign('massactions', ['' => '',
            'move'   => LocalizationUtility::translate('massaction.move', 'AmeosFilemanager'),
            'copy'   => LocalizationUtility::translate('massaction.copy', 'AmeosFilemanager'),
            'remove' => LocalizationUtility::translate('massaction.remove', 'AmeosFilemanager'),
        ]);
        $this->view->assign('folders_options', ExplorerUtility::getFolderOptionTree([$rootFolder]));
    }

    /**
     * search action
     */
    protected function searchAction()
    {
        if (!$this->settingsIsValid()) {
            $this->forward('errors');
        }

        if (!$this->request->hasArgument('query') || $this->request->getArgument('query') == '') {
            $this->forward('index');
        }

        // get configuration
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($configuration['view']['pluginNamespace'])) {
            $configuration['view']['pluginNamespace'] = 'tx_ameosfilemanager_fe_filemanager_explorer';
        }

        // get folders (root and current)
        $currentFolderidentifier = $this->request->hasArgument('folder') ? $this->request->getArgument('folder') : $this->settings['startFolder'];
        $currentFolder = $this->folderRepository->findByUid($currentFolderidentifier);
        $rootFolder = $this->folderRepository->findByUid($this->settings['startFolder']);

        // check if current folder is a child of root folder
        if (!$currentFolder || !$currentFolder->isChildOf($rootFolder->getUid())) {
            $this->addFlashMessage(LocalizationUtility::translate('accessDenied', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors');
        }

        // check recursion
        if (FilemanagerUtility::hasTooMuchRecursion($rootFolder, $folder, $this->settings['recursion'])) {
            $this->addFlashMessage(LocalizationUtility::translate('tooMuchRecursion', 'AmeosFilemanager'), '', FlashMessage::ERROR);
            $this->forward('errors');
        }

        // parse folder if needed
        if ($this->settings['parseFolderInFE']) {
            FilemanagerUtility::parseFolderForNewElements(
                $this->settings['storage'],
                $currentFolder->getGedPath(),
                $currentFolder->getTitle()
            );
        }

        // get current display mode
        $displayMode = ExplorerUtility::getDisplayMode(
            $this->settings['availableMode'],
            $this->configurationManager->getContentObject()->data['uid']
        );

        $this->settings['displayFolders'] = 0;
        $this->settings['recursion'] = $this->settings['recursion'] == '' ? PHP_INT_MAX : $this->settings['recursion'];

        // assign data to the view
        $this->view->assign('settings', $this->settings);
        $this->view->assign('current_query', $this->request->getArgument('query'));
        $this->view->assign('root_folder', $rootFolder);
        $this->view->assign('current_folder', $currentFolder);        
        $this->view->assign('has_many_display_mode', (strpos($this->settings['availableMode'], ',') !== false));
        $this->view->assign('display_mode', $displayMode);        
        $this->view->assign('columns_table', GeneralUtility::trimExplode(',', $this->settings['columnsTable']));
        $this->view->assign('allowed_actions_files', GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFiles']));
        $this->view->assign('allowed_actions_folders', GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFolders']));
        $this->view->assign('files', $this->fileRepository->findBySearchCriterias(
            ['keyword' => $this->request->getArgument('query')],
            $currentFolderidentifier,
            $configuration['view']['pluginNamespace'],
            $this->settings['recursion']
        ));
    }

    /**
     * Update display mode action
     */
    protected function updateDisplayModeAction()
    {
        if ($this->request->hasArgument('displaymode')) {
            ExplorerUtility::updateDisplayMode(
                $this->configurationManager->getContentObject()->data['uid'],
                $this->request->getArgument('displaymode')
            );
        }
        $this->redirect('index', null, null, ['folder' => $this->request->getArgument('folder')]);
    }

    /**
     * Display errors (flash messages)
     */
    protected function errorsAction()
    {
        
    }
}
