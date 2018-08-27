<?php
namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 
class FolderRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    protected $defaultOrderings = array(
        'crdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
    );
    
    /**
     * Initialization
     */
    public function initializeObject()
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Update the folder
     * @param integer $uid uid of the folder
     * @param array $field_values values to update
     */
    public function requestUpdate($uid, $field_values)
    {
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_ameosfilemanager_domain_model_folder', 'uid = ' . $uid, $field_values, false);
    }

    /**
     * Delete a folder and all of it's content
     * @param integer $uid folder uid
     */
    public function requestDelete($uid)
    {
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_metadata', 'folder_uid = '. (int)$uid, ['deleted' => 1]);
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_ameosfilemanager_domain_model_folder', 'uid = '.(int)$uid, ['deleted' => 1]);
    }

    /**
     * insert new Folder
     * @param array $insertArray values of the folder
     */
    public function requestInsert($insertArray)
    {
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_ameosfilemanager_domain_model_folder', $insertArray);
    }

    /**
     * count total files size for a folder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param bool $withArchive
     * @return int
     */ 
    public function countFilesizeForFolder($folder, $withArchive = true)
    {
        if (!$folder) {
            return 0;
        }

        $addWhere = '';
        if (!$withArchive) {
            $addWhere .= ' AND realstatus IN (0,1)';
        }

        return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'SUM(sys_file.size) as total',
            'sys_file, sys_file_metadata, tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.identifier LIKE \'' . $folder->getIdentifier() . '%\'
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage() . '
                AND tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND sys_file_metadata.file = sys_file.uid
                AND sys_file_metadata.folder_uid = tx_ameosfilemanager_domain_model_folder.uid' . $addWhere
        )['total'];
    }

    /**
     * count file for a folder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param bool $withArchive
     * @return int
     */ 
    public function countFilesForFolder($folder, $withArchive = true)
    {
        if (!$folder) {
            return 0;
        }

        $addWhere = '';
        if (!$withArchive) {
            $addWhere .= ' AND realstatus IN (0,1)';
        }

        return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'count(*) as count',
            'sys_file, sys_file_metadata, tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.identifier LIKE \'' . $folder->getIdentifier() . '%\'
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage() . '
                AND tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND sys_file_metadata.file = sys_file.uid
                AND sys_file_metadata.folder_uid = tx_ameosfilemanager_domain_model_folder.uid' . $addWhere
        )['count'];
    }

    /**
     * count file for a folder
     * @param int $folderUid
     * @param bool $withArchive
     * @return int
     */ 
    public function countFoldersForFolder($folderUid, $withArchive = true)
    {
        if (empty($folderUid)) {
            return 0;
        }    
        $where = 'uid_parent = ' . (int)$folderUid;
        if (!$withArchive) {
            $where .= ' AND realstatus IN (0,1)';
        }
        return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as count','tx_ameosfilemanager_domain_model_folder', $where)['count'];
    }

    public function getSubFolderFromFolder($folderUid)
    {
        if (empty($folderUid)) {
            return 0;
        }
        $query = $this->createQuery();        
        $where = 'tx_ameosfilemanager_domain_model_folder.uid_parent = ' . (int)$folderUid;
        $where .= $this->getModifiedEnabledFields();
        $query->statement
        (    '    SELECT tx_ameosfilemanager_domain_model_folder.* 
                FROM tx_ameosfilemanager_domain_model_folder 
                WHERE '.$where.' 
                ORDER BY tx_ameosfilemanager_domain_model_folder.title ASC 
            ',
            array()
        );
        $res = $query->execute();
        return $res;
    }

    public function getModifiedEnabledFields($writeMode = false)
    {
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $enableFieldsWithFeGroup = $pageRepository->enableFields('tx_ameosfilemanager_domain_model_folder', 0, ['disabled' => 1]);
        $enableFieldsWithoutFeGroup = $pageRepository->enableFields('tx_ameosfilemanager_domain_model_folder', 0, ['fe_group' => 1]);

        $ownerOnlyField   = $writeMode ? 'no_write_access' : 'no_read_access';
        $ownerAccessField = $writeMode ? 'owner_has_write_access' : 'owner_has_read_access';

        if ($GLOBALS['TSFE']->loginUser) {
            $where = ' AND (';
            $where .= '(1 ' . $enableFieldsWithoutFeGroup  . ')'; // classic enable fields

            // open clause access right 
            $where .= ' AND (';
                
            // available for all (owner only field = 0)
            $where .=  '(' . $ownerOnlyField . ' = 0 ' // owner only field = 0
                    . ' AND (' // and
                        . '(1 ' . $enableFieldsWithFeGroup . ')' // group access
                        . ' OR (' // is owner 
                            . 'tx_ameosfilemanager_domain_model_folder.fe_user_id = ' . $GLOBALS['TSFE']->fe_user->user['uid']
                            . ' AND tx_ameosfilemanager_domain_model_folder.' . $ownerAccessField . ' = 1
                        )
                    )
                )';

            // available only owner (owner only field = 1)
            $where .=  ' OR ('
                    . $ownerOnlyField . ' = 1 ' // owner only field = 0
                    . ' AND tx_ameosfilemanager_domain_model_folder.fe_user_id = ' . $GLOBALS['TSFE']->fe_user->user['uid'] // user is the owner
                    . ' AND (' // and
                        . '(1 ' . $enableFieldsWithFeGroup . ')'  // group access
                        . ' OR tx_ameosfilemanager_domain_model_folder.fe_user_id = ' . $GLOBALS['TSFE']->fe_user->user['uid'] // owner has access
                    . ')
                )';

            // close clause access right 
            $where .= ')';

            // close enable field clause
            $where .= ')';
        } else {
            $where = ' AND (' . $ownerOnlyField . ' = 0 ' . $enableFieldsWithFeGroup . ')';
        }
        return $where;
    }

    public function findByUid($folderUid, $accessMode = 'read')
    {
        if (empty($folderUid)) {
            return 0;
        }
        // if write mode is set, we change the fegroup enablecolumns value to match the write column in the bdd
        switch ($accessMode) {
            case 'read':      $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_read';      break;
            case 'write':     $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_write';     break;
            case 'addfile':   $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_addfile';   break;
            case 'addfolder': $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]['ctrl']['enablecolumns']['fe_group'] = 'fe_group_addfolder'; break;
        }

        $writeMode = $accessMode == 'read' ? false : true;        
        $query = $this->createQuery();        
        $where = 'tx_ameosfilemanager_domain_model_folder.uid = ' . (int)$folderUid;
        $where .= $this->getModifiedEnabledFields($writeMode);
        $query->statement('SELECT * FROM tx_ameosfilemanager_domain_model_folder WHERE ' . $where, []);

        // Don't forget to change back to read right once the deed is done
        $GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder']['ctrl']['enablecolumns']['fe_group'] = 'fe_group_read';

        $res = $query->execute()->getFirst();
        return $res;
    }
}
