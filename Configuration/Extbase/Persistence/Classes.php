<?php

declare(strict_types=1);

return [
    \Ameos\AmeosFilemanager\Domain\Model\Folder::class => [
        'properties' => [
            'crdate' => [
                'fieldName' => 'crdate',
            ],
        ],
    ],
    \Ameos\AmeosFilemanager\Domain\Model\File::class => [
        'tableName' => 'sys_file',
    ],
    \Ameos\AmeosFilemanager\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
    \TYPO3\CMS\Core\Resource\ResourceStorage::class => [
        'tableName' => 'sys_file_storage',
    ],
];
