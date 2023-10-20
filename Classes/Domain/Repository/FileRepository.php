<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Repository;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Enum\Access;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

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
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * find files for a folder
     * @param Folder $folder
     * @return QueryResult
     */
    public function findFilesForFolder(Folder $folder): QueryResult
    {
        if (empty($folder)) {
            return $this->findAll();
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
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
            )
            ->where($queryBuilder->expr()->eq('sys_file_metadata.folder_uid', $folder->getUid()));

        $query = $this->createQuery();
        $query->statement($queryBuilder->getSQL());
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
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
                ->getQueryBuilderForTable(Configuration::FOLDER_TABLENAME);
            $statement = $queryBuilder
                ->select('uid')
                ->from(Configuration::FOLDER_TABLENAME)
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
    public function findBySearchCriterias(
        $criterias,
        $rootFolder = null,
        $pluginNamespace = 'tx_ameosfilemanager_fe_filemanager',
        $recursiveLimit = 0
    ) {
        if (!is_array($criterias) || empty($criterias)) {
            return $this->findAll();
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
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
                Configuration::FILECONTENT_TABLENAME,
                'filecontent',
                'filecontent.file = sys_file_metadata.file'
            );
        }

        $rootFolder = is_object($rootFolder) ? $rootFolder->getUid() : $rootFolder;

        if (isset($criterias['keyword']) && $criterias['keyword'] !== '') {
            $arrayKeywords = explode(' ', $criterias['keyword']);

            foreach ($arrayKeywords as $keyword) {
                $whereClauseKeyword = [];
                $keyword = '\'%' . $queryBuilder->escapeLikeWildcards($keyword) . '%\'';

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
                            name LIKE ' . $keyword . '
                            OR first_name LIKE ' . $keyword . '
                            OR middle_name LIKE ' . $keyword . '
                            OR last_name LIKE ' . $keyword . '
                        )
                    )';
            }
            $queryBuilder->orWhere(...$whereClauseKeyword);
        }

        if ((int)$rootFolder > 0) {
            $availableFilesIdentifiers = $this->getFilesIdentifiersRecursively($rootFolder, $recursiveLimit);
            if (empty($availableFilesIdentifiers)) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('sys_file.uid', 0));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->in('sys_file.uid', $availableFilesIdentifiers));
            }
        }

        return $this->buildQueryWithSorting($queryBuilder, $pluginNamespace)->execute();
    }

    /**
     * return file by uid
     * @param int $fileUid file uid
     * @param string $access
     */
    public function findByUid($fileUid, $access = Access::ACCESS_READ)
    {
        if (empty($fileUid)) {
            return 0;
        }

        $context = GeneralUtility::makeInstance(Context::class);

        // filter by uid
        $where = 'sys_file.uid = ' . (int)$fileUid;

        // check group access
        $column = $access === Access::ACCESS_WRITE ? 'fe_group_write' : 'fe_group_read';
        $userGroups = $context->getPropertyFromAspect('frontend.user', 'groupIds');
        $where .= ' AND (
            ( 
                sys_file_metadata.' . $column . ' = \'\' 
                OR sys_file_metadata.' . $column . ' IS NULL 
                OR sys_file_metadata.' . $column . ' = 0';

        foreach ($userGroups as $userGroup) {
            $where .= ' OR FIND_IN_SET(' . $userGroup . ', sys_file_metadata.' . $column . ')';
        }
        $where .= ')';

        // check owner access
        if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            $ownerAccessField = $access === Access::ACCESS_WRITE ? 'owner_has_write_access' : 'owner_has_read_access';
            $where .= ' OR (
                sys_file_metadata.fe_user_id = ' . (int)$GLOBALS['TSFE']->fe_user->user['uid'] . '
                AND sys_file_metadata.' . $ownerAccessField . ' = 1
            )';
        }

        // clause access right
        $where .= ')';

        $query = $this->createQuery();
        $query->statement(
            '
            SELECT
                distinct sys_file.uid,
                sys_file_metadata.folder_uid,
                sys_file_metadata.uid as metadatauid
            FROM
                sys_file_metadata
                INNER JOIN sys_file ON sys_file_metadata.file=sys_file.uid
            WHERE
                ' . $where . '
            ORDER BY
                metadatauid DESC 
            ',
            []
        );

        return $query->execute()->getFirst();
    }

    protected function buildQueryWithSorting($queryBuilder, $pluginNamespace = '')
    {
        $get = GeneralUtility::_GET($pluginNamespace);
        $availableSorting = [
            'sys_file.name', 'sys_file.creation_date', 'sys_file.modification_date', 'sys_file.size',
            'sys_file.tstamp', 'sys_file.crdate',
            'sys_file_metadata.tstamp', 'sys_file_metadata.crdate', 'sys_file_metadata.description',
            'sys_file_metadata.title', 'sys_file_metadata.categories', 'sys_file_metadata.keywords',
            'fe_users.name', 'fe_users.username', 'fe_users.company',
        ];

        if (isset($get['sort']) && $get['sort'] != '' && in_array($get['sort'], $availableSorting)) {
            $direction = (
                isset($get[Configuration::DIRECTION_ARGUMENT_KEY])
                && $get[Configuration::DIRECTION_ARGUMENT_KEY] != ''
            )
                    ? $get[Configuration::DIRECTION_ARGUMENT_KEY]
                    : 'ASC';
            if ($direction == 'ASC' || $direction == 'DESC') {
                $queryBuilder->orderBy($get['sort'], $direction);
            }
        }

        $query = $this->createQuery();
        $query->statement($queryBuilder->getSQL());
        return $query;
    }
}
