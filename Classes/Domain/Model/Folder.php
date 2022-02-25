<?php
namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
 
class Folder extends \TYPO3\CMS\Extbase\Domain\Model\Folder
{
    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository
     */
    protected $folderRepository;

    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
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
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Ameos\AmeosFilemanager\Domain\Model\Folder>
     */
    protected $folders;

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Model\Folder
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
     * @var integer
     */
    protected $noReadAccess;

    /**
     * @var integer
     */
    protected $noWriteAccess;

    /**
     * @var integer
     */
    protected $ownerHasReadAccess;

    /**
     * @var integer
     */
    protected $ownerHasWriteAccess;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
     */
    protected $cruserId;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
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
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Ameos\AmeosFilemanager\Domain\Model\Folder>
     */
    public function getFolders()
    {
        return $this->folderRepository->getSubFolderFromFolder($this->getUid());
    }

    /**
     * @return \Ameos\AmeosFilemanager\Domain\Model\Folder
     */
    public function getParent($rootFolderUid = null)
    {
        if ($this->getUid() && $rootFolderUid == $this->getUid()) {
            return false;
        } else {
            return $this->uidParent;    
        }
    }
    
    /**
     * @return array
     */
    public function getArrayFeGroupRead()
    {
        $res=array();
        if ($this->feGroupRead) {
            foreach (explode(',', $this->feGroupRead) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupWrite()
    {
        $res=array();
        if ($this->feGroupWrite) {
            foreach (explode(',',$this->feGroupWrite) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupAddfolder()
    {
        $res=array();
        if ($this->feGroupAddfolder) {
            foreach (explode(',',$this->feGroupAddfolder) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getArrayFeGroupAddfile()
    {
        $res=array();
        if ($this->feGroupAddfile) {
            foreach (explode(',',$this->feGroupAddfile) as $feGroup) {
                $res[] = $feGroup;
            }
        }
        return $res;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUser
     */
    public function getCruser()
    {
        return $this->cruserId;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    public function getFeUser()
    {
        return $this->feUserId;
    }

    /**
     * @return boolean
     */
    public function getNoReadAccess()
    {
        return $this->noReadAccess;
    }

    /**
     * @return boolean
     */
    public function getNoWriteAccess()
    {
        return $this->noWriteAccess;
    }

    /**
     * @return boolean
     */
    public function getOwnerHasReadAccess()
    {
        return $this->ownerHasReadAccess;
    }

    /**
     * @return boolean
     */
    public function getOwnerHasWriteAccess()
    {
        return $this->ownerHasWriteAccess;
    }

    /**
     * @return string
     */
    public function getGedPath() {
        if ($parent = $this->getParent()) {
            return $parent->getGedPath() . '/' . $this->title;
        }
        return '/'.$this->title;
    }

    /**
     * @return string
     */
    public function getOwnerUsername()
    {
        return $this->getFeUser() ? $this->getFeUser()->getUsername() : '';
    }

    /**
     * @return string
     */
    public function getRecursiveSubFolders()
    {
        $res = '';
        if ($folders = $this->folders) {
            foreach ($folders as $folder) {
                $res .= $folder->getRecursiveSubFolders();
            }
        }
        $res .= $this->getUid().',';

        return $res;
    }

    /**
     * @return string
     */
    public function getSubFolders()
    {
        return substr($this->getRecursiveSubFolders(), 0,-1);
    }

    /**
     * Setter for title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Setter for description
     *
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Setter for keywords
     *
     * @param string $keywords
     * @return void
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Setter for identifier
     *
     * @param string $identifier
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Setter for storage
     *
     * @param int $storage
     * @return void
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * Setter for feUserId
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $feUserId
     * @return void
     */
    public function setFeUser($feUserId)
    {
        $this->feUserId = $feUserId;
    }

    /**
     * Setter for cruserId
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUser $cruserId
     * @return void
     */
    public function setCruser($cruserId)
    {
        $this->cruserId = $cruserId;
    }

    /**
     * Setter for uidParent
     *
     * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $uidParent
     * @return void
     */
    public function setUidParent($uidParent)
    {
        $this->uidParent = $uidParent;
    }

    /**
     * Setter for noReadAccess
     *
     * @param boolean $noReadAccess
     * @return void
     */
    public function setNoReadAccess($noReadAccess)
    {
        $this->noReadAccess = $noReadAccess;
    }

    /**
     * Setter for noWriteAccess
     *
     * @param boolean $noWriteAccess
     * @return void
     */
    public function setNoWriteAccess($noWriteAccess)
    {
        $this->noWriteAccess = $noWriteAccess;
    }

    /**
     * Setter for ownerHasReadAccess
     *
     * @param boolean $ownerHasReadAccess
     * @return void
     */
    public function setOwnerHasReadAccess($ownerHasReadAccess)
    {
        $this->ownerHasReadAccess = $ownerHasReadAccess;
    }

    /**
     * Setter for ownerHasWriteAccess
     *
     * @param boolean $ownerHasWriteAccess
     * @return void
     */
    public function setOwnerHasWriteAccess($ownerHasWriteAccess)
    {
        $this->ownerHasWriteAccess = $ownerHasWriteAccess;
    }

    /**
     * Setter for arrayFeGroupRead
     *
     * @param array $arrayFeGroupRead
     * @return void
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
     * @return void
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
     * @return void
     */
    public function setArrayFeGroupAddfolder($arrayFeGroupAddfolder)
    {
        $arrayFeGroupAddfolder = is_array($arrayFeGroupAddfolder) ?  implode(',', $arrayFeGroupAddfolder) : $arrayFeGroupAddfolder;
        $this->feGroupAddfolder = $arrayFeGroupAddfolder;
    }

    /**
     * Setter for arrayFeGroupAddfile
     *
     * @param array $arrayFeGroupAddfile
     * @return void
     */
    public function setArrayFeGroupAddfile($arrayFeGroupAddfile)
    {
        $arrayFeGroupAddfile = is_array($arrayFeGroupAddfile) ?  implode(',', $arrayFeGroupAddfile) : $arrayFeGroupAddfile;
        $this->feGroupAddfile = $arrayFeGroupAddfile;
    }

    /**
     * return number of files in the folder
     * @return int
     */ 
    public function getFileNumber()
    {
        return $this->folderRepository->countFilesForFolder($this);        
    }

    /**
     * return number of ready files in the folder
     * @return int
     */ 
    public function getReadyFileNumber()
    {
        return $this->folderRepository->countFilesForFolder($this, false);        
    }

    /**
     * return number of files in the folder
     * @return int
     */ 
    public function getFilesSize()
    {
        return $this->folderRepository->countFilesizeForFolder($this);        
    }

    /**
     * return number of subfolders in the folder
     * @return int
     */ 
    public function getFolderNumber()
    {
        return $this->folderRepository->countFoldersForFolder($this->getUid());        
    }

    public function hasFolder($folderName, $uid=null)
    {
        foreach ($this->getFolders() as $child) {
            if ($child->getTitle() == $folderName && $child->getUid() != $uid) {
                return true;
            }
        }
        return false;
    }


    public function getCategories()
    {
        if (!$this->getUid()) {
            return [];
        }

        $uidsCategories = $this->getCategoriesUids();

        if (!empty($uidsCategories)) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $repository = $objectManager->get(CategoryRepository::class);
            $categories = FilemanagerUtility::getByUids($repository, $uidsCategories);
            return $categories;
        } else {
            return [];
        }
    }

    public function getCategoriesUids()
    {
        if (!$this->getUid()) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category_record_mm');

        $constraints = [
            $queryBuilder->expr()->like('tablenames', $queryBuilder->createNamedParameter('tx_ameosfilemanager_domain_model_folder')),
            $queryBuilder->expr()->like('fieldname', $queryBuilder->createNamedParameter('cats')),
            $queryBuilder->expr()->like('uid_foreign', $queryBuilder->createNamedParameter($this->getUid())),
        ];
        $categories = $queryBuilder
            ->select('uid_local')
            ->from('sys_category_record_mm')
            ->where(...$constraints)
            ->execute();

        $uids = [];
        while ($category = $categories->fetch()) {
            $uids[] = $category['uid_local'];
        }
        return $uids;
    }

    public function setCategories($categories)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_category_record_mm');

        $constraints = [
            $queryBuilder->expr()->like('tablenames', $queryBuilder->createNamedParameter('tx_ameosfilemanager_domain_model_folder')),
            $queryBuilder->expr()->like('fieldname', $queryBuilder->createNamedParameter('cats')),
            $queryBuilder->expr()->like('uid_foreign', $queryBuilder->createNamedParameter($this->getUid())),
        ];

        $queryBuilder
            ->delete('sys_category_record_mm')
            ->where(...$constraints)
            ->execute();

        $i = 1;
        if (is_array($categories) && !empty($categories)) {
            foreach($categories as $category) {
                $connectionPool
                    ->getConnectionForTable('sys_category_record_mm')
                    ->insert('sys_category_record_mm', [
                        'uid_local'       => $category,
                        'uid_foreign'     => $this->getUid(),
                        'tablenames'      => 'tx_ameosfilemanager_domain_model_folder',
                        'fieldname'       => 'cats',
                        'sorting_foreign' => $i,
                    ]);
                $i++;
            }
        }
    }

    public function isChildOf($uidFolder)
    {
        if ($this->getUid() == $uidFolder) {
            return true;
        } elseif ($this->getParent()){
            return $this->getParent()->isChildOf($uidFolder);
        } else {
            return false;
        }
    }

    public function getIsEmpty()
    {
        return ( $this->getFileNumber() == 0 && $this->getFolderNumber() == 0 );
    }

    public function injectFolderRepository(FolderRepository $folderRepository): void
    {
        $this->folderRepository = $folderRepository;
    }
}
