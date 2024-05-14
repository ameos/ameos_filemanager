<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\DataProvider;

use Ameos\AmeosFilemanager\Enum\Configuration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileTreeFolder extends DatabaseTreeDataProvider
{
    /**
     * Queries the table for an field which might contain a list.
     *
     * @param string $fieldName the name of the field to be queried
     * @param int $queryId the uid to search for
     * @return int[] all uids found
     */
    protected function listFieldQuery(string $fieldName, int $queryId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $content = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', (int)$_GET['uid']))
            ->executeQuery()
            ->fetchAssociative();

        $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage()->getUid();
        if ($content) {
            /** @var FlexFormService $flexFormService */
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $flexformConfiguration = $flexFormService->convertFlexFormContentToArray($content['pi_flexform']);
            if (isset($flexformConfiguration['settings'])
                && isset($flexformConfiguration['settings'][Configuration::SETTINGS_STORAGE])
            ) {
                $storage = $flexformConfiguration['settings'][Configuration::SETTINGS_STORAGE];
            }
        }

        $queryId = (int)$queryId;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->getTableName());
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder->select('uid')
            ->from($this->getTableName())
            ->where(
                $queryBuilder->expr()->inSet($fieldName, $queryBuilder->quote($queryId)),
                $queryBuilder->expr()->eq('storage', $storage)
            );

        if ($queryId === 0) {
            $queryBuilder->orWhere(
                $queryBuilder->expr()->comparison(
                    'CAST(' . $queryBuilder->quoteIdentifier($fieldName) . ' AS CHAR)',
                    ExpressionBuilder::EQ,
                    $queryBuilder->quote('')
                )
            );
        }

        $records = $queryBuilder->executeQuery()->fetchAllAssociative();
        return is_array($records) ? array_column($records, 'uid') : [];
    }
}
