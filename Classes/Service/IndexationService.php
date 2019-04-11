<?php
namespace Ameos\AmeosFilemanager\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
 
class IndexationService
{
    public static function runForDefaultStorage()
    {
        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage();
        GeneralUtility::makeInstance(IndexationService::class)->run($storage);
    }

    /**
     * run indexation for a storage
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage storage
     */
    public function run($storage)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_ameosfilemanager_domain_model_folder')
            ->update(
                'tx_ameosfilemanager_domain_model_folder',
                ['deleted' => '1'],
                ['storage' => $storage->getUid()]
            );
        
        $this->indexFolder($storage, $this->getStorageRootpath($storage), 0);
    }

    /**
     * Parse a folder and add the necessary folder/file into the database
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage storage
     * @param string $folder the folder currently in treatment
     * @param int $uidParent his parent's uid
     */
    protected function indexFolder($storage, $folder, $uidParent = 0)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $folderConnection = $connectionPool->getConnectionForTable('tx_ameosfilemanager_domain_model_folder');        
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
        $queryBuilder->getRestrictions()->removeAll();

        $files = [];
        if ($handle = opendir($folder)) {
            while (($entry = readdir($handle)) !== false) {
                if ($entry != '.' && $entry != '..') {
                    if (is_dir($folder . $entry)) {
                        $identifier = '/' . trim(str_replace($this->getStorageRootpath($storage), '', $folder . $entry), '/') . '/';

                        $qb = clone $queryBuilder;
                        $res = $qb->select('uid', 'storage')
                            ->from('tx_ameosfilemanager_domain_model_folder')
                            ->where(
                                $qb->expr()->eq('storage', $qb->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)),
                                $qb->expr()->like('identifier', $qb->createNamedParameter($identifier))
                            )
                            ->execute();
                        $exist = false;
                        if ($row = $res->fetch()) {
                            $exist = true;
                            $uid = $row['uid'];

                            $updateQb = clone $queryBuilder;
                            $updateQb->update('tx_ameosfilemanager_domain_model_folder')
                                ->where(
                                    $updateQb->expr()->eq('uid', $updateQb->createNamedParameter((int)$uid, \PDO::PARAM_INT))
                                )
                                ->set('deleted', 0)
                                ->set('uid_parent', $uidParent)
                                ->set('storage', $storage->getUid())
                                ->execute();
                        }

                        if (!$exist) {
                            $insertQb = clone $queryBuilder;
                            $insertQb->insert('tx_ameosfilemanager_domain_model_folder')
                                ->values([
                                    'title'      => $entry,
                                    'pid'        => 0,
                                    'cruser_id'  => $GLOBALS['BE_USER']->user['uid'] ?: 0,
                                    'uid_parent' => $uidParent,
                                    'crdate'     => time(),
                                    'tstamp'     => time(),
                                    'deleted'    => 0,
                                    'hidden'     => 0,
                                    'identifier' => $identifier,
                                    'storage'    => $storage->getUid(),
                                    'fe_group_read'      => '',
                                    'fe_group_write'     => '',
                                    'fe_group_addfile'   => '',
                                    'fe_group_addfolder' => '',
                                ])
                                ->execute();

                            $uid = $folderConnection->lastInsertId('tx_ameosfilemanager_domain_model_folder');
                        }
                        $this->indexFolder($storage, $folder . $entry . '/', $uid);
                    } else {
                        $this->indexFile($storage, $folder, $entry, $uidParent);
                    }
                }
            }
            closedir($handle);

            $currentFolderIdentifier = '/' . trim(str_replace($this->getStorageRootpath($storage), '', $folder), '/') . '/';
            $qb = clone $queryBuilder;
            $qb->select('uid')
                ->from('tx_ameosfilemanager_domain_model_folder')
                ->where(
                    $qb->expr()->eq('storage', $qb->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)),
                    $qb->expr()->like('identifier', $qb->createNamedParameter($identifier))
                )
                ->execute()
                ->fetch();

            if ($currentFolderRecord) {
                $qbfile = $connectionPool->getQueryBuilderForTable('sys_file_metadata');
                $qbfile->getRestrictions()->removeAll();

                $result = $qbfile->select('meta.uid', 'meta.file', 'file.identifier')
                    ->from('sys_file_metadata', 'meta')
                    ->join('meta', 'sys_file', 'file',
                        $qbfile->expr()->eq('file.uid', $qbfile->quoteIdentifier('meta.file'))
                    )
                    ->where(
                        $qbfile->expr()->eq(
                            'meta.folder_uid', 
                            $qbfile->createNamedParameter((int)$currentFolderIdentifier['uid'], \PDO::PARAM_INT)
                        ),
                        $qbfile->expr()->eq(
                            'file.storage', 
                            $qbfile->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
               if ($file = $result->fetch()) {
                    if (!file_exists($this->getStorageRootpath($storage) . $file['identifier'])) {
                        $qbDelete = $connectionPool->getQueryBuilderForTable('sys_file');
                        $qbDelete->getRestrictions()->removeAll();
                        $qbDelete
                            ->delete('sys_file')
                            ->where($qbDelete->expr()->eq(
                                'uid', 
                                $qbDelete->createNamedParameter((int)$file['file'], \PDO::PARAM_INT)
                            ))
                            ->execute();

                        $qbDelete = $connectionPool->getQueryBuilderForTable('sys_file_metadata');
                        $qbDelete->getRestrictions()->removeAll();
                        $qbDelete
                            ->delete('sys_file_metadata')
                            ->where($qbDelete->expr()->eq(
                                'uid', 
                                $qbDelete->createNamedParameter((int)$file['uid'], \PDO::PARAM_INT)
                            ))
                            ->execute();
                    }
                }
            }
        }
    }

    /**
     *
     * add file into the database
     */
    protected function indexFile($storage, $folderPath, $entry, $folderParentUid)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $fileConnection = $connectionPool->getConnectionForTable('sys_file');
        $metaConnection = $connectionPool->getConnectionForTable('sys_file_metadata');
        $fileQueryBuilder = $connectionPool->getQueryBuilderForTable('sys_file');

        $filePath = $folderPath . $entry;
        $fileIdentifier = '/' . str_replace($this->getStorageRootpath($storage), '', $filePath);
        
        // Add file into sys_file if it doesn't exist
        $file = $storage->getFile($fileIdentifier);
        if ($file) {
            $meta = $metaConnection->select(['uid'], 'sys_file_metadata', ['file' => (int)$file->getUid()])->fetch();
            if (!$meta && !$storage->isWithinProcessingFolder($fileIdentifier)) {
                $this->getIndexer($storage)->extractMetaData($file);
            }

            $metaConnection->update(
                'sys_file_metadata',
                ['folder_uid' => $folderParentUid],
                ['file' => $file->getUid()]
            );
        }
    }

    /**
     * return storage root path
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage
     * @return string
     */
    protected function getStorageRootpath($storage)
    {
        if ($storage->getConfiguration()['pathType'] == 'relative') {
            $root = php_sapi_name() == 'cli'
                ? realpath(dirname(__FILE__) . '/../../../../../')
                : realpath($_SERVER['DOCUMENT_ROOT']);

            return $root . '/' . $storage->getConfiguration()['basePath'];
        } else {
            return $storage->getConfiguration()['basePath'];
        }
    }

    /**
     * Gets the Indexer.
     *
     * @return Index\Indexer
     */
    protected function getIndexer($storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }
}