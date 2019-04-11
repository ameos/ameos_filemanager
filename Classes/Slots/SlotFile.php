<?php
namespace Ameos\AmeosFilemanager\Slots;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

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
 
class SlotFile
{
    /**
     * Call after file addition in filelist
     * Add the file to the correct folder in the database
     * @param File $file 
     * @param Folder $targetFolder
     * @return void
     */
    public function add($file, $targetFolder)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionPool
            ->getConnectionForTable('sys_file_metadata')
            ->update(
                'sys_file_metadata',
                ['folder_uid' => $folderRecord['uid']],
                ['file' => $file->getUid() ]
            );

        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
            try {
                $textExtractor = $textExtractorRegistry->getTextExtractor($file);
                if (!is_null($textExtractor)) {
                    $fileContent = $connectionPool
                        ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                        ->select(
                            ['file'],
                            'tx_ameosfilemanager_domain_model_filecontent',
                            ['file' => $file->getUid()]
                        )
                        ->fetch();

                    if ($fileContent) {
                        $connectionPool
                            ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                            ->update(
                                'tx_ameosfilemanager_domain_model_filecontent', 
                                ['content' => $textExtractor->extractText($file)],
                                ['file'    => $file->getUid()]                                
                            );
                    } else {
                        $connectionPool
                            ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                            ->insert('tx_ameosfilemanager_domain_model_filecontent', [
                                'file'    => $file->getUid(),
                                'content' => $textExtractor->extractText($file)
                            ]);
                    }
                }    
            } catch (\Exception $e) {
                
            }
        }
    }

    /**
     * Call after file copy in filelist
     * Copy the file to the correct folder in the database
     * @param File $file 
     * @param Folder $targetFolder
     * @return void
     */
    public function copy($file, $targetFolder)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionPool
            ->getConnectionForTable('sys_file_metadata')
            ->update(
                'sys_file_metadata',
                ['folder_uid' => $folderRecord['uid']],
                ['file' => $file->getUid() ]
            );

        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
            try {
                $textExtractor = $textExtractorRegistry->getTextExtractor($file);
                if (!is_null($textExtractor)) {
                    $connectionPool
                        ->getConnectionForTable('tx_ameosfilemanager_domain_model_filecontent')
                        ->insert('tx_ameosfilemanager_domain_model_filecontent', [
                            'file'    => $file->getUid(),
                            'content' => $textExtractor->extractText($file)
                        ]);
                }    
            } catch (\Exception $e) {
                
            }
        }
    }

    /**
     * Call after file move in filelist
     * Move the file to the correct folder in the database
     * @param File $file 
     * @param Folder $targetFolder
     * @return void
     */
    public function move($file, $targetFolder)
    {
        $folderRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FolderRepository::class);
        $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata')
            ->update(
                'sys_file_metadata',
                ['folder_uid' => $folderRecord['uid']],
                ['file' => $file->getUid()]
            );
    }
}
