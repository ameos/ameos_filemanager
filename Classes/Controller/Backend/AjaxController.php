<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Service\FolderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class AjaxController extends ActionController
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly FolderService $folderService
    ) {
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
        $resource = $this->resourceFactory->retrieveFileOrFolderObject($body['folderIdentifier']);

        $folder = $this->folderService->loadByResourceFolder($resource);
        return (new JsonResponse())->setPayload(['result' => $folder ? $folder->getUid() : null]);
    }
}
