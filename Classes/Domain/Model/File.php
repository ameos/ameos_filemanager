<?php

namespace Ameos\AmeosFilemanager\Domain\Model;

use Ameos\AmeosFilemanager\Domain\Repository\CategoryRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;

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
     * @var MetaDataRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metaDataRepository;

    /** @param MetaDataRepository $metaDataRepository */
    public function injectMetaDataRepository(MetaDataRepository $metaDataRepository)
    {
        $this->metaDataRepository = $metaDataRepository;
    }

    /**
     * @var FolderRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $folderRepository;

    /** @param FolderRepository $folderRepository */
    public function injectFolderRepository(FolderRepository $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }

    /**
     * @var FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository;

    /** @param FrontendUserRepository $frontendUserRepository */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @param CategoryRepository $categoryRepository */
    public function injectCategoryRepository(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @var array folders
     */
    protected static $folders = [];

    /**
     * @var object meta
     */
    protected $meta = false;

    /**
     * @var object meta
     */
    protected $cruser = false;

    /**
     * @var object meta
     */
    protected $feuser = false;

    /**
     * @return array
     */
    public function getMeta($reload = false)
    {
        if ($this->meta === false || $reload) {
            $this->meta = $this->metaDataRepository->findByFileUid($this->getUid());
        }
        return $this->meta;
    }

    /**
     * @return int
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
        $res = [];
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
        $res = [];
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
        if ($this->cruser === false) {
            $beUserRepository = GeneralUtility::makeInstance(
                \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository::class
            );
            $this->cruser = $beUserRepository->findByUid($this->getMeta()['cruser_id']);
        }
        return $this->cruser;
    }

    /**
     * @return int
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
        if ($this->feuser === false) {
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
            self::$folders[$this->getMeta()['folder_uid']] = $this->folderRepository
                ->findByUid($this->getMeta()['folder_uid']);
        }
        return self::$folders[$this->getMeta()['folder_uid']];
    }

    /**
     * @return bool
     */
    public function getNoReadAccess()
    {
        return $this->getMeta()['no_read_access'];
    }

    /**
     * @return bool
     */
    public function getNoWriteAccess()
    {
        return $this->getMeta()['no_write_access'];
    }

    /**
     * @return bool
     */
    public function getOwnerHasReadAccess()
    {
        return $this->getMeta()['owner_has_read_access'];
    }

    /**
     * @return bool
     */
    public function getOwnerHasWriteAccess()
    {
        return $this->getMeta()['owner_has_write_access'];
    }

    /**
     * @return bool
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
        $uidsCat = $this->getCategoriesUids();
        if (!empty($uidsCat)) {
            return FilemanagerUtility::getByUids($this->categoryRepository, $uidsCat);
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);
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
            $queryBuilder->expr()->like(
                'tablenames',
                $queryBuilder->createNamedParameter('sys_file_metadata')
            ),
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
            $queryBuilder->expr()->like(
                'tablenames',
                $queryBuilder->createNamedParameter('sys_file_metadata')
            ),
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
                        'uid_local' => $category,
                        'uid_foreign' => $this->getMeta()['uid'],
                        'tablenames' => 'sys_file_metadata',
                        'fieldname' => 'categories',
                        'sorting_foreign' => $i,
                    ]);
                $i++;
            }
        }
    }
}
