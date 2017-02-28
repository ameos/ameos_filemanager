<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;

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

class IconViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Renders icon of extension $type
     *
     * @param string $type 
     * @param string $iconFolder
     * @return string
     */
    public function render($type=null, $iconFolder=null)
    {
        return FilemanagerUtility::getImageIconeTagForType($type, $iconFolder);
    }
}
