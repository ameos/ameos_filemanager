<?php
namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
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
            $metaDataRepository = GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Index\MetaDataRepository');
            $this->meta = $metaDataRepository->findByFileUid($this->getUid());
        }
        return $this->meta;
    }

    /**
     * return status
     */
    public function getStatus()
    {
        return $this->getMeta()['status'];
    }

    /**
     * @return int
     */ 
    public function getDatetime()
    {
        return $this->getMeta()['datetime'];
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
            $beUserRepository = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository');
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
     * @return Tx_Extbase_Domain_Model_FrontendUser
     */
    public function getFeUser()
    {
        if ($this->feuser === FALSE) {
            $feUserRepository = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository');
            $this->feuser = $feUserRepository->findByUid($this->getMeta()['fe_user_id']);
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
            $folderRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
            self::$folders[$this->getMeta()['folder_uid']] = $folderRepository->findByUid($this->getMeta()['folder_uid']);
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
        $extbaseObjectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $repo = $extbaseObjectManager->get('TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository');

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
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $cats
     */
    public function getCategoriesUids()
    {
        $test = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'uid_local',
            'sys_category_record_mm',
            'tablenames like "sys_file_metadata" AND fieldname like "categories" AND uid_foreign = ' . $this->getMeta()['uid'],
            '',
            'sorting_foreign'
        );

        $uidsCat = array_map(function ($e) {
            return $e['uid_local'];
        }, $test);

        return $uidsCat;
    }

    public function setCategories($categories)
    {
        if (is_array($categories)) {
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                'sys_category_record_mm',
                'tablenames like "sys_file_metadata" AND fieldname like "categories" AND uid_foreign = ' . $this->getMeta()['uid']
            );

            $i = 1;
            foreach ($categories as $category) {
                $fields_values = array(
                    "uid_local" => $category,
                    "uid_foreign" => $this->getMeta()['uid'],
                    "tablenames" => "sys_file_metadata",
                    "fieldname" => "categories",
                    "sorting_foreign" => $i,
                );
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_category_record_mm', $fields_values, $no_quote_fields=FALSE);
                $i++;
            }
        }
    }
}
