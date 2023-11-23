<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\XClass;

use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Dto\ResourceView;
use TYPO3\CMS\Filelist\FileList as TYPO3FileList;

class FileList extends TYPO3FileList
{
    protected function createEditDataUriForResource(ResourceInterface $resource): ?string
    {
        if ($resource instanceof Folder) {            
            /** @var QueryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');

            $folder = $queryBuilder
                ->select('*')
                ->from('tx_ameosfilemanager_domain_model_folder')
                ->where(
                    $queryBuilder->expr()->like(
                        'identifier',
                        $queryBuilder->createNamedParameter($resource->getIdentifier())
                    )
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($folder) {
                $parameter = [
                    'edit' => ['tx_ameosfilemanager_domain_model_folder' => [$folder['uid'] => 'edit']],
                    'returnUrl' => $this->createModuleUri(),
                ];
                return (string)$this->uriBuilder->buildUriFromRoute('record_edit', $parameter);
            }
        }

        return parent::createEditDataUriForResource($resource);
    }

    protected function createControlEditMetaData(ResourceView $resourceView): ?ButtonInterface
    {
        if ($resourceView->editDataUri) {
            $button = GeneralUtility::makeInstance(LinkButton::class);
            $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
            $button->setHref($resourceView->editDataUri);
            $button->setIcon($this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL));

            return $button;
        }
        return parent::createControlEditMetaData($resourceView);
    }
}
