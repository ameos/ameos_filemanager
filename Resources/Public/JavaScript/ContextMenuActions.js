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

/**
 * Module: TYPO3/CMS/AmeosFilemanager/ContextMenuActions
 * Click menu actions for db records including tt_content and pages
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity', ], function ($, Modal, Severity) {
    /**
     *
     * @exports TYPO3/CMS/AmeosFilemanager/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.getReturnUrl = function () {
       return top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
    };

    ContextMenuActions.editFolder = function (table, uid) {
        $.ajax({
            method: "POST",
            url: TYPO3.settings.ajaxUrls['Filemanager::getFolderId'],
            data: { folderIdentifier: uid }
        }).done(function(response) {
            top.TYPO3.Backend.ContentContainer.setUrl(
                top.TYPO3.settings.FormEngine.moduleUrl + '&edit[tx_ameosfilemanager_domain_model_folder][' + response.uid + ']=edit&returnUrl=' + ContextMenuActions.getReturnUrl()
            );
        });
    };

    return ContextMenuActions;
});
