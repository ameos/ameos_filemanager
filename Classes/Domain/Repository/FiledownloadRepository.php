<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class FiledownloadRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = ['crdate' => QueryInterface::ORDER_DESCENDING];

    /**
     * Initialization
     */
    public function initializeObject()
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }
}
