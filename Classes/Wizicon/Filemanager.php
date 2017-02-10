<?php

namespace Ameos\AmeosFilemanager\Wizicon;

class Filemanager {

	 /**
	 * Processing the wizard items array
	 *
	 * @param array $wizardItems The wizard items
	 * @return array Modified array with wizard items
	 */
	public function proc($wizardItems) {
		$wizardItems['plugins_ameosfilemenager_filemanager'] = array(
			'icon'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ameos_filemanager') . 'Resources/Public/Images/filemanager.gif',
			'title'       => 'File manager',
			'description' => 'Plugin permettant une gestion de documents...',
			'params'      => '&defVals[tt_content][CType]=list&&defVals[tt_content][list_type]=ameosfilemanager_fe_filemanager'
		);
		return $wizardItems;
	}
}
