<?php
namespace Ameos\AmeosFilemanager\Wizicon;

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

class Filemanager {

	 /**
	 * Processing the wizard items array
	 *
	 * @param array $wizardItems The wizard items
	 * @return array Modified array with wizard items
	 */
	public function proc($wizardItems)
    {
		$wizardItems['plugins_ameosfilemenager_filemanager'] = array(
			'icon'        => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ameos_filemanager') . 'Resources/Public/Images/filemanager.gif',
			'title'       => 'File manager',
			'description' => 'Plugin permettant une gestion de documents...',
			'params'      => '&defVals[tt_content][CType]=list&&defVals[tt_content][list_type]=ameosfilemanager_fe_filemanager'
		);
		return $wizardItems;
	}
}
