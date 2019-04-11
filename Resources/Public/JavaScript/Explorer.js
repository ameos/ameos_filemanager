(function() {

    FileManagerTree = (function(tree) {
        if (tree) {
            var parents = function(element, parentSelector) {
                if (parentSelector === undefined) {
                    parentSelector = document;
                }

                var parents = [];
                var p = element.parentNode;
                if (p) {
                    while (p !== parentSelector) {
                        var o = p;
                        parents.push(o);
                        p = o.parentNode;
                    }
                    parents.push(parentSelector);
                }

                return parents;
            };

            var loadEventListener = function() {
                var items = tree.querySelectorAll("a[data-action=expand]");
                items.forEach(function(item, i) {
                    item.addEventListener("click", function(event) {
                        var ulparent = event.target.parentNode.parentNode.querySelector("ul");
                        ulparent.classList.toggle("active");
                        if (ulparent.classList.contains("active")) {
                            event.target.classList.remove("fa-folder");
                            event.target.classList.add("fa-folder-open");
                        } else {
                            event.target.classList.add("fa-folder");
                            event.target.classList.remove("fa-folder-open");
                        }                        
                        event.preventDefault();
                    });
                });
            };

            if (tree.querySelector(".current")) {
                parents(tree.querySelector(".current"), tree).forEach(function(item, i) {
                    item.classList.add("active");
                    item.parentElement.querySelector("a[data-action=expand] i").classList.remove("fa-folder");
                    item.parentElement.querySelector("a[data-action=expand] i").classList.add("fa-folder-open");
                });
            }
            loadEventListener();
        }
    });
    var tree = new FileManagerTree(document.querySelector('.tree'));


    FileManagerToolbar = (function(toolbar) {
        if (toolbar) {
            var item = toolbar.querySelector("select[data-update-display]");
            if (item) {
                item.addEventListener("change", function(event) {
                    event.target.parentNode.submit();
                });
            }
        }
    });
    var toolbar = new FileManagerToolbar(document.querySelector('.toolbar'));

    FileManagerMassaction = (function(massaction) {
        if (massaction) {
            massaction.querySelector("#targetfolder").style.display = "none";
            massaction.querySelector("#massaction").addEventListener("change", function(event) {
                if (event.target.value == "copy" || event.target.value == "move") {
                    massaction.querySelector("#targetfolder").style.display = "block";
                } else {
                    massaction.querySelector("#targetfolder").style.display = "none";
                }
            });
        }
    });
    var massaction = new FileManagerMassaction(document.querySelector('.massaction-toolbar'));

}).call(this);