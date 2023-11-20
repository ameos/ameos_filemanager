<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class UserService
{
    /**
     * @param ConnectionPool $connectionPool
     */
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isUserLoggedIn(): bool
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }

    /**
     * return user uid
     *
     * @return ?int
     */
    public function getUserId(): ?int
    {
        if ($this->isUserLoggedIn()) {
            $context = GeneralUtility::makeInstance(Context::class);
            return (int)$context->getPropertyFromAspect('frontend.user', 'uid');
        }
        return null;
    }

    /**
     * return user group id
     *
     * @return array
     */
    public function getUserGroups(): array
    {
        if ($this->isUserLoggedIn()) {
            $context = GeneralUtility::makeInstance(Context::class);
            return $context->getPropertyFromAspect('frontend.user', 'groupIds');
        }
        return [];
    }   

    /**
     * Returns available usergroup for current user
     *
     * @return array
     */
    public function getAvailableUsergroups($settings): array
    {
        $usergroups = [];
        if ($this->isUserLoggedIn()) {
            $usergroups[-2] = LocalizationUtility::translate(
                'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                null
            );
            $currentUserGroups = $this->getUserGroups();
            
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_groups');
            $queryBuilder->select('*')->from('fe_groups');
            if ($settings['authorizedGroups']) {
                $queryBuilder->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::trimExplode(',', $settings['authorizedGroups']),
                            ArrayParameterType::INTEGER
                        )
                    )
                );
            }
            $results = $queryBuilder->executeQuery()->fetchAllAssociative();            
            
            
            foreach ($results as $index => $group) {
                if (in_array($group['uid'], $currentUserGroups)) {
                    $usergroups[(int)$group['uid']] = $group['title'];
                }
            }
        }
        return $usergroups;
    }
}