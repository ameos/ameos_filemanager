<?php

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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

class IndexationService
{
    public static function runForDefaultStorage()
    {
        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage();
        if ($storage) {
            GeneralUtility::makeInstance(IndexationService::class)->run($storage);
        }
    }

    /**
     * run indexation for a storage
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage storage
     */
    public function run($storage)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Configuration::FOLDER_TABLENAME)
            ->update(
                Configuration::FOLDER_TABLENAME,
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
        $queryBuilder = $connectionPool->getQueryBuilderForTable(Configuration::FOLDER_TABLENAME);
        $queryBuilder->getRestrictions()->removeAll();

        if ($handle = opendir($folder)) {
            while (($entry = readdir($handle)) !== false) {
                if (!in_array($entry, ['.', '..'])) {
                    if (is_dir($folder . $entry)) {
                        $identifier = '/' . trim(str_replace($this->getStorageRootpath($storage), '', $folder . $entry), '/') . '/';
                        $qb = clone $queryBuilder;
                        $uid = $qb->select('uid', 'storage')
                            ->from(Configuration::FOLDER_TABLENAME)
                            ->where(
                                $qb->expr()->eq(
                                    'storage',
                                    $qb->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)
                                ),
                                $qb->expr()->like('identifier', $qb->createNamedParameter($identifier))
                            )
                            ->execute()
                            ->fetchColumn(0);

                        $uid = $this->unDeleteOrInsertFolder(
                            $uid,
                            $entry,
                            $uidParent,
                            $identifier,
                            $storage,
                            clone $queryBuilder
                        );
                        $this->indexFolder($storage, $folder . $entry . '/', $uid);
                    } else {
                        $this->indexFile($storage, $folder, $entry, $uidParent);
                    }
                }
            }
            closedir($handle);

            $currentFolderIdentifier = '/' . trim(str_replace($this->getStorageRootpath($storage), '', $folder), '/') . '/';
            $qb = clone $queryBuilder;
            $currentFolderRecord = $qb->select('uid')
                ->from(Configuration::FOLDER_TABLENAME)
                ->where(
                    $qb->expr()->eq('storage', $qb->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)),
                    $qb->expr()->like('identifier', $qb->createNamedParameter($currentFolderIdentifier))
                )
                ->execute()
                ->fetch();

            if ($currentFolderRecord) {
                $this->unIndexDeletedFiles($currentFolderRecord, $storage, $connectionPool);
            }
        }
    }

    /**
     * add file into the database
     */
    protected function indexFile($storage, $folderPath, $entry, $folderParentUid)
    {
        // Ignore processed and temp files
        if (!preg_match('/\/\_(processed|temp)\_\//i', $folderPath)) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $metaConnection = $connectionPool->getConnectionForTable('sys_file_metadata');

            $filePath = $folderPath . $entry;
            $fileIdentifier = '/' . str_replace($this->getStorageRootpath($storage), '', $filePath);

            $file = $storage->getFile($fileIdentifier);
            if ($file) {
                $meta = $metaConnection
                    ->select(['uid'], 'sys_file_metadata', ['file' => (int)$file->getUid()])
                    ->fetch();
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
    }

    private function unDeleteOrInsertFolder($uid, $entry, $uidParent, $identifier, $storage, $queryBuilder)
    {
        if (empty($uid)) {
            $queryBuilder->insert(Configuration::FOLDER_TABLENAME)
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

            $uid = $queryBuilder->getConnection()->lastInsertId(Configuration::FOLDER_TABLENAME);
        } else {
            $queryBuilder->update(Configuration::FOLDER_TABLENAME)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)
                    )
                )
                ->set('deleted', 0)
                ->set('uid_parent', $uidParent)
                ->set('storage', $storage->getUid())
                ->execute();
        }
        return $uid;
    }

    private function unIndexDeletedFiles($currentFolderRecord, $storage, $connectionPool)
    {
        $qbfile = $connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $qbfile->getRestrictions()->removeAll();

        $result = $qbfile->select('meta.uid', 'meta.file', 'file.identifier')
            ->from('sys_file_metadata', 'meta')
            ->join(
                'meta',
                'sys_file',
                'file',
                $qbfile->expr()->eq('file.uid', $qbfile->quoteIdentifier('meta.file'))
            )
            ->where(
                $qbfile->expr()->eq(
                    'meta.folder_uid',
                    $qbfile->createNamedParameter((int)$currentFolderRecord['uid'], \PDO::PARAM_INT)
                ),
                $qbfile->expr()->eq(
                    'file.storage',
                    $qbfile->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)
                )
            )
            ->execute();
        if (
            $file = $result->fetch()
            && !file_exists($this->getStorageRootpath($storage) . $file['identifier'])
        ) {
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

    /**
     * return storage root path
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage
     * @return string
     */
    protected function getStorageRootpath($storage)
    {
        if ($storage->getConfiguration()['pathType'] == 'relative') {
            $root = php_sapi_name() == 'cli'
                ? realpath(__DIR__ . '/../../../../../')
                : realpath($_SERVER['DOCUMENT_ROOT']);

            return $root . '/' . $storage->getConfiguration()['basePath'];
        }
        return $storage->getConfiguration()['basePath'];
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
