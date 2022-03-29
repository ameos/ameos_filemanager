<?php

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
