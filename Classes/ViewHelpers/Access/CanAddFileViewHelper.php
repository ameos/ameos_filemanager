<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers\Access;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Service\AccessService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class CanAddFileViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('folder', Folder::class, 'Folder value.', false);
    }

    /**
     * verdict for condition
     * @param array<string, mixed> $arguments
     * @param RenderingContextInterface $renderingContext
     * @return bool
     */
    public static function verdict(array $arguments, RenderingContextInterface $renderingContext)
    {
        $accessService = GeneralUtility::makeInstance(AccessService::class);
        return $accessService->canAddFile($GLOBALS['TSFE']->fe_user->user, $arguments['folder']);
    }
}
