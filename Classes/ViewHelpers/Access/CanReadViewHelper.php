<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers\Access;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Exception\MissingArgumentException;
use Ameos\AmeosFilemanager\Service\AccessService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class CanReadViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('folder', Folder::class, 'Folder', false);
        $this->registerArgument('file', File::class, 'File', false);
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

        if (!is_a($arguments['folder'], Folder::class) && !is_a($arguments['file'], File::class)) {
            throw new MissingArgumentException(
                'DisplayRowViewHelper : Folder or File are required'
            );
        }

        $granted = false;
        if (is_a($arguments['folder'], Folder::class)) {
            $granted = $accessService->canReadFolder($arguments['folder']);
        }
        if (is_a($arguments['file'], File::class)) {
            $granted = $accessService->canReadFile($arguments['file']);
        }

        return $granted;
    }
}
