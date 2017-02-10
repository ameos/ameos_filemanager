<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Tools\Tools;

class CheckAccessViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

	 /**
     * Check user access to folder or file
     *
     * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
     * @param \Ameos\AmeosFilemanager\Domain\Model\_Folder $folder
     * @param string $right
     * @param array $arguments Arguments
     * @return string the rendered string
     */
    public function render($file=null,$folder=null,$right=null, $arguments=null) {
		$user = ($GLOBALS['TSFE']->fe_user->user);
        if(($file==null && $folder==null) || $right==null) {
            return $this->renderElseChild();
        }
        if($folder != null) {   
            if($right=="r") {
                return Tools::userHasFolderReadAccess($user, $folder, $arguments) ? $this->renderThenChild() : $this->renderElseChild();
            }
            else if($right=="w") {
                return Tools::userHasFolderWriteAccess($user, $folder, $arguments) ? $this->renderThenChild() : $this->renderElseChild();
            }
            else {
                return $this->renderElseChild();
            }
        }
        else if($file != null) {
            if($right=="r") {
                return Tools::userHasFileReadAccess($user, $file, $arguments) ? $this->renderThenChild() : $this->renderElseChild();
            }
            else if($right=="w") {
            	return Tools::userHasFileWriteAccess($user, $file, $arguments) ? $this->renderThenChild() : $this->renderElseChild();
            }
            else {
            	return $this->renderElseChild();				
            }
    	}
    	else {
    		return $this->renderElseChild();
        }
    	return $this->renderElseChild();
	}

    static protected function evaluateCondition($arguments = null)
    {
        $user = ($GLOBALS['TSFE']->fe_user->user);
        if(($arguments['file']==null && $arguments['folder']==null) || $arguments['right']==null) {
            return false;
        }
        if($arguments['folder'] != null) {   
            if($arguments['right']=="r") {
                return Tools::userHasFolderReadAccess($user, $arguments['folder'], $arguments['arguments']) ? true : false;
            }
            else if($arguments['right']=="w") {
                return Tools::userHasFolderWriteAccess($user, $arguments['folder'], $arguments['arguments']) ? true : false;
            }
            else {
                return false;
            }
        }
        else if($arguments['file'] != null) {
            if($arguments['right']=="r") {
                return Tools::userHasFileReadAccess($user, $arguments['file'], $arguments['arguments']) ? true : false;
            }
            else if($arguments['right']=="w") {
                return Tools::userHasFileWriteAccess($user, $arguments['file'], $arguments['arguments']) ? true : false;
            }
            else {
                return false;                
            }
        }
        else {
            return false;
        }
        return false;
    }
}
