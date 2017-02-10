<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Tools\Tools;

class CanAddFileViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper
{

    /**
     * Initializes arguments
     */
    public function __construct()
    {
        parent::__construct();
        $this->registerArgument('folder',    'Ameos\\AmeosFilemanager\\Domain\\Model\\Folder', 'Folder value.', false);
        $this->registerArgument('arguments', 'array', 'Arguments.', false);
    }

    /**
     * This method decides if the condition is TRUE or FALSE
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    static protected function evaluateCondition($arguments = null)
    {
        $user = $GLOBALS['TSFE']->fe_user->user;
		return Tools::userHasAddFileAccess($user, $arguments['folder'], $arguments['arguments']);
    }
}
