<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AccessService
{
    /**
     * @var Context
     */
    private Context $context;

    /**
     * construct
     */
    public function __construct()
    {
        $this->context = GeneralUtility::makeInstance(Context::class);
    }

    /**
     * return true if $user can read $file
     *
     * @param File $file
     * @return bool
     */
    public function canReadFile(File $file): bool
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        $userGroups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($file->getNoReadAccess() && (int)$file->getFeUser() > 0 && (int)$file->getFeUser() === $userId) {
            // access only for owner            
            return false;
        } 
        
        $fileGroups = $file->getFeGroupRead() ? array_map('intval', explode(',', $file->getFeGroupRead())) : [];

        // access for owner
        $ownerVerdict = $file->getOwnerHasReadAccess()
            && (int)$file->getFeUser() > 0
            && $isLoggedIn
            && (int)$file->getFeUser() === $userId;

        // access for user's group
        $groupVerdict = 
            empty($fileGroups)
            || ($isLoggedIn && in_array(-1, $fileGroups))
            || (!$isLoggedIn && in_array(-2, $fileGroups))
            || !empty(array_intersect($fileGroups, $userGroups));
        
        return $ownerVerdict || $groupVerdict;
    }

    /**
     * return true if $user can write $file
     *
     * @param File $file
     * @return bool
     */
    public function canWriteFile(File $file): bool
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        $userGroups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($file->getNoWriteAccess() && (int)$file->getFeUser() > 0 && (int)$file->getFeUser() === $userId) {
            // access only for owner            
            return false;
        } 
        
        $fileGroups = $file->getFeGroupWrite() ? array_map('intval', explode(',', $file->getFeGroupWrite())) : [];

        // access for owner
        $ownerVerdict = $file->getOwnerHasWriteAccess()
            && (int)$file->getFeUser() > 0
            && $isLoggedIn
            && (int)$file->getFeUser() === $userId;

        // access for user's group
        $groupVerdict = 
            empty($fileGroups)
            || ($isLoggedIn && in_array(-1, $fileGroups))
            || (!$isLoggedIn && in_array(-2, $fileGroups))
            || !empty(array_intersect($fileGroups, $userGroups));
        
        return $ownerVerdict || $groupVerdict;
    }

    /**
     * return true if $user can read $folder
     *
     * @param Folder $folder
     * @return bool
     */
    public function canReadFolder(Folder $folder): bool
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        $userGroups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($folder->getNoReadAccess() && (int)$folder->getFeUser() > 0 && (int)$folder->getFeUser() === $userId) {
            // access only for owner            
            return false;
        } 
        
        $folderGroups = $folder->getFeGroupRead() ? array_map('intval', explode(',', $folder->getFeGroupRead())) : [];

        // access for owner
        $ownerVerdict = $folder->getOwnerHasReadAccess()
            && (int)$folder->getFeUser() > 0
            && $isLoggedIn
            && (int)$folder->getFeUser() === $userId;

        // access for user's group
        $groupVerdict = 
            empty($folderGroups)
            || ($isLoggedIn && in_array(-1, $folderGroups))
            || (!$isLoggedIn && in_array(-2, $folderGroups))
            || !empty(array_intersect($folderGroups, $userGroups));
        
        return $ownerVerdict || $groupVerdict;
    }

    /**
     * return true if $user can write $folder
     *
     * @param Folder $folder
     * @return bool
     */
    public function canWriteFolder(Folder $folder): bool
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        $userGroups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($folder->getNoWriteAccess() && (int)$folder->getFeUser() > 0 && (int)$folder->getFeUser() === $userId) {
            // access only for owner            
            return false;
        } 
        
        $folderGroups = $folder->getFeGroupWrite() ? array_map('intval', explode(',', $folder->getFeGroupWrite())) : [];

        // access for owner
        $ownerVerdict = $folder->getOwnerHasWriteAccess()
            && (int)$folder->getFeUser() > 0
            && $isLoggedIn
            && (int)$folder->getFeUser() === $userId;

        // access for user's group
        $groupVerdict = 
            empty($folderGroups)
            || ($isLoggedIn && in_array(-1, $folderGroups))
            || (!$isLoggedIn && in_array(-2, $folderGroups))
            || !empty(array_intersect($folderGroups, $userGroups));
        
        return $ownerVerdict || $groupVerdict;
    }

    /**
     * return true if $user can add folder in $folder
     *
     * @param Folder $folder
     * @return bool
     */
    public function canAddFolder(Folder $folder): bool
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        $userGroups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($folder->getNoWriteAccess() && (int)$folder->getFeUser() > 0 && (int)$folder->getFeUser() === $userId) {
            // access only for owner            
            return false;
        } 
        
        $folderGroups = $folder->getFeGroupAddfolder() 
            ? array_map('intval', explode(',', $folder->getFeGroupAddfolder()))
            : [];

        // access for owner
        $ownerVerdict = $folder->getOwnerHasWriteAccess()
            && (int)$folder->getFeUser() > 0
            && $isLoggedIn
            && (int)$folder->getFeUser() === $userId;

        // access for user's group
        $groupVerdict = 
            empty($folderGroups)
            || ($isLoggedIn && in_array(-1, $folderGroups))
            || (!$isLoggedIn && in_array(-2, $folderGroups))
            || !empty(array_intersect($folderGroups, $userGroups));
        
        return $ownerVerdict || $groupVerdict;
    }

    /**
     * return true if $user can add file in $folder
     *
     * @param Folder $folder
     * @return bool
     */
    public function canAddFile(Folder $folder): bool
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        $userGroups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($folder->getNoWriteAccess() && (int)$folder->getFeUser() > 0 && (int)$folder->getFeUser() === $userId) {
            // access only for owner            
            return false;
        } 
        
        $folderGroups = $folder->getFeGroupAddfile() 
            ? array_map('intval', explode(',', $folder->getFeGroupAddfile()))
            : [];

        // access for owner
        $ownerVerdict = $folder->getOwnerHasWriteAccess()
            && (int)$folder->getFeUser() > 0
            && $isLoggedIn
            && (int)$folder->getFeUser() === $userId;

        // access for user's group
        $groupVerdict = 
            empty($folderGroups)
            || ($isLoggedIn && in_array(-1, $folderGroups))
            || (!$isLoggedIn && in_array(-2, $folderGroups))
            || !empty(array_intersect($folderGroups, $userGroups));
        
        return $ownerVerdict || $groupVerdict;
    }
}
