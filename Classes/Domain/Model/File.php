<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File as ModelFile;

class File extends ModelFile
{
    /**
     * @var array folders
     */
    protected static array $folders = [];

    /**
     * @var MetaDataAspect
     */
    protected $metaDataAspect;

    /**
     * @var object meta
     */
    protected $cruser = false;

    /**
     * @var object meta
     */
    protected $feuser = false;

    /**
     * Loads the metadata of a file in an encapsulated aspect
     */
    public function getMetaData(): MetaDataAspect
    {
        if ($this->metaDataAspect === null) {
            $this->metaDataAspect = GeneralUtility::makeInstance(MetaDataAspect::class, $this->getOriginalResource());
        }
        return $this->metaDataAspect;
    }

    /**
     * @return int
     */
    public function getCrdate()
    {
        return $this->getMetaData()->offsetGet('crdate');
    }

    /**
     * @return string
     */
    public function getFeGroupRead()
    {
        return $this->getMetaData()->offsetGet('fe_group_read') ?? false;
    }

    /**
     * @return string
     */
    public function getFeGroupWrite()
    {
        return $this->getMetaData()->offsetGet('fe_group_write') ?? false;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getMetaData()->offsetGet('title') ?? $this->getOriginalResource()->getName();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getMetaData()->offsetGet('description');
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
            $this->cruser = $beUserRepository->findByUid($this->getMetaData()->offsetGet('cruser_id'));
        }
        return $this->cruser;
    }

    /**
     * @return int
     */
    public function getTstamp()
    {
        return $this->getMetaData()->offsetGet('tstamp');
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    public function getFeUser()
    {
        return null;
        /**
         * TODO V12
        if ($this->feuser === false) {
            $this->feuser = $this->frontendUserRepository->findByUid($this->getMetaData()->offsetGet('fe_user_id'));
        }
        return $this->feuser;
        */
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->getMetaData()->offsetGet('keywords');
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
        return null;
        /* TODO V12
        if (!isset(self::$folders[$this->getMetaData()->offsetGet('folder_uid')])) {
            self::$folders[$this->getMetaData()->offsetGet('folder_uid')] = $this->folderRepository
                ->findByUid($this->getMetaData()->offsetGet('folder_uid'));
        }
        return self::$folders[$this->getMetaData()->offsetGet('folder_uid')];*/
    }

    /**
     * @return bool
     */
    public function getNoReadAccess()
    {
        return $this->getMetaData()->offsetGet('no_read_access')?? false;
    }

    /**
     * @return bool
     */
    public function getNoWriteAccess()
    {
        return $this->getMetaData()->offsetGet('no_write_access')?? false;
    }

    /**
     * @return bool
     */
    public function getOwnerHasReadAccess()
    {
        return $this->getMetaData()->offsetGet('owner_has_read_access') ?? false;
    }

    /**
     * @return bool
     */
    public function getOwnerHasWriteAccess()
    {
        return $this->getMetaData()->offsetGet('owner_has_write_access') ?? false;
    }

    /**
     * @return bool
     */
    public function getOwnerReadOnly()
    {
        return $this->getMetaData()->offsetGet('owner_read_only') ?? false;
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
        return [];
/** TODO V12
        $uidsCat = $this->getCategoriesUids();
        if (!empty($uidsCat)) {
            return FilemanagerUtility::getByUids($this->categoryRepository, $uidsCat);
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);*/
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
            $queryBuilder->expr()->like('uid_foreign', $queryBuilder->createNamedParameter($this->getMetaData()->offsetGet('uid'))),
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
            $queryBuilder->expr()->like('uid_foreign', $queryBuilder->createNamedParameter($this->getMetaData()->offsetGet('uid'))),
        ];

        $queryBuilder
            ->delete('sys_category_record_mm')
            ->where(...$constraints)
            ->executeStatement();

        $i = 1;
        if (is_array($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $connectionPool
                    ->getConnectionForTable('sys_category_record_mm')
                    ->insert('sys_category_record_mm', [
                        'uid_local' => $category,
                        'uid_foreign' => $this->getMetaData()->offsetGet('uid'),
                        'tablenames' => 'sys_file_metadata',
                        'fieldname' => 'categories',
                        'sorting_foreign' => $i,
                    ]);
                $i++;
            }
        }
    }
}
