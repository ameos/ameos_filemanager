<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[Controller]
class ExportController extends ActionController
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly FolderRepository $folderRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $this->exportAction($request);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Module d\'export / import de pages');
        return $this->indexAction($request, $moduleTemplate);
    }

    /**
     * Index action
     *
     * @param ServerRequestInterface $request
     * @param ModuleTemplate $view
     * @return ResponseInterface
     */
    protected function indexAction(ServerRequestInterface $request, ModuleTemplate $view): ResponseInterface
    {
        $folderIdentifier = $request->getQueryParams()['id'];
        $folderResource = $this->resourceFactory->retrieveFileOrFolderObject($folderIdentifier);

        $folder = $this->folderRepository->findRawByStorageAndIdentifier(
            $folderResource->getStorage()->getUid(),
            $folderResource->getIdentifier()
        );

        $view->assign(Configuration::FOLDER_ARGUMENT_KEY, $folder);
        return $view->renderResponse('Backend/Export/Index');
    }
    
    /**
     * Export action
     *
     * @param ServerRequestInterface $request
     */
    protected function exportAction(ServerRequestInterface $request)
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
                ->executeQuery()
                ->fetchAssociative();

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
            ->executeQuery();

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

        while ($row = $statement->fetchAssociative()) {
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
                ->executeQuery()
                ->fetchOne();

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
