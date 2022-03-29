<?php

namespace Ameos\AmeosFilemanager\EventListener\Core\Resource;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;

class AbstractFolderEventListener
{
    /** @var ConnectionPool */
    protected $connectionPool;

    /** @var FolderRepository */
    protected $folderRepository;

    /** @param ConnectionPool $connectionPool */
    public function injectConnectionPool(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /** @param FolderRepository $folderRepository */
    public function injectFolderRepository(FolderRepository $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }
}
