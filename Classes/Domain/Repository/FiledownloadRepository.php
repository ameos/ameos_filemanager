<?php
namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

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
 
class FiledownloadRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var array
     */ 
	protected $defaultOrderings = array('crdate' => QueryInterface::ORDER_DESCENDING);
	
	/**
	 * Initialization
	 */
	public function initializeObject()
    {
		$querySettings = $this->createQuery()->getQuerySettings();
		$querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
	}
}
