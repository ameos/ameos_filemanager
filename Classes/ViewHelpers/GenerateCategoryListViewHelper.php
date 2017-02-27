<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
 
class GenerateCategoryListViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Renders icon of extension $type
     *
     * @param array $categories
     * @return string
     */
    public function render($categories)
    {
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
