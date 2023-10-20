

/**
 * Module: TYPO3/CMS/AmeosFilemanager/ContextMenuActions
 * Click menu actions for db records including tt_content and pages
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function($, Modal, Severity) {
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
            url: TYPO3.settings.ajaxUrls['filemanager_folder_getid'],
            data: { folderIdentifier: uid }
        }).done(function(response) {
            top.TYPO3.Backend.ContentContainer.setUrl(
                top.TYPO3.settings.FormEngine.moduleUrl + '&edit[tx_ameosfilemanager_domain_model_folder][' + response.uid + ']=edit&returnUrl=' + ContextMenuActions.getReturnUrl()
            );
        });
    };

    return ContextMenuActions;
});
