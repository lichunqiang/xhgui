window.Xhgui = window.Xhgui || {};

Xhgui.tableSort = function(tables) {
    tables.stickyTableHeaders();
    tables.tablesorter({
        textExtraction: function(node) {
            if (node.className.match(/text/)) {
                return node.innerText;
            }
            var text = node.innerText || node.textContent;
            return '' + parseInt(text.replace(/,/g, ''), 10);
        }
    });
};

// Utilitarian DOM behavior.
$(document).ready(function () {
    $('.tip').tooltip();

    var tables = $('.table-sort');
    Xhgui.tableSort(tables);


    // Bind events for expandable search forms.
    var searchForm = $('.search-form'),
        searchExpand = $('.search-expand'),
        deleteButton = $('.delete-all');

    searchExpand.on('click', function () {
        searchExpand.fadeOut('fast', function () {
            searchForm.slideDown('fast');
        });
        deleteButton.hide();
        return false;
    });

    $('.search-collapse').on('click', function () {
        searchForm.slideUp('fast', function () {
            searchExpand.show();
        });
        deleteButton.show();
        return false;
    });
    deleteButton.on('click', function() {
        if (!confirm('确定删除所有数据？')) {
            return false;
        }
    });
    $('body').on('click', '.delete-item', function () {
        if (!confirm('确定删除此条记录？')) {
            return false;
        }
    });
});
