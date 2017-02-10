<?php

namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	protected $defaultOrderings = array(
		'tstamp' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	);

	/**
	 * Initialization
	 */
	public function initializeObject() {
		$querySettings = $this->createQuery()->getQuerySettings();
		$querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
	}

	public function findFilesForFolder($folder) {
		if(empty($folder)) {
			return $this->findAll();
		}

		$fields = 'sys_file.*'; 
		$from = 'sys_file, sys_file_metadata
            LEFT JOIN fe_users ON sys_file_metadata.fe_user_id = fe_users.uid';
		$where = "sys_file_metadata.file = sys_file.uid AND sys_file_metadata.folder_uid = " . (int)$folder;

        $order = '';
        $get = GeneralUtility::_GET('tx_ameosfilemanager_fe_filemanager');
        $availableSorting = [
            'sys_file.name', 'sys_file.creation_date', 'sys_file.modification_date', 'sys_file.size',
            'sys_file.tstamp', 'sys_file.crdate',
            'sys_file_metadata.tstamp', 'sys_file_metadata.crdate', 'sys_file_metadata.description',
            'sys_file_metadata.title', 'sys_file_metadata.categories', 'sys_file_metadata.keywords',
            'fe_users.name', 'fe_users.username', 'fe_users.company',
        ];
    
        if (isset($get['sort']) && $get['sort'] != '' && in_array($get['sort'], $availableSorting)) {
            $direction = (isset($get['direction']) && $get['direction'] != '') ? $get['direction'] : 'ASC';
            $order = $get['sort'] . ' ' . $direction;
        }
        
		$query = $this->createQuery();
        $query->statement($GLOBALS['TYPO3_DB']->SELECTquery(
			$fields, 
			$from, 
			$where,
            '',
            $order
		));
		return $query->execute();
	}

    /**
     * return files identifiers for folder recursively
     * @param string folders
     */
    protected function getFilesIdentifiersRecursively($folders)
    {
        $files = [];
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('file', 'sys_file_metadata', 'folder_uid IN (' . $folders . ')');
        while (($file = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== false) {
            $files[] = $file['file'];
        }

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_ameosfilemanager_domain_model_folder', 'uid_parent IN (' . $folders . ')');
        $childs = [];
        while (($folder = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) !== false) {
            $childs[] = $folder['uid'];
        }
        if (!empty($childs)) {
            $files = array_merge($files, $this->getFilesIdentifiersRecursively(implode(',', $childs)));    
        }
        return $files;
    }

	/**
	 * Return all filter by search criterias
	 * @param array $criterias criterias
     * @param int $rootFolder
	 */
	public function findBySearchCriterias($criterias, $rootFolder = null) {
		if(!is_array($criterias) || empty($criterias)) {
			return $this->findAll();
		}

        $rootFolder = (!is_null($rootFolder) && is_object($rootFolder)) ? $rootFolder->getUid() : $rootFolder;

        $additionnalWhereClause = '';
        if (!is_null($rootFolder) && (int)$rootFolder > 0) {
            $availableFilesIdentifiers = $this->getFilesIdentifiersRecursively($rootFolder);
            if (empty($availableFilesIdentifiers)) {
                $additionnalWhereClause = ' AND sys_file.uid = 0';    
            } else {
                $additionnalWhereClause = ' AND sys_file.uid IN (' . implode(',', $availableFilesIdentifiers) . ')';
            }
        }
        
		$fields = 'distinct sys_file.*'; 
		$from = 'sys_file_metadata INNER JOIN sys_file  ON sys_file_metadata.file=sys_file.uid LEFT JOIN sys_category_record_mm ON sys_file_metadata.uid = sys_category_record_mm.uid_foreign LEFT JOIN sys_category ON sys_category_record_mm.uid_local = sys_category.uid';
		$where = '1';
		if(isset($criterias['keyword']) && $criterias['keyword'] !== '') {
			$arrayKeywords = explode(' ', $criterias['keyword']);
			$arrayCondition = array();
			$where .= " AND (sys_category_record_mm.tablenames LIKE 'sys_file_metadata' OR sys_category_record_mm.tablenames IS NULL) ";
			foreach ($arrayKeywords as $keyword) {
				$where .= "AND ( ";
				$where .= " sys_file_metadata.title LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file_metadata');
			    $where .= " OR sys_file_metadata.description LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file_metadata'); 
			    $where .= " OR sys_file_metadata.keywords LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file_metadata');
			    $where .= " OR sys_file.name LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_file');
			    $where .= " OR sys_category.title LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $keyword . '%', 'sys_category');
			    $where .= ") ";
			}
		}
        $where .= $additionnalWhereClause;

        $order = '';
        $get = GeneralUtility::_GET('tx_ameosfilemanager_fe_filemanager');
        $availableSorting = [
            'sys_file.name', 'sys_file.creation_date', 'sys_file.modification_date', 'sys_file.size',
            'sys_file.tstamp', 'sys_file.crdate',
            'sys_file_metadata.tstamp', 'sys_file_metadata.crdate', 'sys_file_metadata.description',
            'sys_file_metadata.title', 'sys_file_metadata.categories', 'sys_file_metadata.keywords',
            'fe_users.name', 'fe_users.username', 'fe_users.company',
        ];
    
        if (isset($get['sort']) && $get['sort'] != '' && in_array($get['sort'], $availableSorting)) {
            $direction = (isset($get['direction']) && $get['direction'] != '') ? $get['direction'] : 'ASC';
            $order = $get['sort'] . ' ' . $direction;
        }
        
		$query = $this->createQuery();
        $query->statement($GLOBALS['TYPO3_DB']->SELECTquery(
			$fields, 
			$from, 
			$where,
            '',
            $order
		));
		return $query->execute();
	}

	public function findAuthorizedFiles($user, $minDatetime = 0) {
		$query = $this->createQuery();

		$fields       = 'distinct sys_file.uid, sys_file_metadata.folder_uid';
		$tables       = 'sys_file_metadata, sys_file';		
		$orderBy      = 'sys_file_metadata.datetime DESC';
		$whereClauses = array();
		$whereClauses[] = 'sys_file_metadata.file = sys_file.uid';
		$whereClauses[] = 'sys_file_metadata.datetime >= ' . (int)$minDatetime;
		$whereClauses[] = '(
			sys_file_metadata.fe_user_id = ' . (int)$user['uid'] . ' OR
			sys_file_metadata.no_read_access = 0
		)';

		$query->statement($GLOBALS['TYPO3_DB']->SELECTquery($fields, $tables, implode(' AND ', $whereClauses), '', $orderBy));
		return $query->execute();
	}

	public function findAll() {

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'distinct sys_file.uid', 
			'sys_file_metadata INNER JOIN sys_file ON sys_file_metadata.file=sys_file.uid', 
			'',
			''
		);
		
		$uid = array();
		foreach($res as $r){
			$uid[] = $r['uid'];
		}
		
		$query = $this->createQuery();
		$query->matching($query->in('uid', $uid));
		return $query->execute();
	}

	public function findByUid($fileUid,$writeRight=false) {
		if(empty($fileUid)) {
			return 0;
		}
		if($writeRight) {
			$column = 'fe_group_write';
		}
		else {
			$column = 'fe_group_read';	
		}
		$userGroups = $GLOBALS['TSFE']->gr_list;

		$query = $this->createQuery();		
		$where = 'sys_file.uid = ' . (int)$fileUid;
		$where .= " AND (( 
			sys_file_metadata.".$column."='' 
			OR sys_file_metadata.".$column." IS NULL 
			OR sys_file_metadata.".$column."='0' ";
		foreach (explode(',', $userGroups) as $userGroup) {
			$where .= "OR FIND_IN_SET('".$userGroup."',sys_file_metadata.".$column.") ";
		}
		if($GLOBALS['TSFE']->fe_user->user) {
			$where .= ') OR sys_file_metadata.fe_user_id = '.$GLOBALS['TSFE']->fe_user->user['uid'] . ')';	
		}
		else {
			$where .= '))';
		}
		
		$query->statement
		(	'	SELECT distinct sys_file.uid, sys_file_metadata.folder_uid 
				FROM sys_file_metadata
				INNER JOIN sys_file 
				ON sys_file_metadata.file=sys_file.uid
				WHERE '.$where.'
				ORDER BY sys_file_metadata.datetime DESC 
			',
			array()
		);
		
        $res = $query->execute()->getFirst();
		return $res;
	}
}
