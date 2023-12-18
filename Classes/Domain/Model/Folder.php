<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\Folder as ModelFolder;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Folder extends ModelFolder
{
    /**
     * @var string
     * Validate("NotEmpty")
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var ObjectStorage<Folder>
     */
    protected $folders;

    /**
     * @var ObjectStorage<Category>
     */
    protected $cats;

    /**
     * @var Folder
     */
    protected $uidParent;

    /**
     * @var string
     */
    protected $feGroupRead;

    /**
     * @var string
     */
    protected $feGroupWrite;

    /**
     * @var string
     */
    protected $feGroupAddfile;

    /**
     * @var string
     */
    protected $feGroupAddfolder;

    /**
     * @var int
     */
    protected $crdate;

    /**
     * @var int
     */
    protected $tstamp;

    /**
     * @var int
     */
    protected $noReadAccess;

    /**
     * @var int
     */
    protected $noWriteAccess;

    /**
     * @var int
     */
    protected $ownerHasReadAccess;

    /**
     * @var int
     */
    protected $ownerHasWriteAccess;

    /**
     * @var int
     */
    protected $cruserId;

    /**
     * @var int
     */
    protected $feUserId;

    /**
     * @var array
     */
    protected $arrayFeGroupRead;

    /**
     * @var array
     */
    protected $arrayFeGroupWrite;

    /**
     * @var array
     */
    protected $arrayFeGroupAddfile;

    /**
     * @var array
     */
    protected $arrayFeGroupAddfolder;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var int
     */
    protected $storage;

    /**
     * @return int
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * @return int
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return int
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return ObjectStorage<Folder>
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCats()
    {
        return $this->cats;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories()
    {
        return $this->getCats();
    }

    /**
     * @param ObjectStorage<Category>
     * @return self
     */
    public function setCategories($categories)
    {
        $this->cats = $categories;
        return $this;
    }

    /**
     * @return Folder
     */
    public function getParent($rootFolderUid = null)
    {
        if ($this->getUid() && $rootFolderUid == $this->getUid()) {
            return false;
        }
        return $this->uidParent;
    }

    /**
     * @return string
     */
    public function getFeGroupRead()
    {
        return $this->feGroupRead ? $this->feGroupRead : false;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupRead()
    {
        $res = [];
        if ($this->feGroupRead) {
            foreach (explode(',', $this->feGroupRead) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return string
     */
    public function getFeGroupWrite()
    {
        return $this->feGroupWrite ? $this->feGroupWrite : false;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupWrite()
    {
        $res = [];
        if ($this->feGroupWrite) {
            foreach (explode(',', $this->feGroupWrite) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return string
     */
    public function getFeGroupAddfolder()
    {
        return $this->feGroupAddfolder ? $this->feGroupAddfolder : false;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupAddfolder()
    {
        $res = [];
        if ($this->feGroupAddfolder) {
            foreach (explode(',', $this->feGroupAddfolder) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }


    /**
     * @return string
     */
    public function getFeGroupAddfile()
    {
        return $this->feGroupAddfile ? $this->feGroupAddfile : false;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupAddfile()
    {
        $res = [];
        if ($this->feGroupAddfile) {
            foreach (explode(',', $this->feGroupAddfile) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return int
     */
    public function getCruser()
    {
        return $this->cruserId;
    }

    /**
     * @return int
     */
    public function getFeUser()
    {
        return $this->feUserId;
    }

    /**
     * @return bool
     */
    public function getNoReadAccess()
    {
        return $this->noReadAccess;
    }

    /**
     * @return bool
     */
    public function getNoWriteAccess()
    {
        return $this->noWriteAccess;
    }

    /**
     * @return bool
     */
    public function getOwnerHasReadAccess()
    {
        return $this->ownerHasReadAccess;
    }

    /**
     * @return bool
     */
    public function getOwnerHasWriteAccess()
    {
        return $this->ownerHasWriteAccess;
    }

    /**
     * @return string
     */
    public function getRecursiveSubFolders()
    {
        $res = '';
        if ($this->folders) {
            foreach ($this->folders as $folder) {
                $res .= $folder->getRecursiveSubFolders();
            }
        }
        return $res . $this->getUid() . ',';
    }

    /**
     * @return string
     */
    public function getSubFolders()
    {
        return substr($this->getRecursiveSubFolders(), 0, -1);
    }

    /**
     * Setter for title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Setter for description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Setter for keywords
     *
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Setter for identifier
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Setter for storage
     *
     * @param int $storage
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * Setter for feUserId
     *
     * @param int $feUserId
     */
    public function setFeUser($feUserId)
    {
        $this->feUserId = $feUserId;
    }

    /**
     * Setter for cruserId
     *
     * @param int $cruserId
     */
    public function setCruser($cruserId)
    {
        $this->cruserId = $cruserId;
    }

    /**
     * Setter for uidParent
     *
     * @param Folder $uidParent
     */
    public function setUidParent($uidParent)
    {
        $this->uidParent = $uidParent;
    }

    /**
     * Setter for noReadAccess
     *
     * @param bool $noReadAccess
     */
    public function setNoReadAccess($noReadAccess)
    {
        $this->noReadAccess = $noReadAccess;
    }

    /**
     * Setter for noWriteAccess
     *
     * @param bool $noWriteAccess
     */
    public function setNoWriteAccess($noWriteAccess)
    {
        $this->noWriteAccess = $noWriteAccess;
    }

    /**
     * Setter for ownerHasReadAccess
     *
     * @param bool $ownerHasReadAccess
     */
    public function setOwnerHasReadAccess($ownerHasReadAccess)
    {
        $this->ownerHasReadAccess = $ownerHasReadAccess;
    }

    /**
     * Setter for ownerHasWriteAccess
     *
     * @param bool $ownerHasWriteAccess
     */
    public function setOwnerHasWriteAccess($ownerHasWriteAccess)
    {
        $this->ownerHasWriteAccess = $ownerHasWriteAccess;
    }

    /**
     * Setter for arrayFeGroupRead
     *
     * @param array $arrayFeGroupRead
     */
    public function setArrayFeGroupRead($arrayFeGroupRead)
    {
        $arrayFeGroupRead = is_array($arrayFeGroupRead) ?  implode(',', $arrayFeGroupRead) : $arrayFeGroupRead;
        $this->feGroupRead = $arrayFeGroupRead;
    }

    /**
     * Setter for arrayFeGroupWrite
     *
     * @param array $arrayFeGroupWrite
     */
    public function setArrayFeGroupWrite($arrayFeGroupWrite)
    {
        $arrayFeGroupWrite = is_array($arrayFeGroupWrite) ?  implode(',', $arrayFeGroupWrite) : $arrayFeGroupWrite;
        $this->feGroupWrite = $arrayFeGroupWrite;
    }

    /**
     * Setter for arrayFeGroupAddfolder
     *
     * @param array $arrayFeGroupAddfolder
     */
    public function setArrayFeGroupAddfolder($arrayFeGroupAddfolder)
    {
        $arrayFeGroupAddfolder = is_array($arrayFeGroupAddfolder)
            ? implode(',', $arrayFeGroupAddfolder)
            : $arrayFeGroupAddfolder;
        $this->feGroupAddfolder = $arrayFeGroupAddfolder;
    }

    /**
     * Setter for arrayFeGroupAddfile
     *
     * @param array $arrayFeGroupAddfile
     */
    public function setArrayFeGroupAddfile($arrayFeGroupAddfile)
    {
        $arrayFeGroupAddfile = is_array($arrayFeGroupAddfile)
            ? implode(',', $arrayFeGroupAddfile)
            : $arrayFeGroupAddfile;
        $this->feGroupAddfile = $arrayFeGroupAddfile;
    }

    public function hasFolder($folderName, $uid = null)
    {
        foreach ($this->getFolders() as $child) {
            if ($child->getTitle() == $folderName && $child->getUid() != $uid) {
                return true;
            }
        }
        return false;
    }

    public function isChildOf($uidFolder)
    {
        if ($this->getUid() == $uidFolder) {
            return true;
        }
        if ($this->getParent()) {
            return $this->getParent()->isChildOf($uidFolder);
        }
        return false;
    }
}
