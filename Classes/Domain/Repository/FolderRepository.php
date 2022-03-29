<?php

namespace Ameos\AmeosFilemanager\Domain\Repository;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
    protected $defaultOrderings = [
        'crdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
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
            ->getConnectionForTable(Configuration::FOLDER_TABLENAME)
            ->update(Configuration::FOLDER_TABLENAME, $field_values, ['uid' => $uid]);
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
            ->getConnectionForTable(Configuration::FOLDER_TABLENAME)
            ->delete(
                Configuration::FOLDER_TABLENAME,
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
            ->getConnectionForTable(Configuration::FOLDER_TABLENAME)
            ->insert(Configuration::FOLDER_TABLENAME, $insertArray);
    }

    /**
     * count total files size for a folder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @return int
     */
    public function countFilesizeForFolder($folder)
    {
        if (!$folder) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        return $queryBuilder
            ->addSelectLiteral($queryBuilder->expr()->sum('file.size', 'total_size'))
            ->from('sys_file', 'file')
            ->join('file', 'sys_file_metadata', 'metadata', 'metadata.file = file.uid')
            ->join('metadata', Configuration::FOLDER_TABLENAME, 'folder', 'metadata.folder_uid = folder.uid')
            ->where(
                $queryBuilder->expr()->eq('file.storage', $folder->getStorage()),
                $queryBuilder->expr()->like(
                    'file.identifier',
                    $queryBuilder->createNamedParameter($folder->getIdentifier() . '%')
                )
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * count file for a folder
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @return int
     */
    public function countFilesForFolder($folder)
    {
        if (!$folder) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        return $queryBuilder
            ->count('file.uid')
            ->from('sys_file', 'file')
            ->join('file', 'sys_file_metadata', 'metadata', 'metadata.file = file.uid')
            ->join('metadata', Configuration::FOLDER_TABLENAME, 'folder', 'metadata.folder_uid = folder.uid')
            ->where(
                $queryBuilder->expr()->eq('file.storage', $folder->getStorage()),
                $queryBuilder->expr()->like(
                    'file.identifier',
                    $queryBuilder->createNamedParameter($folder->getIdentifier() . '%')
                )
            )
            ->execute()
            ->fetchColumn(0);
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
            ->getQueryBuilderForTable(Configuration::FOLDER_TABLENAME);

        return $queryBuilder
            ->count('folder.*')
            ->from(Configuration::FOLDER_TABLENAME, 'folder')
            ->where($queryBuilder->expr()->eq('uid_parent', (int)$folderUid))
            ->execute()
            ->fetch();
    }

    public function getSubFolderFromFolder($folderUid)
    {
        if (empty($folderUid)) {
            return 0;
        }
        $query = $this->createQuery();
        $where = 'tx_ameosfilemanager_domain_model_folder.uid_parent = ' . (int)$folderUid;
        $where .= $this->getModifiedEnabledFields();

        // Sort folders
        if (
            isset($GLOBALS['TSFE']->register['tx_ameosfilemanager'])
            && is_array($GLOBALS['TSFE']->register['tx_ameosfilemanager'])
            && array_key_exists('pluginNamespace', $GLOBALS['TSFE']->register['tx_ameosfilemanager'])
        ) {
            $get = GeneralUtility::_GET($GLOBALS['TSFE']->register['tx_ameosfilemanager']['pluginNamespace']);
            if (isset($get['sort'])) {
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
                if (array_key_exists($get['sort'], $availableSorting)) {
                    $sorting = $availableSorting[$get['sort']];
                }
                if (
                    isset($get[Configuration::DIRECTION_ARGUMENT_KEY])
                    && in_array($get[Configuration::DIRECTION_ARGUMENT_KEY], ['ASC', 'DESC'])
                ) {
                    $direction = $get[Configuration::DIRECTION_ARGUMENT_KEY];
                }
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
            ->enableFields(Configuration::FOLDER_TABLENAME, 0, ['disabled' => 1]);
        $enableFieldsWithoutFeGroup = $pageRepository
            ->enableFields(Configuration::FOLDER_TABLENAME, 0, ['fe_group' => 1]);

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

    public function findByUid($folderUid, $accessMode = 'read')
    {
        if (empty($folderUid)) {
            return 0;
        }
        // if write mode is set, we change the fegroup enablecolumns value to match the write column in the bdd
        switch ($accessMode) {
            case 'read':
                $GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder']['ctrl']['enablecolumns']['fe_group']
                    = 'fe_group_read';
                break;
            case 'write':
                $GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder']['ctrl']['enablecolumns']['fe_group']
                    = 'fe_group_write';
                break;
            case 'addfile':
                $GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder']['ctrl']['enablecolumns']['fe_group']
                    = 'fe_group_addfile';
                break;
            case 'addfolder':
                $GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder']['ctrl']['enablecolumns']['fe_group']
                    = 'fe_group_addfolder';
                break;
            default:
                break;
        }

        $writeMode = $accessMode == 'read' ? false : true;
        $query = $this->createQuery();
        $where = 'tx_ameosfilemanager_domain_model_folder.uid = ' . (int)$folderUid;
        $where .= $this->getModifiedEnabledFields($writeMode);
        $query->statement('SELECT * FROM tx_ameosfilemanager_domain_model_folder WHERE ' . $where, []);

        // Don't forget to change back to read right once the deed is done
        $GLOBALS['TCA'][Configuration::FOLDER_TABLENAME]['ctrl']['enablecolumns']['fe_group']
            = 'fe_group_read';

        return $query->execute()->getFirst();
    }

    public function findRawByStorageAndIdentifier($storage, $identifier)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Configuration::FOLDER_TABLENAME);

        $identifier = '/' . trim($identifier, '/') . '/';
        return $queryBuilder
            ->select('*')
            ->from(Configuration::FOLDER_TABLENAME)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage)),
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier))
            )
            ->execute()
            ->fetch();
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
