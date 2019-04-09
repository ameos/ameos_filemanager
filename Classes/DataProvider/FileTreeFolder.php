<?php
namespace Ameos\AmeosFilemanager\DataProvider;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\FlexFormService;

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
 
class FileTreeFolder extends \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider
{

    /**
     * Queries the table for an field which might contain a list.
     *
     * @param string $fieldName the name of the field to be queried
     * @param int $queryId the uid to search for
     * @return int[] all uids found
     */
    protected function listFieldQuery($fieldName, $queryId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $content = $queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', (int)GeneralUtility::_GET('uid')))
            ->execute()
            ->fetch();

        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage()->getUid();
        if ($content) {
            /** @var FlexFormService $flexFormService */
            $flexFormService = GeneralUtility::makeInstance(ObjectManager::class)->get(FlexFormService::class);
            $flexformConfiguration = $flexFormService->convertFlexFormContentToArray($content['pi_flexform']);
            if ($flexformConfiguration['settings']['storage']) {
                $storage = $flexformConfiguration['settings']['storage'];
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

        $records = $queryBuilder->execute()->fetchAll();
        $uidArray = is_array($records) ? array_column($records, 'uid') : [];

        return $uidArray;
    }


}