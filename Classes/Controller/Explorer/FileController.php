<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Service\AccessService;
use Ameos\AmeosFilemanager\Service\AssetService;
use Ameos\AmeosFilemanager\Service\CategoryService;
use Ameos\AmeosFilemanager\Service\DownloadService;
use Ameos\AmeosFilemanager\Service\FileService;
use Ameos\AmeosFilemanager\Service\FolderService;
use Ameos\AmeosFilemanager\Service\UploadService;
use Ameos\AmeosFilemanager\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FileController extends ActionController
{
    public const ARG_FILE = 'file';
    public const ARG_FOLDER = 'folder';

    /**
     * @param DownloadService $downloadService
     * @param FileService $fileService
     * @param FolderService $folderService
     * @param AssetService $assetService
     * @param AccessService $accessService
     * @param UploadService $uploadService
     * @param UserService $userService
     * @param CategoryService $categoryService
     */
    public function __construct(
        private readonly DownloadService $downloadService,
        private readonly FileService $fileService,
        private readonly FolderService $folderService,
        private readonly AssetService $assetService,
        private readonly AccessService $accessService,
        private readonly UploadService $uploadService,
        private readonly UserService $userService,
        private readonly CategoryService $categoryService
    ) {
    }

    /**
     * Edit file
     *
     * @return ResponseInterface
     */
    protected function editAction(): ResponseInterface
    {
        $this->assetService->addCommonAssets($this->settings);

        $file = $this->fileService->load((int)$this->request->getArgument(self::ARG_FILE));

        if (!$this->accessService->canWriteFile($file)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        if ($this->request->getMethod() === 'POST') {
            $hasError = false;
            if (
                !$this->request->hasArgument('title')
                || $this->request->getArgument('title') == ''
            ) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('titleRequired', Configuration::EXTENSION_KEY),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
                $hasError = true;
            }
            $fileArg = $this->request->hasArgument('file') ? $this->request->getArgument('file') : [];
            $allowedExtensions = explode(',', $this->settings['allowedFileExtension']);
            if (
                !empty($fileArg)
                && isset($fileArg['name'])
                && $fileArg['name'] !== ''
                && !in_array(strtolower(pathinfo($fileArg['name'], PATHINFO_EXTENSION)), $allowedExtensions)
            ) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('titleRequired', Configuration::EXTENSION_KEY),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
                $hasError = true;
            }
            if (!$hasError) {
                $file = $this->fileService->update($file, $this->request, $this->settings);

                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'fileUpdated',
                        Configuration::EXTENSION_KEY,
                        [$this->request->getArgument('title')]
                    )
                );

                return $this->redirect(
                    'index',
                    ExplorerController::CONTROLLER_KEY,
                    null,
                    [ExplorerController::ARG_FOLDER => $file->getFolder()]
                );
            }
        }

        $this->view->assign('file', $file);
        $this->view->assign('usergroups', $this->userService->getAvailableUsergroups($this->settings));
        $this->view->assign('categories', $this->categoryService->getAvailableCategories($this->settings));
        $this->view->assign('isUserLoggedIn', $this->userService->isUserLoggedIn());

        return $this->htmlResponse();
    }

    /**
     * Info file
     *
     * @return ResponseInterface
     */
    protected function infoAction(): ResponseInterface
    {
        $this->assetService->addCommonAssets($this->settings);

        if (
            !$this->request->hasArgument(self::ARG_FILE)
            || (int)$this->request->getArgument(self::ARG_FILE) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFileArgument', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $file = $this->fileService->load((int)$this->request->getArgument(self::ARG_FILE));

        if (!$this->accessService->canReadFile($file)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $this->view->assign('file', $file);
        $this->view->assign('file_isimage', $this->fileService->isImage($file));
        $this->view->assign('filemetadata_isloaded', ExtensionManagementUtility::isLoaded('filemetadata'));

        return $this->htmlResponse();
    }

    /**
     * Upload files
     *
     * @return ResponseInterface
     */
    protected function uploadAction(): ResponseInterface
    {
        $fid = $this->request->getArgument(self::ARG_FOLDER) ? (int)$this->request->getArgument(self::ARG_FOLDER) : 0;
        $folder = $this->folderService->load($fid);
        $uploadUri = $this->uriBuilder->reset()->uriFor('upload', [self::ARG_FOLDER => $folder->getUid()]);

        if (!$this->accessService->canAddFile($folder)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $this->assetService->addCommonAssets($this->settings);
        $this->assetService->addDropzone($uploadUri);

        // upload if POST
        if ($this->request->getMethod() === 'POST') {
            $data = $this->uploadService->upload(
                $folder,
                $GLOBALS['TYPO3_REQUEST']->getUploadedFiles(),
                $this->settings
            );
            if ($data['success']) {
                $data['editUri'] = $this->uriBuilder->reset()->uriFor('edit', [self::ARG_FILE => $data['file']]);
                $data['infoUri'] = $this->uriBuilder->reset()->uriFor('info', [self::ARG_FILE => $data['file']]);
            }
            throw new PropagateResponseException(new JsonResponse($data));
        }

        $this->view->assign('folder', $folder);

        return $this->htmlResponse();
    }

    /**
     * Download file
     *
     * @return ResponseInterface
     */
    protected function downloadAction(): ResponseInterface
    {
        if (
            !$this->request->hasArgument(self::ARG_FILE)
            || (int)$this->request->getArgument(self::ARG_FILE) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFileArgument', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $file = $this->fileService->load((int)$this->request->getArgument(self::ARG_FILE));

        if (!$this->accessService->canReadFile($file)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        return $this->downloadService->downloadFile($file);
    }

    /**
     * Remove the file
     *
     * @return ResponseInterface
     */
    protected function removeAction(): ResponseInterface
    {
        if (
            !$this->request->hasArgument(self::ARG_FILE)
            || (int)$this->request->getArgument(self::ARG_FILE) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFileArgument', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $file = $this->fileService->load((int)$this->request->getArgument(self::ARG_FILE));
        $folder = $this->folderService->load($file->getFolder());

        if (!$this->accessService->canWriteFile($file)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('accessDenied', Configuration::EXTENSION_KEY),
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirect('errors', ExplorerController::CONTROLLER_KEY);
        }

        $this->fileService->remove($file);

        $this->addFlashMessage(LocalizationUtility::translate('fileRemoved', Configuration::EXTENSION_KEY));
        return $this->redirect(
            'index',
            ExplorerController::CONTROLLER_KEY,
            null,
            $folder ? [ExplorerController::ARG_FOLDER => $folder->getUid()] : null
        );
    }
}
