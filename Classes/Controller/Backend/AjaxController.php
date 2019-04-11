<?php
namespace Ameos\AmeosFilemanager\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;

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
 
class AjaxController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * return folder id from identifier
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getFolderId(ServerRequestInterface $request)
    {
        $folder = ResourceFactory::getInstance()->retrieveFileOrFolderObject(
            $request->getParsedBody()['folderIdentifier']
        );

        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
        return (new JsonResponse())->setPayload(['uid' => $folderRecord['uid']]);
    }
}