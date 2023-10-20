<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Service\ExplorerService;
use Ameos\AmeosFilemanager\Service\FolderService;
use Ameos\AmeosFilemanager\Service\TreeService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExplorerController extends ActionController
{
    public const ARG_FOLDER = 'folder';
    public const ARG_DISPLAYMODE = 'displaymode';

    /**
     * @param ExplorerService $explorerService
     * @param FolderService $folderService
     */
    public function __construct(
        protected ExplorerService $explorerService,
        protected FolderService $folderService,
        protected TreeService $treeService
    ) {
    }

    /**
     * Main explorer action
     *
     * @return ResponseInterface
     */
    protected function indexAction(): ResponseInterface
    {
        $contentId = (int)$this->request->getAttribute('currentContentObject')->data['uid'];

        $folderIdentifier = null;
        if ($this->request->hasArgument(self::ARG_FOLDER)) {
            $folderIdentifier = (int)$this->request->getArgument(self::ARG_FOLDER);
        }

        $displayMode = $this->explorerService->getCurrentDisplayMode($this->settings['availableMode'], $contentId);
        $rootFolder = $this->folderService->getRootFolder($this->settings);
        $currentFolder = $this->folderService->getCurrentFolder($folderIdentifier, $this->settings);
        $tree = $this->treeService->getFoldersTree([$rootFolder]);

        // assign data to the view
        $this->view->assign('root_folder', $rootFolder);
        $this->view->assign('current_folder', $currentFolder);
        $this->view->assign('tree', $tree);
        $this->view->assign('flat_tree', $this->treeService->flatten($tree));
        $this->view->assign('current_folder_childs', $this->treeService->getFoldersChildren([$currentFolder]));
        $this->view->assign('files', $this->folderService->findFiles($currentFolder));
        $this->view->assign('has_many_display_mode', (count($this->settings['availableMode']) > 1));
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
        
        /*
        if ($this->request->hasArgument(Configuration::DIRECTION_ARGUMENT_KEY)) {
            $this->view->assign(
                Configuration::DIRECTION_ARGUMENT_KEY,
                $this->request->getArgument(Configuration::DIRECTION_ARGUMENT_KEY)
            );
        }*/


        return $this->htmlResponse();
    }

    /**
     * Update display mode action
     *
     * @return ResponseInterface
     */
    protected function updateDisplayModeAction(): ResponseInterface
    {
        if ($this->request->hasArgument(self::ARG_DISPLAYMODE)) {
            $contentId = (int)$this->request->getAttribute('currentContentObject')->data['uid'];
            $this->explorerService->updateDisplayMode($contentId, $this->request->getArgument(self::ARG_DISPLAYMODE));
        }
        
        return $this->redirect(
            'index',
            null,
            null,
            [self::ARG_FOLDER => $this->request->getArgument(self::ARG_FOLDER)]
        );
    }

    /**
     * Display errors (flash messages)
     *
     * @return ResponseInterface
     */
    protected function errorsAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }


    /**
     * Search action
     *
     * @return ResponseInterface
     */
    protected function searchAction(): ResponseInterface
    {
        /*
        TODO V12
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
*/
        return $this->htmlResponse();
    }
}
