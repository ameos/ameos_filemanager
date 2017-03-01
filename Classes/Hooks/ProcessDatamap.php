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
 
class ProcessDatamap
{
    /**
     * post process field array
     */ 
    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj)
    {
        if ($table == 'tx_ameosfilemanager_domain_model_folder' && isset($fieldArray['status'])) {
            if ($fieldArray['status'] == 1 || $fieldArray['status'] == 2) {
                $fieldArray['realstatus'] = $fieldArray['status'];
            }
            if ($fieldArray['status'] == 0) {
                $folder = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_ameosfilemanager_domain_model_folder', 'uid = ' . (int)$id);
                $fieldArray['realstatus'] = FilemanagerUtility::calculFolderStatus($folder['uid_parent']);
            }
            FilemanagerUtility::updateChildStatus($id, $fieldArray['realstatus']);
        }
        if ($table == 'sys_file_metadata' && isset($fieldArray['status'])) {
            if ($fieldArray['status'] == 1 || $fieldArray['status'] == 2) {
                $fieldArray['realstatus'] = $fieldArray['status'];
            }
            if ($fieldArray['status'] == 0) {
                $metadata = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_metadata', 'uid = ' . (int)$id);
                $fieldArray['realstatus'] = FilemanagerUtility::calculFolderStatus($metadata['folder_uid']);
            }
        }
    }
}
