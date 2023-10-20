<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Enum\Configuration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexationService
{
    /**
     * @var ConnectionPool
     */
    protected ConnectionPool $connectionPool;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

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
     * @param ResourceStorage $storage storage
     */
    public function run($storage)
    {
        $this->connectionPool
            ->getConnectionForTable(Configuration::TABLENAME_FOLDER)
            ->update(
                Configuration::TABLENAME_FOLDER,
                ['deleted' => '1'],
                ['storage' => $storage->getUid()]
            );

        $this->indexFolder($storage, $this->getStorageRootpath($storage), 0);
    }

    /**
     * Parse a folder and add the necessary folder/file into the database
     *
     * @param ResourceStorage $storage storage
     * @param string $folder the folder currently in treatment
     * @param int $uidParent his parent's uid
     */
    protected function indexFolder($storage, $folder, $uidParent = 0)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);
        $queryBuilder->getRestrictions()->removeAll();

        if ($handle = opendir($folder)) {
            while (($entry = readdir($handle)) !== false) {
                if (!in_array($entry, ['.', '..'])) {
                    if (is_dir($folder . $entry)) {
                        $identifier = '/'
                            . trim(str_replace($this->getStorageRootpath($storage), '', $folder . $entry), '/')
                            . '/';
                        $qb = clone $queryBuilder;
                        $uid = $qb->select('uid')
                            ->from(Configuration::TABLENAME_FOLDER)
                            ->where(
                                $qb->expr()->eq(
                                    'storage',
                                    $qb->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)
                                ),
                                $qb->expr()->like('identifier', $qb->createNamedParameter($identifier))
                            )
                            ->executeQuery()
                            ->fetchOne();

                        $uid = $this->unDeleteOrInsertFolder(
                            $uid,
                            $entry,
                            $uidParent,
                            $identifier,
                            $storage
                        );
                        $this->indexFolder($storage, $folder . $entry . '/', $uid);
                    } else {
                        $this->indexFile($storage, $folder, $entry, $uidParent);
                    }
                }
            }
            closedir($handle);

            $currentFolderIdentifier = '/'
                . trim(str_replace($this->getStorageRootpath($storage), '', $folder), '/')
                . '/';
            $qb = clone $queryBuilder;
            $currentFolderRecord = $qb->select('uid')
                ->from(Configuration::TABLENAME_FOLDER)
                ->where(
                    $qb->expr()->eq('storage', $qb->createNamedParameter((int)$storage->getUid(), \PDO::PARAM_INT)),
                    $qb->expr()->like('identifier', $qb->createNamedParameter($currentFolderIdentifier))
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($currentFolderRecord) {
                $this->unIndexDeletedFiles($currentFolderRecord, $storage);
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
            $metaConnection = $this->connectionPool->getConnectionForTable('sys_file_metadata');

            $filePath = $folderPath . $entry;
            $fileIdentifier = '/' . str_replace($this->getStorageRootpath($storage), '', $filePath);

            $file = $storage->getFile($fileIdentifier);
            if ($file) {
                $meta = $metaConnection
                    ->select(['uid'], 'sys_file_metadata', ['file' => (int)$file->getUid()])
                    ->fetchAssociative();
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

    private function unDeleteOrInsertFolder($uid, $entry, $uidParent, $identifier, $storage)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);
        if (empty($uid)) {
            $userId = 0;
            if (isset($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->user['uid'])) {
                $userId = (int)$GLOBALS['BE_USER']->user['uid'];
            }
            $queryBuilder->insert(Configuration::TABLENAME_FOLDER)
                ->values([
                    'title'      => $entry,
                    'pid'        => 0,
                    'cruser_id'  => $userId,
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
                ->executeStatement();

            $uid = $queryBuilder->getConnection()->lastInsertId(Configuration::TABLENAME_FOLDER);
        } else {
            $queryBuilder->update(Configuration::TABLENAME_FOLDER)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)
                    )
                )
                ->set('deleted', 0)
                ->set('uid_parent', $uidParent)
                ->set('storage', $storage->getUid())
                ->executeStatement();
        }
        return $uid;
    }

    private function unIndexDeletedFiles($currentFolderRecord, $storage)
    {
        $qbfile = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $qbfile->getRestrictions()->removeAll();

        $file = $qbfile->select('meta.uid', 'meta.file', 'file.identifier')
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
            ->executeQuery()
            ->fetchAssociative();
        if ($file && !file_exists($this->getStorageRootpath($storage) . $file['identifier'])) {
            $qbDelete = $this->connectionPool->getQueryBuilderForTable('sys_file');
            $qbDelete->getRestrictions()->removeAll();
            $qbDelete
                ->delete('sys_file')
                ->where($qbDelete->expr()->eq(
                    'uid',
                    $qbDelete->createNamedParameter((int)$file['file'], \PDO::PARAM_INT)
                ))
                ->executeStatement();

            $qbDelete = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
            $qbDelete->getRestrictions()->removeAll();
            $qbDelete
                ->delete('sys_file_metadata')
                ->where($qbDelete->expr()->eq(
                    'uid',
                    $qbDelete->createNamedParameter((int)$file['uid'], \PDO::PARAM_INT)
                ))
                ->executeStatement();
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
            return Environment::getPublicPath() . '/' . $storage->getConfiguration()['basePath'];
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
