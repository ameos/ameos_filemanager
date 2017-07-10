<?php
namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

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
 
class FileRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var array
     */ 
    protected $defaultOrderings = ['tstamp' => QueryInterface::ORDER_DESCENDING];

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
     * find files for a folder
     * @param mixed $folder 
     */ 
    public function findFilesForFolder($folder, $pluginNamespace = 'tx_ameosfilemanager_fe_filemanager')
    {
        if (empty($folder)) {
            return $this->findAll();
        }

        $fields = 'sys_file.*'; 
        $from = 'sys_file, sys_file_metadata LEFT JOIN fe_users ON sys_file_metadata.fe_user_id = fe_users.uid';
        if (is_array($folder)) {
            $folders = array_map('intval', $folder);
            $where = 'sys_file_metadata.file = sys_file.uid AND sys_file_metadata.folder_uid IN (' . implode(',', $folders) . ')';
        } else {
            $where = 'sys_file_metadata.file = sys_file.uid AND sys_file_metadata.folder_uid = ' . (int)$folder;    
        }

        $order = '';
        $get = GeneralUtility::_GET($pluginNamespace);
        $availableSorting = [
            'sys_file.name', 'sys_file.creation_date', 'sys_file.modification_date', 'sys_file.size',
            'sys_file.tstamp', 'sys_file.crdate',
            'sys_file_metadata.tstamp', 'sys_file_metadata.crdate', 'sys_file_metadata.description',
            'sys_file_metadata.title', 'sys_file_metadata.categories', 'sys_file_metadata.keywords',
            'fe_users.name', 'fe_users.username', 'fe_users.company',
        ];
    
        if (isset($get['sort']) && $get['sort'] != '' && in_array($get['sort'], $availableSorting)) {
            $direction = (isset($get['direction']) && $get['direction'] != '') ? $get['direction'] : 'ASC';
            $order = $get['sort'] . ' ' . $direction;
        }
        
        $query = $this->createQuery();
        $query->statement($GLOBALS['TYPO3_DB']->SELECTquery(
            $fields, 
            $from, 
            $where,
            '',
            $order
        ));
        return $query->execute();
    }

    /**
     * return files identifiers for folder recursively
     * @param string folders
     * @param int $recursiveLimit
     * @param int $currentRecursive
     */
    protected function getFilesIdentifiersRecursively($folders, $recursiveLimit = 0, $currentRecursive = 1)
    {
        $files = [];
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('file', 'sys_file_metadata', 'folder_uid IN (' . $folders . ')');
        while (($file = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== false) {
            $files[] = $file['file'];
        }

        if ($currentRecursive <= $recursiveLimit) {
            $currentRecursive++;
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'uid_parent IN (' . $folders . ')');
            $childs = [];
            while (($folder = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== false) {
                $childs[] = $folder['uid'];
            }
            if (!empty($childs)) {
                $files = array_merge($files, $this->getFilesIdentifiersRecursively(implode(',', $childs), $recursiveLimit, $currentRecursive));    
            }
        }
        return $files;
    }

    /**
     * Return all filter by search criterias
     * @param array $criterias criterias
     * @param int $rootFolder
     * @param string $pluginNamespace
     * @param int $recursiveLimit
     */
    public function findBySearchCriterias($criterias, $rootFolder = null, $pluginNamespace = 'tx_ameosfilemanager_fe_filemanager', $recursiveLimit = 0)
    {
        if (!is_array($criterias) || empty($criterias)) {
            return $this->findAll();
        }

        $rootFolder = (!is_null($rootFolder) && is_object($rootFolder)) ? $rootFolder->getUid() : $rootFolder;

        $additionnalWhereClause = '';
        if (!is_null($rootFolder) && (int)$rootFolder > 0) {
            $availableFilesIdentifiers = $this->getFilesIdentifiersRecursively($rootFolder, $recursiveLimit);
            if (empty($availableFilesIdentifiers)) {
                $additionnalWhereClause = ' AND sys_file.uid = 0';    
            } else {
                $additionnalWhereClause = ' AND sys_file.uid IN (' . implode(',', $availableFilesIdentifiers) . ')';
            }
        }
        
        $fields = 'distinct sys_file.*'; 
        $from = 'sys_file_metadata INNER JOIN sys_file  ON sys_file_metadata.file=sys_file.uid LEFT JOIN sys_category_record_mm ON sys_file_metadata.uid = sys_category_record_mm.uid_foreign LEFT JOIN sys_category ON sys_category_record_mm.uid_local = sys_category.uid';
        $where = '1';
        if (isset($criterias['keyword']) && $criterias['keyword'] !== '') {
            $arrayKeywords = explode(' ', $criterias['keyword']);
            $arrayCondition = array();
            $where .= " AND (sys_category_record_mm.tablenames LIKE 'sys_file_metadata' OR sys_category_record_mm.tablenames IS NULL) ";
            foreach ($arrayKeywords as $keyword) {
                $where .= "AND ( ";
                $where .= " sys_file_metadata.title LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file_metadata');
                $where .= " OR sys_file_metadata.description LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file_metadata'); 
                $where .= " OR sys_file_metadata.keywords LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file_metadata');
                $where .= " OR sys_file.name LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file');
                $where .= " OR sys_category.title LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_category');
                $where .= " OR sys_file_metadata.fe_user_id IN (SELECT uid FROM fe_users WHERE
                    deleted = 0
                    AND (name LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'fe_users') . "
                    OR first_name LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'fe_users') . "
                    OR middle_name LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'fe_users') . "
                    OR last_name LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'fe_users') . "))";
                $where .= ") ";
            }
        }

        $where .= $additionnalWhereClause;

        $order = '';
        $get = GeneralUtility::_GET($pluginNamespace);
        $availableSorting = [
            'sys_file.name', 'sys_file.creation_date', 'sys_file.modification_date', 'sys_file.size',
            'sys_file.tstamp', 'sys_file.crdate',
            'sys_file_metadata.tstamp', 'sys_file_metadata.crdate', 'sys_file_metadata.description',
            'sys_file_metadata.title', 'sys_file_metadata.categories', 'sys_file_metadata.keywords',
            'fe_users.name', 'fe_users.username', 'fe_users.company',
        ];
    
        if (isset($get['sort']) && $get['sort'] != '' && in_array($get['sort'], $availableSorting)) {
            $direction = (isset($get['direction']) && $get['direction'] != '') ? $get['direction'] : 'ASC';
            $order = $get['sort'] . ' ' . $direction;
        }
        
        $query = $this->createQuery();
        $query->statement($GLOBALS['TYPO3_DB']->SELECTquery(
            $fields, 
            $from, 
            $where,
            '',
            $order
        ));
        return $query->execute();
    }

    /**
     * return file by uid
     * @param int $fileUid file uid
     * @param boolean $writeRight if true, use write access instead of read access
     */ 
    public function findByUid($fileUid, $writeRight = false)
    {
        if (empty($fileUid)) {
            return 0;
        }

        // filter by uid
        $where = 'sys_file.uid = ' . (int)$fileUid;

        // check group access 
        $column = $writeRight ? 'fe_group_write' : 'fe_group_read';
        $userGroups = $GLOBALS['TSFE']->gr_list;
        $where .= ' AND (
            ( 
                sys_file_metadata.' . $column . ' = \'\' 
                OR sys_file_metadata.' . $column . ' IS NULL 
                OR sys_file_metadata.' . $column . ' = 0';

        
        foreach (explode(',', $userGroups) as $userGroup) {
            $where .= ' OR FIND_IN_SET(' . $userGroup . ', sys_file_metadata.' . $column . ')';
        }
        $where .= ')';

        // check owner access 
        if ($GLOBALS['TSFE']->loginUser) {
            $ownerAccessField = $writeRight ? 'owner_has_write_access' : 'owner_has_read_access';
            $where .= ' OR (
                sys_file_metadata.fe_user_id = '. (int)$GLOBALS['TSFE']->fe_user->user['uid'] . '
                AND sys_file_metadata.' . $ownerAccessField . ' = 1
            )';
        }

        // clause access right 
        $where .= ')';

        $query = $this->createQuery();
        $query->statement('
            SELECT
                distinct sys_file.uid,
                sys_file_metadata.folder_uid 
            FROM
                sys_file_metadata
                INNER JOIN sys_file ON sys_file_metadata.file=sys_file.uid
            WHERE
                ' . $where . '
            ORDER BY
                sys_file_metadata.uid DESC ',
            []
        );
        
        $res = $query->execute()->getFirst();
        return $res;
    }
}
