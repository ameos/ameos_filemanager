<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class FolderUtility
{
    /**
     * remove folder
     * @param int $fid folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function remove($fid, $sid, $folderRoot)
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);

        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($sid);
        $folder = $folderRepository->findByUid($fid);

        if (
            $folder
            && AccessUtility::userHasFolderWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $folder,
                ['folderRoot' => $folderRoot]
            )
        ) {
            $storage->deleteFolder($storage->getFolder($folder->getGedPath()), true);
            $folderRepository->remove($folder);
            return true;
        }
        return false;
    }

    /**
     * move folder
     * @param int $fid folder id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function move($fid, $tfid, $sid, $folderRoot)
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);

        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($sid);
        $folder = $folderRepository->findByUid($fid);
        $tfolder = $folderRepository->findByUid($tfid);

        if (
            $folder
            && AccessUtility::userHasFolderWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $folder,
                ['folderRoot' => $folderRoot]
            )
            && $tfolder
            && AccessUtility::userHasFolderWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $tfolder,
                ['folderRoot' => $folderRoot]
            )
        ) {
            $storage->moveFolder(
                $storage->getFolder($folder->getGedPath()),
                $storage->getFolder($tfolder->getGedPath())
            );

            $folder->setUidParent($tfid);
            $folder->setIdentifier($tfolder->getIdentifier() . $folder->getTitle() . '/');
            $folderRepository->add($folder);

            return true;
        }
        return false;
    }

    /**
     * copy folder
     * @param int $fid folder id
     * @param int $tfid target folder id
     * @param int $sid storage id
     * @param int $folderRoot root folder
     */
    public static function copy($fid, $tfid, $sid, $folderRoot)
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);

        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($sid);
        $folder = $folderRepository->findByUid($fid);
        $tfolder = $folderRepository->findByUid($tfid);

        if (
            $folder
            && AccessUtility::userHasFolderWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $folder,
                ['folderRoot' => $folderRoot]
            )
            && $tfolder
            && AccessUtility::userHasFolderWriteAccess(
                $GLOBALS['TSFE']->fe_user->user,
                $tfolder,
                ['folderRoot' => $folderRoot]
            )
        ) {
            $newfolder = $storage->copyFolder(
                $storage->getFolder($folder->getGedPath()),
                $storage->getFolder($tfolder->getGedPath())
            );

            $gedNewFolder = self::getGedCopiedFolder($folder, $newfolder->getName(), $tfolder, $sid);
            static::indexingAfterCopy($folder, $gedNewFolder, $sid);

            return true;
        }
        return false;
    }

    /**
     * indexing new folder after copy
     * @param Ameos\AmeosFilemanager\Domain\Model\Folder $sourceFolder source folder
     * @param Ameos\AmeosFilemanager\Domain\Model\Folder $targetFolder target folder
     * @param int $sid storage id
     */
    protected static function indexingAfterCopy($sourceFolder, $targetFolder, $sid)
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($sid);

        $files = $storage->getFolder($sourceFolder->getGedPath())->getFiles();
        if ($files) {
            foreach ($files as $file) {
                $fileIdentifier = $targetFolder->getGedPath() . '/' . $file->getName();
                $newfile = $storage->getFile($fileIdentifier);

                $meta = $fileRepository->findByUid($file->getProperty('uid'))->getMeta();
                $meta['file'] = $newfile->getUid();
                $meta['folder_uid'] = $targetFolder->getUid();

                $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                $metaDataRepository->update($newfile->getUid(), $meta);
            }
        }

        if ($sourceFolder->getFolders()) {
            foreach ($sourceFolder->getFolders() as $folder) {
                $gedNewFolder = self::getGedCopiedFolder(
                    $folder,
                    $folder->getTitle(),
                    $targetFolder,
                    $folder->getStorage()
                );
                static::indexingAfterCopy($folder, $gedNewFolder, $sid);
            }
        }
    }

    /**
     * Call after folder addition in filelist
     * Add the correct folder in the database
     * @param Folder $folder
     */
    public static function add($folder)
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        if ($folder->getParentFolder() && $folder->getParentFolder()->getName() != '') {
            $inserted = false;

            $folderParentRecord = $folderRepository->findRawByStorageAndIdentifier(
                $folder->getParentFolder()->getStorage()->getUid(),
                $folder->getParentFolder()->getIdentifier()
            );
            if ($folderParentRecord) {
                $folderRecord = $folderRepository->findRawByStorageAndIdentifier(
                    $folder->getStorage()->getUid(),
                    $folder->getIdentifier()
                );
                if (!$folderRecord) {
                    $folderRepository->requestInsert([
                        'tstamp'     => time(),
                        'crdate'     => time(),
                        'cruser_id'  => 1,
                        'title'      => $folder->getName(),
                        'uid_parent' => $folderParentRecord['uid'],
                        'identifier' => $folder->getIdentifier(),
                        'storage'    => $folder->getStorage()->getUid(),
                    ]);
                }
                $inserted = true;
            }

            if (!$inserted) {
                self::add($folder->getParentFolder());
                self::add($folder);
            }
        } else {
            $folderRepository->requestInsert([
                'tstamp'     => time(),
                'crdate'     => time(),
                'cruser_id'  => 1,
                'title'      => $folder->getName(),
                'uid_parent' => 0,
                'identifier' => $folder->getIdentifier(),
                'storage'    => $folder->getStorage()->getUid(),
            ]);
        }
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();
    }

    private static function getGedCopiedFolder($folder, $newTitle, $targetFolder, $targetStorage)
    {
        $gedNewFolder = GeneralUtility::makeInstance(Folder::class);

        $gedNewFolder->setTitle($folder->getTitle());
        $gedNewFolder->setDescription($folder->getDescription());
        $gedNewFolder->setKeywords($folder->getKeywords());
        $gedNewFolder->setNoReadAccess($folder->getNoReadAccess());
        $gedNewFolder->setNoWriteAccess($folder->getNoWriteAccess());
        $gedNewFolder->setArrayFeGroupRead($folder->getArrayFeGroupRead());
        $gedNewFolder->setArrayFeGroupWrite($folder->getArrayFeGroupWrite());
        $gedNewFolder->setArrayFeGroupAddfile($folder->getArrayFeGroupAddfile());
        $gedNewFolder->setArrayFeGroupAddfolder($folder->getArrayFeGroupAddfolder());
        $gedNewFolder->setOwnerHasReadAccess($folder->getOwnerHasReadAccess());
        $gedNewFolder->setOwnerHasWriteAccess($folder->getOwnerHasWriteAccess());
        $gedNewFolder->setUidParent($targetFolder);
        $gedNewFolder->setIdentifier($targetFolder->getGedPath() . '/' . $newTitle . '/');
        $gedNewFolder->setStorage($targetStorage);

        GeneralUtility::makeInstance(FolderRepository::class)->add($gedNewFolder);
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();

        return $gedNewFolder;
    }
}
