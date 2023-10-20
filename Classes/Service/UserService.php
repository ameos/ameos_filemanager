<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserGroupRepository;

class UserService
{
    /**
     * @param FrontendUserGroupRepository $frontendUserGroupRepository
     */
    public function __construct(protected FrontendUserGroupRepository $frontendUserGroupRepository)
    {
        
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }

    /**
     * Returns available usergroup for current user
     *
     * @return array
     */
    public function getAvailableUsergroups($settings)
    {
        // TODO V12
        return [];
        if ($this->isUserLoggedIn()) {
            if ($settings['authorizedGroups']) {
                $query = $this->frontendUserGroupRepository->createQuery();
                $usergroups = $query->matching(
                    $query->in(
                        'uid',
                        GeneralUtility::trimExplode(
                            ',',
                            $settings['authorizedGroups']
                        )
                    )
                )->execute();
            } else {
                $usergroups = $this->frontendUserGroupRepository->findAll();
            }
            $usergroups = $usergroups->toArray();

            $currentUserGroups = explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']);
            foreach ($usergroups as $index => $group) {
                if (!in_array($group->getUid(), $currentUserGroups)) {
                    unset($usergroups[$index]);
                }
            }

            $anyUsergroup = GeneralUtility::makeInstance(FrontendUserGroup::class);
            $anyUsergroup->_setProperty('uid', -2);
            $anyUsergroup->setTitle(
                LocalizationUtility::translate(
                    'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                    null
                )
            );
            $usergroups[] = $anyUsergroup;
        } else {
            $usergroups = [];
        }
        return $usergroups;
    }
}