<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

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

class IsInListViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper
{

    /**
     * Renders categorie or file
     *
     * @param int $uid 
     * @param list $list 
     * @return string 
     */
    public function render($uid=null, $list = null)
    {
        if (is_array($list) && in_array($uid,$list)) {
            return $this->renderThenChild();
        }
        return $this->renderElseChild();
    }
}
