<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class AjaxController extends ActionController
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly FolderRepository $folderRepository
    )
    {
    }

    /**
     * Return folder id from identifier
     *
     * @param ServerRequestInterface $request The request
     *
     * @return ResponseInterface
     */
    public function getFolderId(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode($request->getBody()->getContents(), true);
        $folder = $this->resourceFactory->retrieveFileOrFolderObject($body['folderIdentifier']);

        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        return (new JsonResponse())->setPayload(['result' => $folderRecord['uid']]);
    }
}
