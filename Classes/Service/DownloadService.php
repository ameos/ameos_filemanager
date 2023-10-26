<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\Filedownload;
use Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Exception\AccessDeniedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class DownloadService
{
    /**
     * @param AccessService $accessService
     * @param FileRepository $fileRepository
     * @param FiledownloadRepository $filedownloadRepository
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        private readonly AccessService $accessService,
        private readonly FileRepository $fileRepository,
        private readonly FiledownloadRepository $filedownloadRepository,
        private readonly StreamFactoryInterface $streamFactory
    ) {
        
    }

    /**
     * download the file and log the download in the DB
     * @param int $uid
     * @return ResponseInterface
     */
    public function downloadFile(int $uid): ResponseInterface
    {
        $file = $this->fileRepository->findByUid($uid);
        $user = ($GLOBALS['TSFE']->fe_user->user);

        // We check if the user has access to the file.
        if ($file && $this->accessService->canReadFile($user, $file)) {
            $filename = preg_replace('/^\//i', '', urldecode($file->getPublicUrl()));

            if (
                ExtensionManagementUtility::isLoaded('fal_securedownload')
                && $file->getOriginalResource()->getStorage()->getStorageRecord()['is_public'] == 0
            ) {
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($user['uid']);
                $this->filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                return new RedirectResponse($filename);
            }
            if (file_exists($filename)) {
                // We register who downloaded the file and when
                $filedownload = GeneralUtility::makeInstance(Filedownload::class);
                $filedownload->setFile($file);
                $filedownload->setUserDownload($user['uid'] ?? 0);
                $this->filedownloadRepository->add($filedownload);
                $persitenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                $persitenceManager->persistAll();

                $response = new Response();
                $response = $response->withHeader('Content-Description', 'File Transfer');
                $response = $response->withHeader('Content-Type', mime_content_type($filename));
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
                $response = $response->withHeader('Expires', '0');
                $response = $response->withHeader('Cache-Control', 'must-revalidate');
                $response = $response->withHeader('Pragma', 'public');
                $response = $response->withBody($this->streamFactory->createStream(file_get_contents($filename)));
                return $response;
            }
        } else {
            throw new AccessDeniedException('Access denied');
        }
    }

}
