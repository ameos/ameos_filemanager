<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Service\AssetService;
use Ameos\AmeosFilemanager\Service\ExplorerService;
use Ameos\AmeosFilemanager\Service\FileService;
use Ameos\AmeosFilemanager\Service\FolderService;
use Ameos\AmeosFilemanager\Service\TreeService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExplorerController extends ActionController
{
    public const CONTROLLER_KEY = 'Explorer\Explorer';
    public const ARG_FOLDER = 'folder';
    public const ARG_DIR = 'direction';
    public const ARG_SORT = 'sort';
    public const ARG_DISPLAYMODE = 'displaymode';
    public const ARG_QUERY = 'query';

    /**
     * @param ExplorerService $explorerService
     * @param FolderService $folderService
     * @param FileService $fileService
     * @param TreeService $treeService
     * @param AssetService $assetService
     */
    public function __construct(
        private readonly ExplorerService $explorerService,
        private readonly FolderService $folderService,
        private readonly FileService $fileService,
        private readonly TreeService $treeService,
        private readonly AssetService $assetService
    ) {
    }

    /**
     * Main explorer action
     *
     * @return ResponseInterface
     */
    protected function indexAction(): ResponseInterface
    {
        $this->assetService->addCommonAssets($this->settings);

        $contentId = (int)$this->request->getAttribute('currentContentObject')->data['uid'];

        $folderIdentifier = null;
        if ($this->request->hasArgument(self::ARG_FOLDER)) {
            $folderIdentifier = (int)$this->request->getArgument(self::ARG_FOLDER);
        }

        $displayMode = $this->explorerService->getCurrentDisplayMode($this->settings['availableMode'], $contentId);
        $rootFolder = $this->folderService->getRootFolder($this->settings);
        $currentFolder = $this->folderService->getCurrentFolder($folderIdentifier, $this->settings);
        $tree = $this->treeService->getFoldersTree([$rootFolder]);
        $sort = $this->request->hasArgument(self::ARG_SORT) ? $this->request->getArgument(self::ARG_SORT) : 'sys_file.name';
        $direction = $this->request->hasArgument(self::ARG_DIR) ? $this->request->getArgument(self::ARG_DIR) : 'ASC';

        // assign data to the view
        $this->view->assign('root_folder', $rootFolder);
        $this->view->assign('current_folder', $currentFolder);
        $this->view->assign('tree', $tree);
        $this->view->assign('flat_tree', $this->treeService->flatten($tree));
        $this->view->assign('current_folder_children', $this->treeService->getFoldersChildren([$currentFolder], $sort, $direction));
        $this->view->assign('files', $this->folderService->findFiles($currentFolder, $sort, $direction));
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
        
        if ($this->request->hasArgument(self::ARG_DIR)) {
            $this->view->assign('direction', $this->request->getArgument(self::ARG_DIR));
        }

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
        $this->assetService->addCommonAssets($this->settings);
        return $this->htmlResponse();
    }

    /**
     * Search action
     *
     * @return ResponseInterface
     */
    protected function searchAction(): ResponseInterface
    {
        $this->assetService->addCommonAssets($this->settings);

        if (!$this->request->hasArgument(self::ARG_QUERY) || $this->request->getArgument(self::ARG_QUERY) == '') {
            return $this->redirect('index');
        }

        $contentId = (int)$this->request->getAttribute('currentContentObject')->data['uid'];

        $displayMode = $this->explorerService->getCurrentDisplayMode($this->settings['availableMode'], $contentId);
        $rootFolder = $this->folderService->getRootFolder($this->settings);

        $tree = $this->treeService->getFoldersTree([$rootFolder]);
        $sort = $this->request->hasArgument(self::ARG_SORT) ? $this->request->getArgument(self::ARG_SORT) : 'sys_file.name';
        $direction = $this->request->hasArgument(self::ARG_DIR) ? $this->request->getArgument(self::ARG_DIR) : 'ASC';
        $files = $this->fileService->search($rootFolder, $this->request->getArgument(self::ARG_QUERY), $sort, $direction);

        // assign data to the view
        $this->view->assign('current_query', $this->request->getArgument(self::ARG_QUERY));
        $this->view->assign('files', $files);
        $this->view->assign('root_folder', $rootFolder);
        $this->view->assign('current_folder', $rootFolder);
        $this->view->assign('tree', $tree);
        $this->view->assign('flat_tree', $this->treeService->flatten($tree));
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
        
        if ($this->request->hasArgument(self::ARG_DIR)) {
            $this->view->assign('direction', $this->request->getArgument(self::ARG_DIR));
        }

        return $this->htmlResponse();
    }
}
