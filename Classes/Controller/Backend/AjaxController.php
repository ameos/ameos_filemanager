<?php
namespace Ameos\AmeosFilemanager\Controller\Backend;

use TYPO3\CMS\Core\Resource\ResourceFactory;

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
     * @param array $params Array of parameters from the AJAX interface, currently unused
     * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
     * @return void
     */
    public function getFolderId($params = [], \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = null)
    {
        $combinedIdentifier = $params['request']->getParsedBody()['folderIdentifier'];
        $folder = ResourceFactory::getInstance()->retrieveFileOrFolderObject($combinedIdentifier);

        $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.uid',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $folder->getIdentifier() . '\''
        );
        header('Content-Type: text/json');
        echo json_encode(['uid' => $folderRecord['uid']]);
        exit;
    }
}

