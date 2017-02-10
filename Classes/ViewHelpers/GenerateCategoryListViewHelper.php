<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
class GenerateCategoryListViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * Renders icon of extension $type
     *
     * @param array $categories
     * @return string
     */
    public function render($categories) {
        $test = GeneralUtility::makeInstance('\\TYPO3\\CMS\\Fluid\\View\\StandaloneView');    
        $test->setControllerContext($this->controllerContext);
        $templatePath = ExtensionManagementUtility::extPath('ameos_filemanager') . 'Resources/Private/Templates/ViewHelpers/Checkbox.html';
        $test->setTemplatePathAndFilename($templatePath);
        $test->assign("name" , "name");
        $test->assign("value" , "value");
        $test->assign("label" , "label");
        
        return $test->render();
    }
}