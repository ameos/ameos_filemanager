<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Service\CategoryService;
use Ameos\AmeosFilemanager\Service\FolderService;
use Ameos\AmeosFilemanager\Service\UserService;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     */
    public function __construct(
        protected FolderService $folderService,
        protected CategoryService $categoryService,
        protected UserService $userService
    ) {
    }

    /**
     * Edit folder
     *
     * @return ResponseInterface
     */
    protected function editAction()
    {
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
                    $folder = $this->folderService->createFolder($parent, $this->request, $this->settings);
                } else {
                    $folder = $this->folderService->updateFolder($folder, $this->request, $this->settings);
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
                    'Explorer/Explorer',
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
        die('TODO V12 : dwonload ZIP');
        return $this->htmlResponse();
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
            return $this->redirect('errors', 'Explorer/Explorer');
        }

        $folder = $this->folderService->load((int)$this->request->getArgument(self::ARG_FOLDER));
        $parentFolder = $folder->getParent();

        $this->folderService->remove($folder);

        $this->addFlashMessage(LocalizationUtility::translate('folderRemoved', Configuration::EXTENSION_KEY));
        return $this->redirect(
            'index',
            'Explorer/Explorer',
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
        if (
            !$this->request->hasArgument(self::ARG_FOLDER)
            || (int)$this->request->getArgument(self::ARG_FOLDER) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFolderArgument', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', 'Explorer/Explorer');
        }

        $folder = $this->folderService->load((int)$this->request->getArgument(self::ARG_FOLDER));
        $this->view->assign('folder', $folder);

        return $this->htmlResponse();
    }
}
