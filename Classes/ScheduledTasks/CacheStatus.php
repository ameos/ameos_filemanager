<?php
namespace Ameos\AmeosFilemanager\ScheduledTasks;

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

class CacheStatus extends \TYPO3\CMS\Scheduler\Task\AbstractTask 
{

    /**
     * Execute scheduled task
     *
     * @return bool TRUE if task correcty executed
     */
    public function execute() 
    {
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_ameosfilemanager_domain_model_folder', 'deleted = 0 AND uid_parent = 0');
        while (($folder = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== false) {
            $updateData = [];
            if ($folder['status'] == 0) {
                $folder['status'] = $updateData['status'] = 1;                
            }
            if ($folder['realstatus'] != $folder['status']) {
                $updateData['realstatus'] = $folder['realstatus'] = $folder['status'];    
            }
            if (!empty($updateData)) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_ameosfilemanager_domain_model_folder', 'uid = ' . (int)$folder['uid'], $updateData);
            }
            FilemanagerUtility::updateChildStatus($folder['uid'], $folder['realstatus']);
        }
        return true;
    }
}
