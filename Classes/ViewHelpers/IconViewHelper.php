<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

class IconViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * Renders icon of extension $type
     *
     * @param string $type 
     * @param string $iconFolder
     * @return string
     */
    public function render($type=null,$iconFolder=null) {
            return \Ameos\AmeosFilemanager\Tools\Tools::getImageIconeTagForType($type,$iconFolder);
    }
}