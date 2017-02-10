<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ameos_filemanager".
 *
 * Auto generated 21-03-2016 14:15
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'File manager',
  'description' => 'This will allow you to fully handle file upload in FE context',
  'category' => 'plugin',
  'author' => 'Ameos',
  'author_email' => 'typo3dev@ameos.com',
  'author_company' => 'Ameos',
  'state' => 'beta',
  'uploadfolder' => false,
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'version' => '1.0.2',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '6.2.0-7.99.99',
      'php' => '5.4.1-7.0.99',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  'clearcacheonload' => false,
);

