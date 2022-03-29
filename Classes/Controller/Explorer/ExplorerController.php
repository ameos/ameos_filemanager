<?php

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Utility\ExplorerUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
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

class ExplorerController extends AbstractController
{
    /**
     * Current folder identifier
     *
     * @var string
     */
    protected $currentFolderidentifier;

    /**
     * Current folder
     *
     * @var Folder
     */
    protected $currentFolder;

    /**
     * Root folder
     *
     * @var Folder
     */
    protected $rootFolder;

    /**
     * Main explorer action
     */
    protected function indexAction()
    {
        $configuration = $this->getPluginConfiguration();

        $this->fetchRootAndCurrentFolders();

        $this->parseFolderInFe();

        $availableMode = is_array($this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY])
            ? $this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY]
            : GeneralUtility::trimExplode(',', $this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY]);
        if (is_array($this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY])) {
            $this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY]
                = implode(',', $this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY]);
        }

        // get current display mode
        $displayMode = ExplorerUtility::getDisplayMode(
            $availableMode,
            $this->configurationManager->getContentObject()->data['uid']
        );

        // assign data to the view
        $this->view->assign('root_folder', $this->rootFolder);
        $this->view->assign('current_folder', $this->currentFolder);
        $this->view->assign(
            'files',
            $this->fileRepository->findFilesForFolder(
                $this->currentFolder->getUid(),
                $configuration['view'][Configuration::PLUGIN_NAMESPACE_KEY]
            )
        );
        $this->view->assign('has_many_display_mode', (count($availableMode) > 1));
        $this->view->assign('display_mode', $displayMode);
        $this->view->assign('columns_table', GeneralUtility::trimExplode(',', $this->settings['columnsTable']));
        $this->view->assign(
            'allowed_actions_files',
            GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFiles'])
        );
        $this->view->assign(
            'allowed_actions_folders',
            GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFolders'])
        );
        $this->view->assign(
            'massactions',
            [
                '' => '',
                'move' => LocalizationUtility::translate('massaction.move', Configuration::EXTENSION_KEY),
                'copy' => LocalizationUtility::translate('massaction.copy', Configuration::EXTENSION_KEY),
                'remove' => LocalizationUtility::translate('massaction.remove', Configuration::EXTENSION_KEY),
            ]
        );
        $this->view->assign('folders_options', ExplorerUtility::getFolderOptionTree([$this->rootFolder]));
        if ($this->request->hasArgument(Configuration::DIRECTION_ARGUMENT_KEY)) {
            $this->view->assign(
                Configuration::DIRECTION_ARGUMENT_KEY,
                $this->request->getArgument(Configuration::DIRECTION_ARGUMENT_KEY)
            );
        }
        $GLOBALS['TSFE']->register['tx_ameosfilemanager'][Configuration::PLUGIN_NAMESPACE_KEY]
            = $configuration['view'][Configuration::PLUGIN_NAMESPACE_KEY];
    }

    /**
     * Search action
     */
    protected function searchAction()
    {
        if (
            !$this->request->hasArgument(Configuration::QUERY_ARGUMENT_KEY)
            || $this->request->getArgument(Configuration::QUERY_ARGUMENT_KEY) == ''
        ) {
            $this->forward(Configuration::INDEX_ACTION_KEY);
        }

        $configuration = $this->getPluginConfiguration();

        $this->fetchRootAndCurrentFolders();

        $this->parseFolderInFe();

        // get current display mode
        $displayMode = ExplorerUtility::getDisplayMode(
            $this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY],
            $this->configurationManager->getContentObject()->data['uid']
        );

        $this->settings['displayFolders'] = 0;
        $this->settings[Configuration::RECURSION_SETTINGS_KEY]
            = $this->settings[Configuration::RECURSION_SETTINGS_KEY] == ''
                ? PHP_INT_MAX
                : $this->settings[Configuration::RECURSION_SETTINGS_KEY];

        // assign data to the view
        $this->view->assign('settings', $this->settings);
        $this->view->assign('current_query', $this->request->getArgument(Configuration::QUERY_ARGUMENT_KEY));
        $this->view->assign('root_folder', $this->rootFolder);
        $this->view->assign('current_folder', $this->currentFolder);
        $this->view->assign(
            'has_many_display_mode',
            (strpos($this->settings[Configuration::AVAILABLE_MODE_SETTINGS_KEY], ',') !== false)
        );
        $this->view->assign('display_mode', $displayMode);
        $this->view->assign('columns_table', GeneralUtility::trimExplode(',', $this->settings['columnsTable']));
        $this->view->assign(
            'allowed_actions_files',
            GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFiles'])
        );
        $this->view->assign(
            'allowed_actions_folders',
            GeneralUtility::trimExplode(',', $this->settings['allowedActionsOnFolders'])
        );
        $this->view->assign(
            'files',
            $this->fileRepository->findBySearchCriterias(
                ['keyword' => $this->request->getArgument(Configuration::QUERY_ARGUMENT_KEY)],
                $this->currentFolderidentifier,
                $configuration['view'][Configuration::PLUGIN_NAMESPACE_KEY],
                $this->settings[Configuration::RECURSION_SETTINGS_KEY]
            )
        );
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
        $this->redirect(
            Configuration::INDEX_ACTION_KEY,
            null,
            null,
            [Configuration::FOLDER_ARGUMENT_KEY => $this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY)]
        );
    }

    /**
     * Display errors (flash messages)
     */
    protected function errorsAction()
    {
    }

    /**
     * Fetch root and current folder from request
     */
    protected function fetchRootAndCurrentFolders()
    {
        // get folders (root and current)
        $this->currentFolderidentifier = $this->request->hasArgument(Configuration::FOLDER_ARGUMENT_KEY)
            ? $this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY)
            : $this->settings[Configuration::START_FOLDER_SETTINGS_KEY];
        $this->currentFolder = $this->folderRepository->findByUid($this->currentFolderidentifier);
        $this->rootFolder = $this->folderRepository
            ->findByUid($this->settings[Configuration::START_FOLDER_SETTINGS_KEY]);

        // check if current folder is a child of root folder
        if (!$this->currentFolder || !$this->currentFolder->isChildOf($this->rootFolder->getUid())) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY);
        }

        // check recursion
        if (
            FilemanagerUtility::hasTooMuchRecursion(
                $this->rootFolder,
                $this->currentFolder,
                $this->settings[Configuration::RECURSION_SETTINGS_KEY]
            )
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('tooMuchRecursion', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY);
        }
    }

    /**
     * Parse current folder from FE context if option active
     */
    protected function parseFolderInFe()
    {
        // parse folder if needed
        if ($this->settings['parseFolderInFE']) {
            FilemanagerUtility::parseFolderForNewElements(
                $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                $this->currentFolder->getGedPath(),
                $this->currentFolder->getTitle()
            );
        }
    }
}
