import AjaxRequest from "@typo3/core/ajax/ajax-request.js";

class FilemanagerContextMenuActions {
    static editFolder(t, e, n)
    {
        let request = new AjaxRequest(TYPO3.settings.ajaxUrls['filemanager_folder_getid']);
        request.post({folderIdentifier: e}, {
            headers: {
                'Content-Type': 'application/json; charset=utf-8'
            }
        }).then(async function (response) {
            const resolved = await response.resolve();
            const id = resolved.result;
            top.TYPO3.Backend.ContentContainer.setUrl(
                top.TYPO3.settings.FormEngine.moduleUrl + '&edit[tx_ameosfilemanager_domain_model_folder][' + id + ']=edit&returnUrl=' + FilemanagerContextMenuActions.getReturnUrl()
            )
        });
    }

    static getReturnUrl()
    {
        return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search)
    }
}

export default FilemanagerContextMenuActions;
