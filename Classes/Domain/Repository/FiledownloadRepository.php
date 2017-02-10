<?php

namespace Ameos\AmeosFilemanager\Domain\Repository;

class FiledownloadRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	protected $defaultOrderings = array(
		'crdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	);
	
	/**
	 * Initialization
	 */
	public function initializeObject() {
		$querySettings = $this->createQuery()->getQuerySettings();
		$querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
	}
}