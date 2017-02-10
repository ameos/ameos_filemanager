<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

class IsInListViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

    /**
     * Renders categorie or file
     *
     * @param int $uid 
     * @param list $list 
     * @return string 
     */
    public function render($uid=null,$list = null) {
        if(is_array($list) && in_array($uid,$list)) {
            return $this->renderThenChild();
        }
        return $this->renderElseChild();
    }
}