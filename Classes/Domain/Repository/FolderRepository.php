<?php
namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

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

class FolderRepository extends Repository
{

    protected $defaultOrderings = array(
        'crdate' => QueryInterface::ORDER_DESCENDING
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
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_ameosfilemanager_domain_model_folder')
            ->update('tx_ameosfilemanager_domain_model_folder', $field_values, ['uid' => $uid]);
    }

    /**
     * Delete a folder and all of it's content
     * @param integer $uid folder uid
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
            ->getConnectionForTable('tx_ameosfilemanager_domain_model_folder')
            ->delete(
                'tx_ameosfilemanager_domain_model_folder',
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
            ->getConnectionForTable('tx_ameosfilemanager_domain_model_folder')
            ->insert('tx_ameosfilemanager_domain_model_folder', $insertArray);
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        return $queryBuilder
            ->addSelectLiteral($queryBuilder->expr()->sum('file.size', 'total_size'))
            ->from('sys_file', 'file')
            ->join('file', 'sys_file_metadata', 'metadata', 'metadata.file = file.uid')
            ->join('metadata', 'tx_ameosfilemanager_domain_model_folder', 'folder', 'metadata.folder_uid = folder.uid')
            ->where(
                $queryBuilder->expr()->eq('file.storage', $folder->getStorage()),
                $queryBuilder->expr()->like('file.identifier', $queryBuilder->createNamedParameter($folder->getIdentifier() . '%'))
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        return $queryBuilder
            ->count('file.uid')
            ->from('sys_file', 'file')
            ->join('file', 'sys_file_metadata', 'metadata', 'metadata.file = file.uid')
            ->join('metadata', 'tx_ameosfilemanager_domain_model_folder', 'folder', 'metadata.folder_uid = folder.uid')
            ->where(
                $queryBuilder->expr()->eq('file.storage', $folder->getStorage()),
                $queryBuilder->expr()->like('file.identifier', $queryBuilder->createNamedParameter($folder->getIdentifier() . '%'))
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
            ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');

        return $queryBuilder
            ->count('folder.*')
            ->from('tx_ameosfilemanager_domain_model_folder', 'folder')
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
        $query->statement
        (    '  SELECT tx_ameosfilemanager_domain_model_folder.*
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
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $enableFieldsWithFeGroup = $pageRepository->enableFields('tx_ameosfilemanager_domain_model_folder', 0, ['disabled' => 1]);
        $enableFieldsWithoutFeGroup = $pageRepository->enableFields('tx_ameosfilemanager_domain_model_folder', 0, ['fe_group' => 1]);

        $ownerOnlyField   = $writeMode ? 'no_write_access' : 'no_read_access';
        $ownerAccessField = $writeMode ? 'owner_has_write_access' : 'owner_has_read_access';

        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
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

        $writeMode = !($accessMode == 'read');
        $query = $this->createQuery();
        $where = 'tx_ameosfilemanager_domain_model_folder.uid = ' . (int)$folderUid;
        $where .= $this->getModifiedEnabledFields($writeMode);
        $query->statement('SELECT * FROM tx_ameosfilemanager_domain_model_folder WHERE ' . $where, []);

        // Don't forget to change back to read right once the deed is done
        $GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder']['ctrl']['enablecolumns']['fe_group'] = 'fe_group_read';

        return $query->execute()->getFirst();
    }

    public function findRawByStorageAndIdentifier($storage, $identifier)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');

        $identifier = '/' . trim($identifier, '/') . '/';
        return $queryBuilder
            ->select('*')
            ->from('tx_ameosfilemanager_domain_model_folder')
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage)),
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier))
            )
            ->execute()
            ->fetch();
    }
}
