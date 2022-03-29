<?php

namespace Ameos\AmeosFilemanager\Utility;

use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
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
     * @return bool
     */
    public static function userHasFolderReadAccess($user, $folder, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder || !$folder->isChildOf($rootFolder)) {
            return false;
        }

        // Hooks to forbid read permission to a folder if necessary
        if (self::getHookResponseForObjectAccess('folderReadAccess', $user, $folder, $arguments) === false) {
            return false;
        }

        $userHasAccess = self::userHasAccessForObject($user, 'read', $folder);

        if (is_null($userHasAccess)) {
            $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
            if ($folderRepository->findByUid($folder->getUid())) {
                $userHasAccess = true;
            } else {
                $userHasAccess = false;
            }
        }
        return $userHasAccess;
    }

    /**
     * check if user has add permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param array $arguments array of other arguments for hooks
     * @return bool
     */
    public static function userHasAddFolderAccess($user, $folder, $arguments = null)
    {
        return self::userHasAddObjectAccess($user, $folder, 'folder', $arguments);
    }

    /**
     * check if user has add permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * @param array $arguments array of other arguments for hooks
     * @return bool
     */
    public static function userHasAddFileAccess($user, $folder, $arguments = null)
    {
        return self::userHasAddObjectAccess($user, $folder, 'file', $arguments);
    }

    public static function userHasAddObjectAccess($user, $folder, $type, $arguments = null)
    {
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder) {
            return false;
        }

        if ($type == 'file') {
            $hookName = 'addFileAccess';
            $accessMode = 'addfile';
        } else {
            $hookName = 'addFolderAccess';
            $accessMode = 'addfolder';
        }

        // Hooks
        if (self::getHookResponseForObjectAccess($hookName, $user, $folder, $arguments) === false) {
            return false;
        }

        $userHasAccess = self::userHasAccessForObject($user, 'write', $folder);

        if (is_null($userHasAccess)) {
            $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
            if ($folderRepository->findByUid($folder->getUid(), $accessMode)) {
                $userHasAccess = true;
            } else {
                $userHasAccess = false;
            }
        }
        return $userHasAccess;
    }

    /**
     * check if user has read permission to the file
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
     * @param array $arguments array of other arguments for hooks
     * @return bool
     */
    public static function userHasFileReadAccess($user, $file, $arguments = null)
    {
        // Hooks to forbid read permission to a file if necessary
        if (self::getHookResponseForObjectAccess('fileReadAccess', $user, $file, $arguments) === false) {
            return false;
        }

        $userHasAccess = self::userHasAccessForObject($user, 'read', $file);

        if (is_null($userHasAccess)) {
            if (!self::userHasFolderReadAccess($user, $file->getParentFolder(), $arguments)) {
                $userHasAccess = false;
            }
            if ($file->getArrayFeGroupRead()) {
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                if ($fileRepository->findByUid($file->getUid())) {
                    $userHasAccess = true;
                } else {
                    $userHasAccess = false;
                }
            } else {
                $userHasAccess = self::userHasFolderReadAccess($user, $file->getParentFolder(), $arguments);
            }
        }
        return $userHasAccess;
    }

    /**
     * check if user has write permission to the folder
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folde $folder
     * @param array $arguments array of other arguments for hooks
     * @return bool
     */
    public static function userHasFolderWriteAccess($user, $folder, $arguments = null)
    {
        $rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if (!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder && !$folder->isChildOf($rootFolder)) {
            return false;
        }

        // Hooks to forbid write permission to a folder if necessary
        if (self::getHookResponseForObjectAccess('folderWriteAccess', $user, $folder, $arguments) === false) {
            return false;
        }

        $userHasAccess = self::userHasAccessForObject($user, 'write', $folder);

        if (is_null($userHasAccess)) {
            $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
            if ($folderRepository->findByUid($folder->getUid(), 'write')) {
                $userHasAccess = true;
            } else {
                $userHasAccess = false;
            }
        }
        return $userHasAccess;
    }

    /**
     * check if user has write permission to the file
     * @param array $user current user
     * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
     * @param array $arguments array of other arguments for hooks
     * @return bool
     */
    public static function userHasFileWriteAccess($user, $file, $arguments = null)
    {
        // Hooks to forbid write permission to a file if necessary
        if (self::getHookResponseForObjectAccess('fileWriteAccess', $user, $file, $arguments) === false) {
            return false;
        }

        $userHasAccess = self::userHasAccessForObject($user, 'write', $file);

        if (is_null($userHasAccess)) {
            if ($file->getArrayFeGroupWrite()) {
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                if ($fileRepository->findByUid($file->getUid(), true)) {
                    $userHasAccess = true;
                }
            } else {
                $userHasAccess = self::userHasFolderWriteAccess($user, $file->getParentFolder(), $arguments);
            }
        }
        return $userHasAccess;
    }

    /**
     * Register available access hooks
     * @param string $hookName The name of the hook without 'userHas' prefix
     * @param array $user The user array
     * @param object $object The object to test file / folder
     * @param array $arguments
     */
    private static function getHookResponseForObjectAccess($hookName, $user, $object, $arguments)
    {
        $userHasAccessHookName = 'userHas' . ucfirst($hookName);
        $userHasNotAccessFunctionName = 'userHasNot' . ucfirst($hookName);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class][$userHasAccessHookName] = array_merge(
            // retro-compatibility
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class][$userHasAccessHookName],
            (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools'][$userHasAccessHookName]
        );
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class][$userHasAccessHookName])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][AccessUtility::class][$userHasAccessHookName] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                // Don't forget to test $user in your hook
                if (
                    method_exists($hookObject, $userHasNotAccessFunctionName)
                    && $hookObject->$userHasNotAccessFunctionName($user, $object, $arguments)
                ) {
                    return false;
                }
            }
        }
        return null;
    }

    /**
     * Test if user has read/write access over file/folder
     * @param array $user
     * @param string $accessType read|write
     * @param object $object the current file / folder
     * @return bool|null
     */
    private static function userHasAccessForObject($user, $accessType, $object)
    {
        if (!in_array($accessType, ['read', 'write'])) {
            return null;
        }
        $getNoAccessFunction = 'getNo' . ucfirst($accessType) . 'Access';
        $getOwnerHasAccessFunction = 'getOwnerHas' . ucfirst($accessType) . 'Access';

        $return = null;

        if (
            $object->$getNoAccessFunction() // read/write only for owner
            && (
                !isset($user['uid']) // no user authenticated
                || !is_object($object->getFeUser()) // no owner
                || $object->getFeUser()->getUid() != $user['uid'] // user is not the owner
            )
        ) {
            $return = false;
        }

        if (
            is_null($return)
            && $user
            && $user['uid'] > 0
            && $object->$getOwnerHasAccessFunction()
            && is_object($object->getFeUser())
            && $object->getFeUser()->getUid() == $user['uid']
        ) {
            $return = true;
        }

        return $return;
    }
}
