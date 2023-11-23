<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Service\AssetService;
use Ameos\AmeosFilemanager\Service\CategoryService;
use Ameos\AmeosFilemanager\Service\DownloadService;
use Ameos\AmeosFilemanager\Service\FolderService;
use Ameos\AmeosFilemanager\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FolderController extends ActionController
{
    public const ARG_FOLDER = 'folder';
    public const ARG_PARENT = 'parentfolder';

    /**
     * @param FolderService $folderService
     * @param CategoryService $categoryService
     * @param UserService $userService
     * @param AssetService $assetService
     * @param DownloadService $downloadService
     */
    public function __construct(
        private readonly FolderService $folderService,
        private readonly CategoryService $categoryService,
        private readonly UserService $userService,
        private readonly AssetService $assetService,
        private readonly DownloadService $downloadService
    ) {
    }

    /**
     * Edit folder
     *
     * @return ResponseInterface
     */
    protected function editAction()
    {
        $this->assetService->addCommonAssets($this->settings);

        $isNewFolder = $this->request->getArgument(self::ARG_FOLDER) === 'new';
        
        $fid = $this->request->getArgument(self::ARG_FOLDER) ? (int)$this->request->getArgument(self::ARG_FOLDER) : 0;
        $pid = $this->request->hasArgument(self::ARG_PARENT) ? (int)$this->request->getArgument(self::ARG_PARENT) : 0;

        $parent = $isNewFolder ? $this->folderService->load($pid) : null;
        $folder = $isNewFolder ? (new Folder()) : $this->folderService->load($fid);

        if ($this->request->getMethod() === 'POST') {
            if (
                !$this->request->hasArgument('title')
                || $this->request->getArgument('title') == ''
            ) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('titleRequired', Configuration::EXTENSION_KEY),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
            } else {
                if ($isNewFolder) {
                    $folder = $this->folderService->create($parent, $this->request, $this->settings);
                } else {
                    $folder = $this->folderService->update($folder, $this->request, $this->settings);
                }

                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        $isNewFolder ? 'folderCreated' : 'folderUpdated',
                        Configuration::EXTENSION_KEY,
                        [$this->request->getArgument('title')]
                    )
                );

                $this->redirect(
                    'index',
                    ExplorerController::CONTROLLER_KEY,
                    null,
                    [ExplorerController::ARG_FOLDER => $folder->getUid()]
                );
            }
        }

        $this->view->assign('folder', $folder);
        $this->view->assign('parent', $parent);
        $this->view->assign('usergroups', $this->userService->getAvailableUsergroups($this->settings));
        $this->view->assign('categories', $this->categoryService->getAvailableCategories($this->settings));
        $this->view->assign('isUserLoggedIn', $this->userService->isUserLoggedIn());

        return $this->htmlResponse();
    }

    /**
     * Download folder as zip
     *
     * @return ResponseInterface
     */
    protected function downloadAction(): ResponseInterface
    {
        $folder = $this->folderService->load((int)$this->request->getArgument(self::ARG_FOLDER));
        return $this->downloadService->downloadFolder($folder);
    }

    /**
     * Delete the folder
     *
     * @return ResponseInterface
     */
    protected function removeAction()
    {
        if (
            !$this->request->hasArgument(self::ARG_FOLDER)
            || (int)$this->request->getArgument(self::ARG_FOLDER) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFolderArgument', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $folder = $this->folderService->load((int)$this->request->getArgument(self::ARG_FOLDER));
        $parentFolder = $folder->getParent();

        $this->folderService->remove($folder);

        $this->addFlashMessage(LocalizationUtility::translate('folderRemoved', Configuration::EXTENSION_KEY));
        return $this->redirect(
            'index',
            ExplorerController::CONTROLLER_KEY,
            null,
            [ExplorerController::ARG_FOLDER => $parentFolder->getUid()]
        );
    }

    /**
     * Info folder
     *
     * @return ResponseInterface
     */
    protected function infoAction(): ResponseInterface
    {
        $this->assetService->addCommonAssets($this->settings);

        if (
            !$this->request->hasArgument(self::ARG_FOLDER)
            || (int)$this->request->getArgument(self::ARG_FOLDER) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFolderArgument', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $folder = $this->folderService->load((int)$this->request->getArgument(self::ARG_FOLDER));
        $this->view->assign('folder', $folder);

        return $this->htmlResponse();
    }
}
