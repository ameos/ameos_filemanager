<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Service\DownloadService;
use Ameos\AmeosFilemanager\Service\FileService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FileController extends ActionController
{
    public const ARG_FILE = 'file';
    public const ARG_FOLDER = 'folder';

    /**
     * Errors array
     *
     * @var array
     */
    protected $errors = [];

    public function __construct(
        protected DownloadService $downloadService,
        protected FileService $fileService
    ) {
    }

    /**
     * Edit file
     * 
     * @return ResponseInterface
     */
    protected function editAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * Info file
     *
     * @return ResponseInterface
     */
    protected function infoAction(): ResponseInterface
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
            return $this->redirect('errors', 'Explorer/Explorer');
        }

        $file = $this->fileService->load((int)$this->request->getArgument(self::ARG_FILE));
        $this->view->assign(self::ARG_FILE, $file);
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
            return $this->redirect('errors', 'Explorer/Explorer');
        }

        return $this->downloadService->downloadFile((int)$this->request->getArgument(self::ARG_FILE));
    }

    /**
     * Remove the file
     *
     * @return ResponseInterface
     */
    protected function removeAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }
}
