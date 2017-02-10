<?php

namespace Ameos\AmeosFilemanager\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    
    /**
     * Homepage
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign("filemanagerPid",$this->settings['filemanagerPid']);
    }

}

