<?php
namespace Ameos\AmeosFilemanager\Controller\Explorer;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\DownloadUtility;
use Ameos\AmeosFilemanager\Utility\ExplorerUtility;

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
 
abstract class AbstractController extends ActionController
{
    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository
     */
    protected $folderRepository;

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FileRepository
     */
    protected $fileRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     */
    protected $frontendUserRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository
     */
    protected $frontendUserGroupRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Handles the path resolving for *rootPath(s)
     *
     * numerical arrays get ordered by key ascending
     *
     * @param array $extbaseFrameworkConfiguration
     * @param string $setting parameter name from TypoScript
     *
     * @return array
     */
    protected function getViewProperty($extbaseFrameworkConfiguration, $setting)
    {
        $values = [];
        if (
            !empty($extbaseFrameworkConfiguration['view'][$setting])
            && is_array($extbaseFrameworkConfiguration['view'][$setting])
        ) {
            $values = ArrayUtility::sortArrayWithIntegerKeys($extbaseFrameworkConfiguration['view'][$setting]);
            $values = array_reverse($values, true);
        } elseif (
            !empty($extbaseFrameworkConfiguration['view'][$setting])
            && is_string($extbaseFrameworkConfiguration['view'][$setting])
        ) {
            $values = [$extbaseFrameworkConfiguration['view'][$setting]];
        }
        return $values;
    }

    /**
     * check current settings
     * 
     * @return bool
     */
    protected function settingsIsValid()
    {
        $settingsIsValid = true;
        if ((int)$this->settings['startFolder'] === 0) {
            $settingsIsValid = false;
            $this->addFlashMessage(LocalizationUtility::translate('missingStartFolder', 'AmeosFilemanager'), '', FlashMessage::ERROR);
        }
        if ((int)$this->settings['storage'] === 0) {
            $settingsIsValid = false;
            $this->addFlashMessage(LocalizationUtility::translate('missingStorage', 'AmeosFilemanager'), '', FlashMessage::ERROR);
        }

        // Setting feUser Repository
        if ($this->settings['stockageGroupPid'] != '') {
            $querySettings = $this->frontendUserGroupRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds(GeneralUtility::trimExplode(',', $this->settings['stockageGroupPid']));
            $this->frontendUserGroupRepository->setDefaultQuerySettings($querySettings);
        } else {
            $querySettings = $this->frontendUserGroupRepository->createQuery()->getQuerySettings();
            $querySettings->setRespectStoragePage(false);
            $this->frontendUserGroupRepository->setDefaultQuerySettings($querySettings);
        }

        return $settingsIsValid;
    }

    /**
     * return available usergroup
     * @return array
     */
    protected function getAvailableUsergroups()
    {
        if ($this->settings['authorizedGroups']) {
            $query = $this->frontendUserGroupRepository->createQuery();
            $usergroups = $query->matching($query->in('uid', GeneralUtility::trimExplode(',', $this->settings['authorizedGroups'])))->execute();
        } else {
            $usergroups = $this->frontendUserGroupRepository->findAll();
        }
        $usergroups = $usergroups->toArray();
        $anyUsergroup = $this->objectManager->get(FrontendUserGroup::class);
        $anyUsergroup->_setProperty('uid', -2);
        $anyUsergroup->setTitle(LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login', null));
        $usergroups[] = $anyUsergroup;
        return $usergroups;
    }

    /**
     * return available categories
     * @return array
     */
    protected function getAvailableCategories()
    {
        if ($this->settings['authorizedCategories']) {
            $query = $this->categoryRepository->createQuery();
            $categories = $query->matching($query->in('uid', GeneralUtility::trimExplode(',', $this->settings['authorizedCategories'])))->execute();
        } else {
            $categories = $this->categoryRepository->findByParent(0);
        }
        return $categories;
    }

    public function injectFolderRepository(FolderRepository $folderRepository): void
    {
        $this->folderRepository = $folderRepository;
    }

    public function injectFileRepository(FileRepository $fileRepository): void
    {
        $this->fileRepository = $fileRepository;
    }

    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    public function injectFrontendUserGroupRepository(FrontendUserGroupRepository $frontendUserGroupRepository): void
    {
        $this->frontendUserGroupRepository = $frontendUserGroupRepository;
    }

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }
}
