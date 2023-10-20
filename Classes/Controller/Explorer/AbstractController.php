<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Domain\Repository\CategoryRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

abstract class AbstractController extends ActionController
{
    /**
     * @var FolderRepository
     */
    protected FolderRepository $folderRepository;

    /**
     * @var FileRepository
     */
    protected FileRepository $fileRepository;

    /**
     * @var CategoryRepository
     */
    protected CategoryRepository $categoryRepository;

    /**
     * @var PersistenceManager
     */
    protected PersistenceManager $persistenceManager;

    /**
     * @var ResourceFactory
     */
    protected ResourceFactory $resourceFactory;

    public function __construct(
        FolderRepository $folderRepository,
        FileRepository $fileRepository,
        CategoryRepository $categoryRepository,
        PersistenceManager $persistenceManager,
        ResourceFactory $resourceFactory
    ) {
        $this->folderRepository = $folderRepository;
        $this->fileRepository = $fileRepository;
        $this->categoryRepository = $categoryRepository;
        $this->persistenceManager = $persistenceManager;
        $this->resourceFactory = $resourceFactory;
    }

    protected function initializeAction()
    {
        $this->checkValidSettings();
    }

    /**
     * Handles the path resolving for *rootPath(s)
     *
     * Numerical arrays get ordered by key ascending
     *
     * @param array $extbaseFrameworkConfiguration Extbase config
     * @param string $setting Parameter name from TypoScript
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
     * Check current settings
     *
     * @return bool
     */
    protected function settingsIsValid()
    {
        $settingsIsValid = true;
        if ((int)$this->settings[Configuration::START_FOLDER_SETTINGS_KEY] === 0) {
            $settingsIsValid = false;
            $this->addFlashMessage(
                LocalizationUtility::translate('missingStartFolder', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
        }
        if ((int)$this->settings['storage'] === 0) {
            $settingsIsValid = false;
            $this->addFlashMessage(
                LocalizationUtility::translate('missingStorage', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
        }

        // Setting feUser Repository
      /*  if ($this->settings['stockageGroupPid'] != '') {
            $querySettings = $this->frontendUserGroupRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds(GeneralUtility::trimExplode(',', $this->settings['stockageGroupPid']));
            $this->frontendUserGroupRepository->setDefaultQuerySettings($querySettings);
        } else {
            $querySettings = $this->frontendUserGroupRepository->createQuery()->getQuerySettings();
            $querySettings->setRespectStoragePage(false);
            $this->frontendUserGroupRepository->setDefaultQuerySettings($querySettings);
        } */

        return $settingsIsValid;
    }

    /**
     * Returns available usergroup for current user
     *
     * @return array
     */
    protected function getAvailableUsergroups()
    {
        return [];
        if ($this->isUserLoggedIn()) {
            if ($this->settings['authorizedGroups']) {
                $query = $this->frontendUserGroupRepository->createQuery();
                $usergroups = $query->matching(
                    $query->in(
                        'uid',
                        GeneralUtility::trimExplode(
                            ',',
                            $this->settings['authorizedGroups']
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

    /**
     * Returns available categories
     *
     * @return array
     */
    protected function getAvailableCategories()
    {
        return [];
        if ($this->settings['authorizedCategories']) {
            $query = $this->categoryRepository->createQuery();
            $categories = $query->matching(
                $query->in(
                    'uid',
                    GeneralUtility::trimExplode(
                        ',',
                        $this->settings['authorizedCategories']
                    )
                )
            )->execute();
        } else {
            $categories = $this->categoryRepository->findByParent(0);
        }
        return $categories;
    }

    /**
     * Get Plugin Configuration
     *
     * @return array
     */
    protected function getPluginConfiguration()
    {
        $configuration = $this->configurationManager
            ->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($configuration['view'][Configuration::PLUGIN_NAMESPACE_KEY])) {
            $configuration['view'][Configuration::PLUGIN_NAMESPACE_KEY] = 'tx_ameosfilemanager_fe_filemanager_explorer';
        }
        return $configuration;
    }

    /**
     * Checks valid settings
     */
    protected function checkValidSettings()
    {
        if (!$this->settingsIsValid()) {
            $this->forward(Configuration::ERROR_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
        }
    }

    /**
     * Checks folder argument existence
     */
    protected function checkFolderArgumentExistence()
    {
        if (
            !$this->request->hasArgument(Configuration::FOLDER_ARGUMENT_KEY)
            || (int)$this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY) === 0
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('missingFolderArgument', Configuration::EXTENSION_KEY),
                '',
                FlashMessage::ERROR
            );
            $this->forward(Configuration::ERROR_ACTION_KEY, Configuration::EXPLORER_CONTROLLER_KEY);
        }
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    protected function isUserLoggedIn()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }
}
