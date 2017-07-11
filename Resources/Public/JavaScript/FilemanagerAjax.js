function loadGoToFolderListener() {
    jQuery("*[data-ged-reload=1]").unbind("click");
    jQuery("*[data-ged-reload=1]").bind("click", function(event) {        
        var currentLink = jQuery(this);
        jQuery.ajax(currentLink.attr("href"), {
            method: 'POST',
            data: {ameos_filemanager_content: currentLink.attr("data-ged-uid")}
        }).done(function(data) {
            currentLink.parents("#ameos_file_manager_" + currentLink.attr("data-ged-uid")).replaceWith(data.html);
            loadGoToFolderListener();
        });
        event.preventDefault;
        return false;
    });
}
    
jQuery(function() {
    loadGoToFolderListener();
});

