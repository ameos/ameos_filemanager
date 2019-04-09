<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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
    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments() 
    {
        $this->registerArgument('type', 'string', 'File type', true);
        $this->registerArgument('iconFolder', 'string', 'icon folder', true);
    }

    /**
     * Renders icon of extension $type
     *
     * @param string $type 
     * @param string $iconFolder
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return FilemanagerUtility::getImageIconeTagForType($arguments['type'], $arguments['iconFolder']);
    }
}
