<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileUtility
{
    /**
     * remove file
     * @param int $fid file id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function remove($fid, $sid, $folderRoot)
    {
        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($sid);
        $file = GeneralUtility::makeInstance(FileRepository::class)->findByUid($fid);

        if (
            $file
            && AccessUtility::userHasFileWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $file,
                ['folderRoot' => $folderRoot]
            )
        ) {
            $storage->deleteFile($file->getOriginalResource());
            return true;
        }
        return false;
    }

    /**
     * move file
     * @param int $fid file id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function move($fid, $tfid, $sid, $folderRoot)
    {
        return self::moveOrCopy('move', $fid, $tfid, $sid, $folderRoot);
    }

    /**
     * copy file
     * @param int $fid file id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function copy($fid, $tfid, $sid, $folderRoot)
    {
        return self::moveOrCopy('copy', $fid, $tfid, $sid, $folderRoot);
    }

    /**
     * move or copy file
     * @param string $mode move or copy
     * @param int $fid file id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    private static function moveOrCopy($mode, $fid, $tfid, $sid, $folderRoot)
    {
        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($sid);
        $file = GeneralUtility::makeInstance(FileRepository::class)->findByUid($fid);
        $folder = GeneralUtility::makeInstance(FolderRepository::class)->findByUid($tfid);
        if (
            $file
            && AccessUtility::userHasFileWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $file,
                ['folderRoot' => $folderRoot]
            )
            && $folder
            && AccessUtility::userHasFolderWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $folder,
                ['folderRoot' => $folderRoot]
            )
        ) {
            $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
            if ($mode == 'copy') {
                $newfile = $storage->copyFile($file->getOriginalResource(), $storage->getFolder($folder->getGedPath()));
                $meta = $file->getMeta();
                $meta['file'] = $newfile->getUid();
                $meta['folder_uid'] = $tfid;
                $metaDataRepository->update($newfile->getUid(), $meta);
            } else {
                $storage->moveFile($file->getOriginalResource(), $storage->getFolder($folder->getGedPath()));
                $metaDataRepository->update($file->getUid(), ['folder_uid' => $tfid]);
            }
            return true;
        }
        return false;
    }

    /**
     * Call after file addition in filelist
     * Add the file to the correct folder in the database
     * @param File $file
     * @param Folder $targetFolder
     */
    public static function add($file, $targetFolder)
    {
        $folderRecord = GeneralUtility::makeInstance(FolderRepository::class)->findRawByStorageAndIdentifier(
            $targetFolder->getStorage()->getUid(),
            $targetFolder->getIdentifier()
        );

        if ($folderRecord !== false) {
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
                        $fileContentTable = 'tx_ameosfilemanager_domain_model_filecontent';
                        $fileContentConnection = $connectionPool->getConnectionForTable($fileContentTable);
                        $fileContent = $fileContentConnection
                            ->select(
                                ['file'],
                                $fileContentTable,
                                ['file' => $file->getUid()]
                            )
                            ->fetch();

                        if ($fileContent) {
                            $fileContentConnection
                                ->update(
                                    $fileContentTable,
                                    ['content' => $textExtractor->extractText($file)],
                                    ['file' => $file->getUid()]
                                );
                        } else {
                            $fileContentConnection
                                ->insert($fileContentTable, [
                                    'file' => $file->getUid(),
                                    'content' => $textExtractor->extractText($file),
                                ]);
                        }
                    }
                } catch (\Exception $e) {
                    //
                }
            }
        }
    }
}
