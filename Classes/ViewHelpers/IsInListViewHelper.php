<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class IsInListViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'identifier', false);
        $this->registerArgument('list', 'mixed', 'The list (array)', false);
    }

    /**
     * This method decides if the condition is TRUE or FALSE
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    public static function evaluateCondition($arguments = null)
    {
        if (is_string($arguments['list'])) {
            $arguments['list'] = GeneralUtility::trimExplode(',', $arguments['list']);
        }

        return is_array($arguments['list']) && in_array($arguments['uid'], $arguments['list']);
    }
}
