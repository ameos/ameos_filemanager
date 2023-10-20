<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Enum\Access;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AccessService
{
    /**
     * return true if $user can read $file
     * @param ?array $user
     * @param File $file
     * @return bool
     */
    public function canReadFile(?array $user, File $file): bool
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $can = false;

        if (
            $file->getNoReadAccess() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($file->getFeUser()) // no owner
                || $file->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $can = false;
        } elseif (
            $user
            && $user['uid'] > 0
            && $file->getOwnerHasReadAccess()
            && is_object($file->getFeUser())
            && $file->getFeUser()->getUid() == $user['uid']
        ) {
            $can = true;
        } else {
            $can = $fileRepository->findByUid($file->getUid()) ? true : false;
        }

        return $can;
    }

    /**
     * return true if $user can write $file
     * @param ?array $user
     * @param File $file
     * @return bool
     */
    public function canWriteFile(?array $user, File $file): bool
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $can = false;

        if (
            $file->getNoWriteAccess() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($file->getFeUser()) // no owner
                || $file->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $can = false;
        } elseif (
            $user
            && $user['uid'] > 0
            && $file->getOwnerHasWriteAccess()
            && is_object($file->getFeUser())
            && $file->getFeUser()->getUid() == $user['uid']
        ) {
            $can = true;
        } else {
            $can = $fileRepository->findByUid($file->getUid()) ? true : false;
        }

        return $can;
    }

    /**
     * return true if $user can read $folder
     * @param ?array $user
     * @param Folder $folder
     * @return bool
     */
    public function canReadFolder(?array $user, Folder $folder): bool
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        $can = false;

        if (
            $folder->getNoReadAccess() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $can = false;
        } elseif (
            $user
            && $user['uid'] > 0
            && $folder->getOwnerHasReadAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            $can = true;
        } else {
            $can = $folderRepository->findByUid($folder->getUid()) ? true : false;
        }

        return $can;
    }

    /**
     * return true if $user can write $folder
     * @param ?array $user
     * @param Folder $folder
     * @return bool
     */
    public function canWriteFolder(?array $user, Folder $folder): bool
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        $can = false;

        if (
            $folder->getNoWriteAccess() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $can = false;
        } elseif (
            $user
            && $user['uid'] > 0
            && $folder->getOwnerHasWriteAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            $can = true;
        } else {
            $can = $folderRepository->findByUid($folder->getUid(), Access::ACCESS_WRITE) ? true : false;
        }

        return $can;
    }

    /**
     * return true if $user can add folder in $folder
     * @param ?array $user
     * @param Folder $folder
     * @return bool
     */
    public function canAddFolder(?array $user, Folder $folder): bool
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        $can = false;

        if (
            $folder->getNoWriteAccess() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $can = false;
        } elseif (
            $user
            && $user['uid'] > 0
            && $folder->getOwnerHasWriteAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            $can = true;
        } else {
            $can = $folderRepository->findByUid($folder->getUid(), Access::ACCESS_ADDFOLDER) ? true : false;
        }

        return $can;
    }

    /**
     * return true if $user can add file in $folder
     * @param ?array $user
     * @param Folder $folder
     * @return bool
     */
    public function canAddFile(?array $user, Folder $folder): bool
    {
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        $can = false;

        if (
            $folder->getNoWriteAccess() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $can = false;
        } elseif (
            $user
            && $user['uid'] > 0
            && $folder->getOwnerHasWriteAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            $can = true;
        } else {
            $can = $folderRepository->findByUid($folder->getUid(), Access::ACCESS_ADDFILE) ? true : false;
        }

        return $can;
    }
}
