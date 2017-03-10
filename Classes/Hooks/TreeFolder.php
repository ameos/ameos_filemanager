<?php
namespace Ameos\AmeosFilemanager\Hooks;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;

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
 
class TreeFolder
{
    /**
     * User function for to render a tree for selecting a folder
     * of a selected storage
     *
     * @param array $PA the array with additional configuration options.
     * @param Object $tceformsObj Parent object
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function renderFlexTreeFolder(&$PA, $tceformsObj)
    {
        $storage = $PA['row']['settings.storage'][0];
        $availableFolders = [];
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND storage = ' . $storage);
        while (($folder = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== false) {
            $availableFolders[] = $folder['uid'];
        }

        foreach ($PA['items'] as $key => $item) {
            if (!in_array($item['1'], $availableFolders)) {
                unset($PA['items'][$key]);
            }
        }
    }
}
