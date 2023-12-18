<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Repository;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Enum\Configuration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class FolderRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    protected $defaultOrderings = [
        'crdate' => QueryInterface::ORDER_DESCENDING,
    ];

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
     * Update the folder
     * @param int $uid uid of the folder
     * @param array $field_values values to update
     */
    public function requestUpdate($uid, $field_values)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Configuration::TABLENAME_FOLDER)
            ->update(Configuration::TABLENAME_FOLDER, $field_values, ['uid' => $uid]);
    }

    /**
     * Delete a folder and all of it's content
     * @param int $uid folder uid
     */
    public function requestDelete($uid)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata')
            ->delete(
                'sys_file_metadata',
                ['folder_uid' => (int)$uid]
            );

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Configuration::TABLENAME_FOLDER)
            ->delete(
                Configuration::TABLENAME_FOLDER,
                ['uid' => (int)$uid]
            );
    }

    /**
     * insert new Folder
     * @param array $insertArray values of the folder
     */
    public function requestInsert($insertArray)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Configuration::TABLENAME_FOLDER)
            ->insert(Configuration::TABLENAME_FOLDER, $insertArray);
    }

    /**
     * count total files size for a folder
     * @param Folder $folder
     * @return int
     */
    public function countFilesizeForFolder(Folder $folder): int
    {
        if (!$folder) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder
            ->addSelectLiteral($queryBuilder->expr()->sum('file.size', 'total_size'))
            ->from('sys_file', 'file')
            ->join('file', 'sys_file_metadata', 'metadata', 'metadata.file = file.uid')
            ->join('metadata', Configuration::TABLENAME_FOLDER, 'folder', 'metadata.folder_uid = folder.uid')
            ->where(
                $queryBuilder->expr()->eq('file.storage', $folder->getStorage()),
                $queryBuilder->expr()->like(
                    'file.identifier',
                    $queryBuilder->createNamedParameter($folder->getIdentifier() . '%')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * count file for a folder
     * @param Folder $folder
     * @return int
     */
    public function countFilesForFolder(Folder $folder): int
    {
        if (!$folder) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder
            ->count('file.uid')
            ->from('sys_file', 'file')
            ->join('file', 'sys_file_metadata', 'metadata', 'metadata.file = file.uid')
            ->join('metadata', Configuration::TABLENAME_FOLDER, 'folder', 'metadata.folder_uid = folder.uid')
            ->where(
                $queryBuilder->expr()->eq('file.storage', $folder->getStorage()),
                $queryBuilder->expr()->like(
                    'file.identifier',
                    $queryBuilder->createNamedParameter($folder->getIdentifier() . '%')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * count file for a folder
     * @param int $folderUid
     * @param bool $withArchive
     * @return int
     */
    public function countFoldersForFolder($folderUid)
    {
        if (empty($folderUid)) {
            return 0;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);

        return $queryBuilder
            ->count('folder.*')
            ->from(Configuration::TABLENAME_FOLDER, 'folder')
            ->where($queryBuilder->expr()->eq('uid_parent', (int)$folderUid))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * find subfolders
     *
     * @param Folder $folder
     * @param string $sort
     * @param string $direction
     * @return QueryResult
     */
    public function findSubFolders(Folder $folder, string $sort = 'sys_file.name', string $direction = 'ASC'): QueryResult
    {
        /** @var Query */
        $query = $this->createQuery();
        $where = 'tx_ameosfilemanager_domain_model_folder.uid_parent = ' . (int)$folder->getUid();
        $where .= $this->getModifiedEnabledFields();

        $sorting = null;
        if ($sort) {
            $availableSorting = [
                'sys_file.name' => 'title',
                'sys_file.creation_date' => 'crdate',
                'sys_file.modification_date' => 'tstamp',
                'sys_file.tstamp' => 'tstamp',
                'sys_file.crdate' => 'crdate',
                'sys_file_metadata.tstamp' => 'tstamp',
                'sys_file_metadata.crdate' => 'crdate',
                'sys_file_metadata.description' => 'description',
                'sys_file_metadata.title' => 'title',
                'sys_file_metadata.categories' => 'categories',
                'sys_file_metadata.keywords' => 'keywords',
                'fe_users.name' => 'fe_users.name',
                'fe_users.username' => 'fe_users.username',
                'fe_users.company' => 'fe_users.company',
            ];
            if (array_key_exists($sort, $availableSorting)) {
                $sorting = $availableSorting[$sort];
            }
        }

        if (isset($sorting)) {
            $query->statement($this->buildSubfolderStatement($where, $sorting, $direction), []);
        } else {
            $query->statement(
                '  SELECT tx_ameosfilemanager_domain_model_folder.* 
                    FROM tx_ameosfilemanager_domain_model_folder 
                    WHERE ' . $where . ' 
                    ORDER BY tx_ameosfilemanager_domain_model_folder.title ASC 
                ',
                []
            );
        }
        return $query->execute();
    }

    public function getModifiedEnabledFields($writeMode = false)
    {
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Domain\Repository\PageRepository::class);
        $enableFieldsWithFeGroup = $pageRepository
            ->enableFields(Configuration::TABLENAME_FOLDER, 0, ['disabled' => 1]);
        $enableFieldsWithoutFeGroup = $pageRepository
            ->enableFields(Configuration::TABLENAME_FOLDER, 0, ['fe_group' => 1]);

        $ownerOnlyField = $writeMode ? 'no_write_access' : 'no_read_access';
        $ownerAccessField = $writeMode ? 'owner_has_write_access' : 'owner_has_read_access';

        $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
        if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            $feUserUid = $GLOBALS['TSFE']->fe_user->user['uid'];
            $where = ' AND (';
            $where .= '(1 ' . $enableFieldsWithoutFeGroup . ')'; // classic enable fields

            // open clause access right
            $where .= ' AND (';

            // available for all (owner only field = 0)
            $where .=  '(' . $ownerOnlyField . ' = 0 ' // owner only field = 0
                    . ' AND (' // and
                        . '(1 ' . $enableFieldsWithFeGroup . ')' // group access
                        . ' OR (' // is owner
                            . 'tx_ameosfilemanager_domain_model_folder.fe_user_id = ' . $feUserUid
                            . ' AND tx_ameosfilemanager_domain_model_folder.' . $ownerAccessField . ' = 1
                        )
                    )
                )';

            // available only owner (owner only field = 1)
            $where .=  ' OR ('
                    . $ownerOnlyField . ' = 1 ' // owner only field = 0
                    . ' AND tx_ameosfilemanager_domain_model_folder.fe_user_id = ' . $feUserUid // user is the owner
                    . ' AND (' // and
                        . '(1 ' . $enableFieldsWithFeGroup . ')'  // group access
                        . ' OR tx_ameosfilemanager_domain_model_folder.fe_user_id = ' . $feUserUid // owner has access
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

    public function findRawByStorageAndIdentifier($storage, $identifier)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);

        $identifier = '/' . trim($identifier, '/') . '/';
        return $queryBuilder
            ->select('*')
            ->from(Configuration::TABLENAME_FOLDER)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage)),
                $queryBuilder->expr()->like('identifier', $queryBuilder->createNamedParameter($identifier))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function findOneRawByStorageAndIdentifier($storage, $identifier)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);

        $identifier = '/' . trim($identifier, '/') . '/';
        return $queryBuilder
            ->select('*')
            ->from(Configuration::TABLENAME_FOLDER)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage)),
                $queryBuilder->expr()->like('identifier', $queryBuilder->createNamedParameter($identifier))
            )
            ->executeQuery()
            ->fetchAssociative();
    }

    private function buildSubfolderStatement($where, $sorting, $direction)
    {
        $select = 'SELECT DISTINCT tx_ameosfilemanager_domain_model_folder.*';
        $from = 'FROM tx_ameosfilemanager_domain_model_folder';
        if (preg_match('/\./', $sorting)) {
            $orderField = explode('.', $sorting)[1];
            $from .= '
                LEFT JOIN fe_users ON fe_users.uid = tx_ameosfilemanager_domain_model_folder.fe_user_id
            ';
            $ordering = 'ORDER BY fe_users.' . $orderField . ' ' . $direction;
        } elseif ($sorting == 'categories') {
            $from .= '
                LEFT JOIN sys_category_record_mm ON tx_ameosfilemanager_domain_model_folder.uid = sys_category_record_mm.uid_foreign 
                    AND sys_category_record_mm.tablenames = \'tx_ameosfilemanager_domain_model_folder\'
                    AND sys_category_record_mm.fieldname = \'cats\'
                LEFT JOIN sys_category ON sys_category_record_mm.uid_local = sys_category.uid
            ';
            $ordering = 'ORDER BY sys_category.title ' . $direction;
        } else {
            $ordering = 'ORDER BY tx_ameosfilemanager_domain_model_folder.' . $sorting . ' ' . $direction;
        }
        return $select . ' ' . $from . ' WHERE ' . $where . ' ' . $ordering;
    }
}
