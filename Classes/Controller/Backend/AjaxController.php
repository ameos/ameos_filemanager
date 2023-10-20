<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class AjaxController extends ActionController
{
    /**
     * ResourceFactory object
     *
     * @var ResourceFactory
     * @Inject
     */
    protected $resourceFactory;

    /**
     * FolderRepository object
     *
     * @var FolderRepository
     * @Inject
     */
    protected $folderRepository;

    /**
     * Inject ResourceFactory object
     *
     * @param ResourceFactory $resourceFactory ResourceFactory object
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Inject FolderRepository object
     *
     * @param FolderRepository $folderRepository FolderRepository object
     */
    public function injectFolderRepository(FolderRepository $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }

    /**
     * Return folder id from identifier
     *
     * @param ServerRequestInterface $request The request
     *
     * @return ResponseInterface
     */
    public function getFolderId(ServerRequestInterface $request)
    {
        $folder = $this->resourceFactory->retrieveFileOrFolderObject(
            $request->getParsedBody()['folderIdentifier']
        );

        $folderRecord = $this->folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        return (new JsonResponse())->setPayload(['uid' => $folderRecord['uid']]);
    }
}
