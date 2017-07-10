(function ($) {

    $.fn.fileManagerTree = function () {
        var tree = $(this);

        function loadEventListener() {
            tree.find("a[data-action=expand]").unbind("click");
            tree.find("a[data-action=expand]").click(function(event) {
                $(this).parent().children("ul").toggleClass("active");
                if ($(this).children("i.fa")) {
                    if ($(this).parent().children("ul").hasClass("active")) {
                        $(this).children("i.fa").removeClass("fa-folder");
                        $(this).children("i.fa").addClass("fa-folder-open");
                    } else {
                        $(this).children("i.fa").removeClass("fa-folder-open");
                        $(this).children("i.fa").addClass("fa-folder");
                    }
                }                
                event.preventDefault();
            });
        }

        if (tree.find(".current")) {
            tree.find(".current").parents("ul").addClass("active");
            if (tree.find(".current").find("i.fa")) {
                tree.find(".current").parents("li").each(function(key, element) {
                    $(element).children("a").children("i.fa").addClass("fa-folder-open");
                });
            }
        }

        loadEventListener();        
    }

    $.fn.fileManagerToolbar = function () {
        var toolbar = $(this);
        toolbar.find("*[data-update-display=1]").change(function(event) {
            $(this).parent('form').submit();
        });
    }

    $(".tree").fileManagerTree();
    $(".toolbar").fileManagerToolbar();

}(jQuery));
