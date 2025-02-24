<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Filedownload;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Exception\AccessDeniedException;
use Ameos\AmeosFilemanager\Exception\MissingPackageException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class DownloadService
{
    /**
     * @param AccessService $accessService
     * @param FiledownloadRepository $filedownloadRepository
     * @param FileRepository $fileRepository
     * @param StreamFactoryInterface $streamFactory
     * @param ResourceFactory $resourceFactory
     */
    public function __construct(
        private readonly AccessService $accessService,
        private readonly FiledownloadRepository $filedownloadRepository,
        private readonly FileRepository $fileRepository,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ResourceFactory $resourceFactory
    ) {
    }

    /**
     * download the file and log the download in the DB
     * @param File $file
     * @return ResponseInterface
     */
    public function downloadFile(File $file): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');

        // We check if the user has access to the file.
        if ($file && $this->accessService->canReadFile($file)) {
            $filename = Environment::getPublicPath()
                . '/'
                . preg_replace('/^\//i', '', urldecode($file->getPublicUrl()));

            if (
                ExtensionManagementUtility::isLoaded('fal_securedownload')
                && $file->getOriginalResource()->getStorage()->getStorageRecord()['is_public'] == 0
            ) {
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($feUserUid);
                $this->filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                return new RedirectResponse($filename);
            }
            if (file_exists($filename)) {
                // We register who downloaded the file and when
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($feUserUid ?? 0);
                $this->filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                ob_clean();
                header('Content-Description: FileTransfer');
                header('Content-Type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                header('Expires: 0');
                header('Cache-Control: must-relavidate');
                header('Pragma: public');
                echo readfile($filename);
                exit;
            }
        } else {
            throw new AccessDeniedException('Access denied');
        }
    }

    /**
     * download folder (zip)
     *
     * @param Folder $folder
     * @return ResponseInterface
     */
    public function downloadFolder(Folder $folder): ResponseInterface
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new MissingPackageException(
                'ZipArchive is not installed on your server : see http://php.net/ZipArchive'
            );
        }

        // We check if the user has access to the folder.
        if ($folder && $this->accessService->canReadFolder($folder)) {
            $zipPath  = Environment::getVarPath()
                . '/'
                . preg_replace('/^\//i', '', $folder->getTitle())
                . '_'
                . date('dmY_His')
                . '.zip';

            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE);
            $this->addToZip($folder, $folder, $zip);
            $zip->close();

            ob_clean();
            header('Content-Description: FileTransfer');
            header('Content-Type: ' . mime_content_type($zipPath));
            header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-relavidate');
            header('Pragma: public');
            echo readfile($zipPath);
            unlink($zipPath);
            exit;
        } else {
            throw new AccessDeniedException('Access denied');
        }
    }


    /**
     * add folder to zip
     * @param Folder $root
     * @param Folder $folder
     * @param \ZipArchive $zip zip archive
     * @return void
     */
    private function addToZip(Folder $root, Folder $folder, \ZipArchive $zip): void
    {
        $storage = $this->resourceFactory->getStorageObject($folder->getStorage());
        $configuration = $storage->getConfiguration();

        if (!empty($configuration['pathType']) && $configuration['pathType'] === 'relative') {
            $relativeBasePath = $configuration['basePath'];
            $absoluteBasePath = rtrim(Environment::getPublicPath() . '/' . $relativeBasePath, '/');
        } else {
            $absoluteBasePath = rtrim($configuration['basePath'], '/');
        }

        $rootPath = $absoluteBasePath . $storage->getFolder($root->getIdentifier())->getReadablePath();

        /** @var iterable<File> */
        $files = $this->fileRepository->findFilesForFolder($folder);
        foreach ($files as $file) {
            if (!$file->isRemote() && $this->accessService->canReadFile($file)) {
                $realFilepath = $absoluteBasePath . $file->getOriginalResource()->getIdentifier();
                $zipFilepath = str_replace($rootPath, '', $realFilepath);

                $zip->addFile($realFilepath, $zipFilepath);
            }
        }

        foreach ($folder->getFolders() as $subFolder) {
            if ($this->accessService->canAddFolder($subFolder)) {
                $this->addToZip($root, $subFolder, $zip);
            }
        }
    }
}
