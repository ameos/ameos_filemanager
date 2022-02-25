<?php
namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
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
 
class File extends \TYPO3\CMS\Extbase\Domain\Model\File
{
    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository
     */
    protected $folderRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     */
    protected $frontendUserRepository;

    /**
     * @var array folders
     */ 
    protected static $folders = array();

    /**
     * @var Object meta
     */
    protected $meta = FALSE;

    /**
     * @var Object meta
     */
    protected $cruser = FALSE;

    /**
     * @var Object meta
     */
    protected $feuser = FALSE; 

    /**
     * @return array
     */
    public function getMeta($reload = false)
    {
        if ($this->meta === FALSE || $reload) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $metaDataRepository = $objectManager->get(MetaDataRepository::class);
            $this->meta = $metaDataRepository->findByFileUid($this->getUid());
        }
        return $this->meta;
    }

    /**
     * @return integer
     */
    public function getCrdate()
    {
        return $this->getMeta()['crdate'];
    }

    /**
     * @return string
     */
    public function getFeGroupRead()
    {
        return $this->getMeta()['fe_group_read'];
    }
    
    /**
     * @return string
     */
    public function getFeGroupWrite()
    {
        return $this->getMeta()['fe_group_write'];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getMeta()['title'] ?: $this->getOriginalResource()->getName();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getMeta()['description'];
    }

    /**
     * return true if thumbnail is available
     * alias for call from fluid template
     * @return bool
     */
    public function getThumbnailAvailable()
    {
        return $this->thumbnailAvailable();
    }

    /**
     * return true if thumbnail is available
     * @return bool
     */
    public function thumbnailAvailable()
    {
        return in_array($this->getOriginalResource()->getProperty('mime_type'), [
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
            'application/pdf',
        ]);
    }

    /**
     * return true if preview is available
     * alias for call from fluid template
     * @return bool
     */
    public function getPreviewAvailable()
    {
        return $this->previewAvailable();
    }

    /**
     * return true if preview is available
     * @return bool
     */
    public function previewAvailable()
    {
        return in_array($this->getOriginalResource()->getProperty('mime_type'), [
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/svg+xml',
            'application/pdf',
            'video/youtube',
            'video/vimeo',
            'video/dailymotion',
        ]);
    }

    /**
     * return true if is remote file
     * alias for call from fluid template
     * @return bool
     */
    public function getIsRemote()
    {
        return $this->isRemote();
    }

    /**
     * return true if is remote file
     * @return bool
     */
    public function isRemote()
    {
        return in_array($this->getOriginalResource()->getProperty('mime_type'), [
            'video/youtube',
            'video/vimeo',
            'video/dailymotion',
        ]);
    }

    /**
     * @return array
     */
    public function getArrayFeGroupRead()
    {
        $res=array();
        if ($grp = $this->getFeGroupRead()) {
            foreach (explode(',', $grp) as $g) {
                $res[] = $g;
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
        if ($grp = $this->getFeGroupWrite()) {
            foreach (explode(',', $grp) as $g) {
                $res[] = $g;
            }
        }
        return $res;
    }

    /**
     * @return Tx_Extbase_Domain_Model_BackendUser
     */
    public function getCruser()
    {
        if ($this->cruser === FALSE) {
            $beUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class);
            $this->cruser = $beUserRepository->findByUid($this->getMeta()['cruser_id']);
        }
        return $this->cruser;
    }

    /**
     * @return integer
     */
    public function getTstamp()
    {
        return $this->getMeta()['tstamp'];
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    public function getFeUser()
    {
        if ($this->feuser === FALSE) {
            $this->feuser = $this->frontendUserRepository->findByUid($this->getMeta()['fe_user_id']);
        }
        return $this->feuser;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->getMeta()['keywords'];
    }   

    /**
     * @return string
     */
    public function getGedPath()
    {
        return $this->getOriginalResource()->getIdentifier();
    }

    /**
     * @return \Ameos\AmeosFilemanager\Domain\Model\Folder
     */
    public function getParentFolder()
    {
        if (!isset(self::$folders[$this->getMeta()['folder_uid']])) {
            self::$folders[$this->getMeta()['folder_uid']] = $this->folderRepository->findByUid($this->getMeta()['folder_uid']);
        }        
        return self::$folders[$this->getMeta()['folder_uid']];
    }

    /**
     * @return boolean
     */
    public function getNoReadAccess()
    {
        return $this->getMeta()['no_read_access'];
    }

    /**
     * @return boolean
     */
    public function getNoWriteAccess()
    {
        return $this->getMeta()['no_write_access'];
    }

    /**
     * @return boolean
     */
    public function getOwnerHasReadAccess() {
        return $this->getMeta()['owner_has_read_access'];
    }

    /**
     * @return boolean
     */
    public function getOwnerHasWriteAccess() {
        return $this->getMeta()['owner_has_write_access'];
    }

    /**
     * @return boolean
     */
    public function getOwnerReadOnly()
    {
        return $this->getMeta()['owner_read_only'];
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
    public function getPublicUrl()
    {
        return $this->getOriginalResource()->getPublicUrl();
    }
    
    /**
     * Returns the cats
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $cats
     */
    public function getCategories()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $repo = $objectManager->get(CategoryRepository::class);

        $uidsCat = $this->getCategoriesUids();
        if (!empty($uidsCat)) {
            $categories = FilemanagerUtility::getByUids($repo, $uidsCat);
            return $categories;
        } else {
            return;
        }  
    }

    /**
     * Returns the cats
     *
     * @return array
     */
    public function getCategoriesUids()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category_record_mm');

        $constraints = [
            $queryBuilder->expr()->like('tablenames', $queryBuilder->createNamedParameter('sys_file_metadata')),
            $queryBuilder->expr()->like('fieldname', $queryBuilder->createNamedParameter('categories')),
            $queryBuilder->expr()->like('uid_foreign', $queryBuilder->createNamedParameter($this->getMeta()['uid'])),
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
            $queryBuilder->expr()->like('tablenames', $queryBuilder->createNamedParameter('sys_file_metadata')),
            $queryBuilder->expr()->like('fieldname', $queryBuilder->createNamedParameter('categories')),
            $queryBuilder->expr()->like('uid_foreign', $queryBuilder->createNamedParameter($this->getMeta()['uid'])),
        ];
        
        $queryBuilder
            ->delete('sys_category_record_mm')
            ->where(...$constraints)
            ->execute();

        $i = 1;
        if (is_array($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $connectionPool
                    ->getConnectionForTable('sys_category_record_mm')
                    ->insert('sys_category_record_mm', [
                        'uid_local'       => $category,
                        'uid_foreign'     => $this->getMeta()['uid'],
                        'tablenames'      => 'sys_file_metadata',
                        'fieldname'       => 'categories',
                        'sorting_foreign' => $i,
                    ]);
                $i++;
            }
        }
    }

    public function injectFolderRepository(FolderRepository $folderRepository): void
    {
        $this->folderRepository = $folderRepository;
    }

    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }
}
