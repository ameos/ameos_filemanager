<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
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

class ext_update
{

    /**
     * Main function, returning the HTML content of the module
     * @return string HTML
     */
    public function main()
    {
        if (!GeneralUtility::_GP('do_initialize')) {
            $content = $this->displayWarning();
            $onClick = 'document.location="' . GeneralUtility::linkThisScript(array('do_initialize'=>1)) . '"; return false;';
            $content .= htmlspecialchars($GLOBALS['LANG']->getLL('update_convert_now')) . '
                <br /><br />
                <form action=""><input type="submit" value="' . LocalizationUtility::translate('doInitialize', 'ameos_filemanager') . '" onclick="' . htmlspecialchars($onClick) . '"></form>';
        } else {
            $this->countFolder = 0;
            $this->countAddedFolder = 0;
            
            $updated = $this->initializeDatabase();
            $content .= $updated.'<br/>'.LocalizationUtility::translate('initializeCountFolder', 'ameos_filemanager').' : '.$this->countFolder. ' - ' . $this->countAddedFolder .'<br/>'.LocalizationUtility::translate('adviceInitialize', 'ameos_filemanager');

        }
        return $content;
    }

    /**
     * Checks if extension is loaoed
     * @return boolean true if user have access, otherwise false
     */
    public function access()
    {
        // We cannot update before the extension is installed: required tables are not yet in TCA
        return ExtensionManagementUtility::isLoaded('ameos_filemanager');
    }

    /**
     * Go through the DB and initialize the basic settings to use the extension. 
     * @return string indication about the success or failure of the task
     */
    protected function initializeDatabase()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $storageRepository = $this->objectManager->get(StorageRepository::class);
        $storages = $storageRepository->findAll();
        
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_ameosfilemanager_domain_model_folder');
        $connection->executeQuery(
            'DELETE FROM tx_ameosfilemanager_domain_model_folder WHERE identifier = \'\' OR identifier IS NULL'
        );
        $connection->executeQuery(
            'UPDATE tx_ameosfilemanager_domain_model_folder SET deleted = 1'
        );
        
        foreach ($storages as $storage) {
            $this->currentStorage = $storage;
            $storagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $storage->getConfiguration()['basePath'];
            $this->setDatabaseForFolder(substr($storagePath, 0,-1), $storagePath, 0, $storage->getUid());
        }
        return LocalizationUtility::translate('initializeSuccess', 'ameos_filemanager');
    }

    /**
     *
     * Display the description of the task
     * @return string
     */
    protected function displayWarning()
    {
        $out = '
            <div style="padding:15px 15px 20px 0;">
                <div class="typo3-message message-warning">
                        <div class="message-header">' . LocalizationUtility::translate('warning', 'ameos_filemanager') . '</div>
                        <div class="message-body">' . LocalizationUtility::translate('warningInitialize', 'ameos_filemanager') . '</div>
                    </div>
                </div>
            </div>';

        return $out;
    }

    /**
     *
     * Parse a folder and add the necessary folder/file into the database
     *
     * @param string $storageFolderPath storage folder path
     * @param Ameos\AmeosFilemanager\Domain\Model\Folder $folder the folder currently in treatment
     * @param int $uidParent his parent's uid
     * @param int $storage storage uid
     */
    protected function setDatabaseForFolder($storageFolderPath, $folder, $uidParent = 0, $storage)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $folderConnection = $connectionPool->getConnectionForTable('tx_ameosfilemanager_domain_model_folder');        
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
        $queryBuilder->getRestrictions()->removeAll();

        $files = [];
        $this->countFolder++;
        if ($handle = opendir($folder)) {
            while (($entry = readdir($handle)) !== false) {
                if ($entry != '.' && $entry != '..') {
                    if (is_dir($folder . $entry)) {
                        $identifier = '/' . trim(str_replace($storageFolderPath, '', $folder . $entry), '/') . '/';

                        $qb = clone $queryBuilder;
                        $res = $qb->select('uid', 'storage')
                            ->from('tx_ameosfilemanager_domain_model_folder')
                            ->where(
                                $qb->expr()->eq('storage', $qb->createNamedParameter((int)$storage, \PDO::PARAM_INT)),
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
                                ->set('deleted', $storage)
                                ->execute();
                        }

                        if (!$exist) {
                            $this->countAddedFolder ++;

                            $insertQb = clone $queryBuilder;
                            $insertQb->insert('tx_ameosfilemanager_domain_model_folder')
                                ->values([
                                    'title'      => $entry,
                                    'pid'        => 0,
                                    'cruser_id'  => $GLOBALS['BE_USER']->user['uid'],
                                    'uid_parent' => $uidParent,
                                    'crdate'     => time(),
                                    'tstamp'     => time(),
                                    'deleted'    => 0,
                                    'hidden'     => 0,
                                    'identifier' => $identifier,
                                    'storage'    => $storage,
                                    'fe_group_read'      => '',
                                    'fe_group_write'     => '',
                                    'fe_group_addfile'   => '',
                                    'fe_group_addfolder' => '',
                                ])
                                ->execute();

                            $uid = $folderConnection->lastInsertId('tx_ameosfilemanager_domain_model_folder');
                            $this->countFolder++;                            
                        }
                        $this->setDatabaseForFolder($storageFolderPath, $folder . $entry . '/', $uid, $storage);
                    } else {                        
                        $this->setDatabaseForFile($storageFolderPath, $folder, $entry, $uidParent);
                    }
                }
            }
            closedir($handle);

            $currentFolderIdentifier = '/' . trim(str_replace($storageFolderPath, '', $folder), '/') . '/';
            $qb = clone $queryBuilder;
            $qb->select('uid')
                ->from('tx_ameosfilemanager_domain_model_folder')
                ->where(
                    $qb->expr()->eq('storage', $qb->createNamedParameter((int)$storage, \PDO::PARAM_INT)),
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
                            $qbfile->createNamedParameter((int)$storage, \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
               if ($file = $result->fetch()) {
                    if (!file_exists($storageFolderPath . $file['identifier'])) {
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
    protected function setDatabaseForFile($storageFolderPath, $folderPath, $entry, $folderParentUid)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $fileConnection = $connectionPool->getConnectionForTable('sys_file');
        $metaConnection = $connectionPool->getConnectionForTable('sys_file_metadata');
        $fileQueryBuilder = $connectionPool->getQueryBuilderForTable('sys_file');

        $filePath = $folderPath . $entry;
        $fileIdentifier = str_replace($storageFolderPath, '', $filePath);
        $infoFile = $this->currentStorage->getFileInfoByIdentifier($fileIdentifier, array());

        // Add file into sys_file if it doesn't exist
        $file = $fileQueryBuilder->select('uid')
            ->from('sys_file')
            ->where(
                $fileQueryBuilder->expr()->like('identifier', $fileQueryBuilder->createNamedParameter($fileIdentifier))
            )
            ->execute()
            ->fetch();

        if ($file) {
            $fileUid = $file['uid'];

            // adding metadatas.
            $metaConnection->update(
                'sys_file_metadata',
                ['folder_uid' => $folderParentUid],
                ['file' => $fileUid]
            );

        } else {
            $fileConnection->insert('sys_file', [
                'pid'               => 0,
                'tstamp'            => time(),
                'storage'           => $infoFile['storage'],
                'identifier'        => $infoFile['identifier'],
                'identifier_hash'   => $infoFile['identifier_hash'],
                'folder_hash'       => $infoFile['folder_hash'],
                'extension'         => pathinfo($filePath)['extension'] ?: '',
                'mime_type'         => $infoFile['mimetype'],
                'name'              => $entry,
                'size'              => $infoFile['size'],
                'creation_date'     => $infoFile['ctime'],
                'modification_date' => $infoFile['mtime']
            ]);

            $fileUid = $fileConnection->lastInsertId('sys_file');

            $metaConnection->insert('sys_file_metadata', [
                'pid'    => 0,
                'tstamp' => time(),
                'crdate' => time(),
                'file'   => $fileUid,
                'fe_group_read'  => '',
                'fe_group_write' => '',
            ]);
        }
    }
}
