<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class DisplayModeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Renders display mode input
     *
     * @param string $class
     * @return string 
     */
    public function render($class = null)
    {
         // TODO Multiple plugin in one page ?????
         
        $class = $class ? ' class="' . $class . '"' : '';
        $settings = $this->templateVariableContainer->get('settings');
        $availableMode = GeneralUtility::trimExplode(',', $settings['availableMode']);

        if (count($availableMode) == 1) {
            return '<input type="hidden" id="display_mode" value="' . $availableMode[0] . '" />';
        } else {
            $output = '<select id="display_mode"' . $class . '>';
            $output .='<option selected="selected" value="' . $availableMode[0] . '">' . $availableMode[0] . '</option>';
            $output .='<option value="' . $availableMode[1] . '">' . $availableMode[1] . '</option>';
            $output .='</select>';
            return $output;
        }
    }
}
