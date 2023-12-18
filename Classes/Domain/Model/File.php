<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File as ModelFile;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
     * @var ObjectStorage
     */
    protected $categories;

    /**
     * @var object meta
     */
    protected $cruser = false;

    /**
     * @var object meta
     */
    protected $feuser = false;

    /**
     * construct
     */
    public function __construct()
    {
        $this->categories = new ObjectStorage();   
    }

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
            'image/svg+xml'
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
     * @return int
     */
    public function getCruser()
    {
        return $this->getMetaData()->offsetGet('cruser_id');
    }

    /**
     * @return int
     */
    public function getTstamp()
    {
        return $this->getMetaData()->offsetGet('tstamp');
    }

    /**
     * @return int
     */
    public function getFeUser()
    {
        return $this->getMetaData()->offsetGet('fe_user_id');
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->getMetaData()->offsetGet('keywords');
    }

    /**
     * @return int
     */
    public function getFolder()
    {
        return (int)$this->getMetaData()->offsetGet('folder_uid');
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
    public function getPublicUrl()
    {
        return $this->getOriginalResource()->getPublicUrl();
    }

    /**
     * Returns the cats
     *
     * @return ObjectStorage<Category>
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * set categories
     *
     * @param ObjectStorage $categories
     * @return self
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
        return $this;   
    }
}
