<?php
namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
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

class AccessUtility
{
    /**
     * check if user has read permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param array $arguments array of other arguments for hooks
     * @return boolean
     */
    public static function userHasFolderReadAccess($user, $folder, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder || !$folder->isChildOf($rootFolder)){
            return false;
        }
        // Hooks to forbid read permission to a file if necessary
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderReadAccess'] = array_merge( // retro-compatibility
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderReadAccess'],
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFolderReadAccess']
        );
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderReadAccess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderReadAccess'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                if(method_exists($hookObject, 'userHasNotFolderReadAccess') && $hookObject->userHasNotFolderReadAccess($user, $folder, $arguments)) {
                    return false;
                }
            }
        }
        if($folder->getNoReadAccess() && // read only for owner
            (
                !isset($user['uid']) // no user authenficated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            return false;
        }
        if($user && $user['uid'] > 0
            && $folder->getOwnerHasReadAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            return true;
        }
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        if ($folderRepository->findByUid($folder->getUid())) {
            return true;
        }
        return false;
    }

    /**
     * check if user has read permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param array $arguments array of other arguments for hooks
     * @return boolean
     */
    public static function userHasAddFolderAccess($user, $folder, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder) {
            return false;
        }
        if($folder->getNoWriteAccess() && // write only for owner
            (
                !isset($user['uid']) // no user authenficated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            return false;
        }
        if($user && $user['uid'] > 0
            && $folder->getOwnerHasWriteAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            return true;
        }
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        if ($exist = $folderRepository->findByUid($folder->getUid(), 'addfolder')) {
            return true;
        }
        return false;
    }

    /**
     * check if user has read permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param array $arguments array of other arguments for hooks
     * @return boolean
     */
    public static function userHasAddFileAccess($user, $folder, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder) {
            return false;
        }
        if($folder->getNoWriteAccess() && // write only for owner
            (
                !isset($user['uid']) // no user authenficated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            return false;
        }
        if($user && $user['uid'] > 0
            && $folder->getOwnerHasWriteAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            return true;
        }
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        if ($exist = $folderRepository->findByUid($folder->getUid(), 'addfile')) {
            return true;
        }
        return false;
    }

    /**
     * check if user has read permission to the file
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
     * @param array $arguments array of other arguments for hooks
     * @return boolean
     */
    public static function userHasFileReadAccess($user, $file, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        // Hooks to forbid read permission to a file if necessary
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileReadAccess'] = array_merge( // retro-compatibility
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileReadAccess'],
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFileReadAccess']
        );
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileReadAccess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileReadAccess'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                // Don't forget to test $user in your hook
                if(method_exists($hookObject, 'userHasNotFileReadAccess') && $hookObject->userHasNotFileReadAccess($user, $file, $arguments)) {
                    return false;
                }
            }
        }
        if($file->getNoReadAccess() && // read only for owner
            (
                !isset($user['uid']) // no user authenficated
                || !is_object($file->getFeUser()) // no owner
                || $file->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            return false;
        }
        if($user && $user['uid'] > 0
            && $file->getOwnerHasReadAccess()
            && is_object($file->getFeUser())
            && $file->getFeUser()->getUid() == $user['uid']
        ) {
            return true;
        }
        if (!self::userHasFolderReadAccess($user, $file->getParentFolder(), $arguments)) {
            return false;
        }
        if ($file->getArrayFeGroupRead()) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            if ($exist = $fileRepository->findByUid($file->getUid())) {
                return true;
            }
        } else {
            return self::userHasFolderReadAccess($user,$file->getParentFolder(), $arguments);
        }
        return false;
    }


    /**
     * check if user has write permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param array $arguments array of other arguments for hooks
     * @return boolean
     */
    public static function userHasFolderWriteAccess($user, $folder, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder && !$folder->isChildOf($rootFolder)){
            return false;
        }
        // Hooks to forbid read permission to a file if necessary
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderWriteAccess'] = array_merge( // retro-compatibility
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderWriteAccess'],
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFolderWriteAccess']
        );
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderWriteAccess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFolderWriteAccess'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                // Don't forget to test $user in your hook
                if(method_exists($hookObject, 'userHasNotFolderWriteAccess') && $hookObject->userHasNotFolderWriteAccess($user, $folder, $arguments)) {
                    return false;
                }
            }
        }
        if($folder->getNoWriteAccess() && // write only for owner
            (
                !isset($user['uid']) // no user authenficated
                || !is_object($folder->getFeUser()) // no owner
                || $folder->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            return false;
        }
        if($user && $user['uid'] > 0
            && $folder->getOwnerHasWriteAccess()
            && is_object($folder->getFeUser())
            && $folder->getFeUser()->getUid() == $user['uid']
        ) {
            return true;
        }
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        if ($exist = $folderRepository->findByUid($folder->getUid(), 'write')) {
            return true;
        }
        return false;
    }


    /**
     * check if user has write permission to the file
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
     * @param array $arguments array of other arguments for hooks
     * @return boolean
     */
    public static function userHasFileWriteAccess($user, $file, $arguments = null)
    {
        $rootFolder = $arguments['$folderRoot'] ? $arguments['$folderRoot'] : null;
        // Hooks to forbid write permission to a file if necessary
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileWriteAccess'] = array_merge( // retro-compatibility
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileWriteAccess'],
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFileWriteAccess']
        );
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileWriteAccess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class]['userHasFileWriteAccess'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                // Don't forget to test $user in your hook
                if(method_exists($hookObject, 'userHasNotFileWriteAccess') && $hookObject->userHasNotFileWriteAccess($user, $file, $arguments)) {
                    return false;
                }
            }
        }
        if($file->getNoWriteAccess() && // write only for owner
            (
                !isset($user['uid']) // no user authenficated
                || !is_object($file->getFeUser()) // no owner
                || $file->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            return false;
        }
        if($user && $user['uid'] > 0
            && $file->getOwnerHasWriteAccess()
            && is_object($file->getFeUser())
            && $file->getFeUser()->getUid() == $user['uid']
        ) {
            return true;
        }
        if ($file->getArrayFeGroupWrite()) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            if ($exist = $fileRepository->findByUid($file->getUid(),$writeMode = true)) {
                return true;
            }
        } else {
            return self::userHasFolderWriteAccess($user,$file->getParentFolder(),$arguments);
        }
        return false;
    }

}
