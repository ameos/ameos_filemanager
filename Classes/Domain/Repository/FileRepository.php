<?php
namespace Ameos\AmeosFilemanager\Domain\Repository;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('sys_file.*')
            ->from('sys_file')
            ->join(
                'sys_file',
                'sys_file_metadata',
                'sys_file_metadata',
                'sys_file_metadata.file = sys_file.uid'
            )
            ->leftJoin(
                'sys_file_metadata',
                'fe_users',
                'fe_users',
                'fe_users.uid = sys_file_metadata.fe_user_id'
            );

        if (is_array($folder)) {
            $folders = array_map('intval', $folder);
            $queryBuilder->where($queryBuilder->expr()->in('sys_file_metadata.folder_uid', $folders));
        } else {
            $queryBuilder->where($queryBuilder->expr()->eq('sys_file_metadata.folder_uid', (int)$folder));
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
            $queryBuilder->orderBy($order);
        }

        $query = $this->createQuery();
        $query->statement($queryBuilder->getSql());
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
        $folders = GeneralUtility::trimExplode(',', $folders);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $statement = $queryBuilder
            ->select('file')
            ->from('sys_file_metadata')
            ->where($queryBuilder->expr()->in('folder_uid', $folders))
            ->execute();

        while ($file = $statement->fetch()) {
            $files[] = $file['file'];
        }

        if ($currentRecursive <= $recursiveLimit) {
            $currentRecursive++;

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
            $statement = $queryBuilder
                ->select('uid')
                ->from('tx_ameosfilemanager_domain_model_folder')
                ->where($queryBuilder->expr()->in('uid_parent', $folders))
                ->execute();

            $childs = [];
            while ($folder = $statement->fetch()) {
                $childs[] = $folder['uid'];
            }
            if (!empty($childs)) {
                $files = array_merge(
                    $files,
                    $this->getFilesIdentifiersRecursively(
                        implode(',', $childs),
                        $recursiveLimit,
                        $currentRecursive
                    )
                );
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder
            ->select('sys_file.*')
            ->from('sys_file_metadata')
            ->join(
                'sys_file_metadata', 
                'sys_file',
                'sys_file',
                'sys_file_metadata.file = sys_file.uid'
            )
            ->leftJoin(
                'sys_file_metadata',
                'sys_category_record_mm',
                'sys_category_record_mm',
                'sys_file_metadata.uid = sys_category_record_mm.uid_foreign 
                    AND sys_category_record_mm.tablenames LIKE \'sys_file_metadata\'
                    AND sys_category_record_mm.fieldname LIKE \'categories\''
            )
            ->leftJoin(
                'sys_category_record_mm',
                'sys_category',
                'sys_category',
                'sys_category_record_mm.uid_local = sys_category.uid'
            );

        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $queryBuilder->leftJoin(
                'sys_file_metadata',
                'tx_ameosfilemanager_domain_model_filecontent',
                'filecontent',
                'filecontent.file = sys_file_metadata.file'
            );
        }

        $rootFolder = (!is_null($rootFolder) && is_object($rootFolder)) ? $rootFolder->getUid() : $rootFolder;

        $whereClauses = [];
        if (!is_null($rootFolder) && (int)$rootFolder > 0) {
            $availableFilesIdentifiers = $this->getFilesIdentifiersRecursively($rootFolder, $recursiveLimit);
            if (empty($availableFilesIdentifiers)) {
                $whereClauses[] = $queryBuilder->expr()->eq('sys_file.uid', 0);
            } else {
                $whereClauses[] = $queryBuilder->expr()->in('sys_file.uid', $availableFilesIdentifiers);
            }
        }
        
        if (isset($criterias['keyword']) && $criterias['keyword'] !== '') {
            $arrayKeywords = explode(' ', $criterias['keyword']);
            $arrayCondition = [];

            foreach ($arrayKeywords as $keyword) {
                $whereClauseKeyword = [];
                $keyword = '%' . $queryBuilder->escapeLikeWildcards($keyword) . '%';

                $whereClauseKeyword[] = $queryBuilder->expr()->like('sys_file_metadata.title', $keyword);
                $whereClauseKeyword[] = $queryBuilder->expr()->like('sys_file_metadata.description', $keyword);
                $whereClauseKeyword[] = $queryBuilder->expr()->like('sys_file_metadata.keywords', $keyword);
                $whereClauseKeyword[] = $queryBuilder->expr()->like('sys_file.name', $keyword);
                $whereClauseKeyword[] = $queryBuilder->expr()->like('sys_category.title', $keyword);

                if (FilemanagerUtility::fileContentSearchEnabled()) {
                    $whereClauseKeyword[] = $queryBuilder->expr()->like('filecontent.content', $keyword);
                }
                
                $whereClauseKeyword[] = 'sys_file_metadata.fe_user_id IN (
                    SELECT
                        uid
                    FROM
                        fe_users
                    WHERE
                        deleted = 0
                        AND (
                            name LIKE \'' . $keyword . '\'
                            OR first_name LIKE \'' . $keyword . '\'
                            OR middle_name LIKE \'' . $keyword . '\'
                            OR last_name LIKE \'' . $keyword . '\'
                        )
                    )';

                $whereClauses[] = $queryBuilder->orWhere(...$whereClauseKeyword);
            }
        }

        $queryBuilder->where(...$whereClauses);

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
            $queryBuilder->orderBy($order);
        }
        
        $query = $this->createQuery();
        $query->statement($query->statement($queryBuilder->getSql()));
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
