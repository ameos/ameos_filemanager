<?php

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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

class ExportController extends ActionController
{
    /**
     * ResourceFactory object
     *
     * @var ResourceFactory
     * @Inject
     */
    protected $resourceFactory;

    /**
     * FolderRepository object
     *
     * @var FolderRepository
     * @Inject
     */
    protected $folderRepository;

    /**
     * Inject ResourceFactory object
     *
     * @param ResourceFactory $resourceFactory ResourceFactory object
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Inject FolderRepository object
     *
     * @param FolderRepository $folderRepository FolderRepository object
     */
    public function injectFolderRepository(FolderRepository $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }

    /**
     * Index action
     */
    protected function indexAction()
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/AmeosFilemanager/Export');

        $folderIdentifier = GeneralUtility::_GET('id');
        $folderResource = $this->resourceFactory->retrieveFileOrFolderObject($folderIdentifier);

        $folder = $this->folderRepository->findRawByStorageAndIdentifier(
            $folderResource->getStorage()->getUid(),
            $folderResource->getIdentifier()
        );

        $this->view->assign(Configuration::FOLDER_ARGUMENT_KEY, $folder);
    }

    /**
     * Export action
     */
    protected function exportAction()
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export.csv";');
        header('Content-Transfer-Encoding: binary');

        echo '"' . LocalizationUtility::translate('export.filename', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.createdAt', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.updatedAt', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.description', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.owner', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.size', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.keywords', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.path', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.nbDownload', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.extension', Configuration::EXTENSION_KEY) . '"' . "\n";

        $folders = [];
        $folders[] = (int)$this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY);

        if ($this->request->getArgument('subfolders') == 1) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Configuration::FOLDER_TABLENAME);
            $folder = $queryBuilder
                ->select('*')
                ->from(Configuration::FOLDER_TABLENAME, Configuration::FOLDER_ARGUMENT_KEY)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        (int)$this->request->getArgument(configuration::FOLDER_ARGUMENT_KEY)
                    )
                )
                ->execute()
                ->fetch();

            $subfolders = $this->folderRepository->findRawByStorageAndIdentifier(
                $folder['storage'],
                $folder['identifier'] . '%'
            );
            if ($subfolders) {
                foreach ($subfolders as $subfolder) {
                    $folders[] = $subfolder['uid'];
                }
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('meta.*', 'file.name', 'file.size', 'file.extension', 'file.identifier', 'users.username')
            ->from('sys_file_metadata', 'meta')
            ->join('meta', 'sys_file', 'file', 'file.uid = meta.file')
            ->leftJoin('meta', 'fe_users', 'users', 'users.uid = meta.fe_user_id')
            ->where($queryBuilder->expr()->in('meta.folder_uid', $folders))
            ->execute();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Configuration::FILEDOWNLOAD_TABLENAME)
            ->count('uid', 'nb_downloads')
            ->from(Configuration::FILEDOWNLOAD_TABLENAME);

        $constraints = [];
        if ($this->request->hasArgument('start')) {
            $startArg = $this->request->getArgument('start');
            if (preg_match('/\d{2}\.\d{2}\.\d{4}/i', $startArg)) {
                $datetime = \DateTime::createFromFormat(
                    'd.m.Y H:i:s',
                    $startArg . ' 00:00:00'
                );
                $constraints[] = $queryBuilder->expr()->gte(
                    'crdate',
                    $queryBuilder->createNamedParameter((int)$datetime->getTimestamp(), \PDO::PARAM_INT)
                );
            }
        }
        if ($this->request->hasArgument('end')) {
            $endArg = $this->request->getArgument('end');
            if (preg_match('/\d{2}\.\d{2}\.\d{4}/i', $endArg)) {
                $datetime = \DateTime::createFromFormat(
                    'd.m.Y H:i:s',
                    $endArg . ' 23:59:59'
                );
                $constraints[] = $queryBuilder->expr()->lte(
                    'crdate',
                    $queryBuilder->createNamedParameter((int)$datetime->getTimestamp(), \PDO::PARAM_INT)
                );
            }
        }

        while ($row = $statement->fetch()) {
            $fileConstraint = $queryBuilder->expr()->eq(
                'file',
                $queryBuilder->createNamedParameter((int)$row['file'], \PDO::PARAM_INT)
            );

            $downloaded = $queryBuilder
                ->where(
                    ...array_merge(
                        $constraints,
                        [$fileConstraint]
                    )
                )
                ->execute()
                ->fetchColumn(0);

            echo '"' . ($row['title'] ? $row['title'] : $row['name']) . '";';
            echo '"' . strftime('%d/%m/%Y', $row['crdate']) . '";';
            echo '"' . strftime('%d/%m/%Y', $row['tstamp']) . '";';
            echo '"' . $row['description'] . '";';
            echo '"' . $row['username'] . '";';
            echo '"' . $row['size'] . '";';
            echo '"' . $row['keywords'] . '";';
            echo '"' . $row['identifier'] . '";';
            echo '"' . (int)$downloaded . '";';
            echo '"' . $row['extension'] . '";' . "\n";
        }
        exit;
    }
}
