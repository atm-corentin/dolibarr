// #344
$(document).ready(function () {
    var quickListContainer = $('div.quicklist-filter-list.centpercent');
    var quicklistFilters = quickListContainer.children('.quicklist-button');
    quicklistManagement(quickListContainer, quicklistFilters);
    $(window).resize(function () {
        quicklistManagement(quickListContainer, quicklistFilters);
    });
});

function quicklistManagement(quickListContainer, quicklistFilters) {
    var textContent = $(quickListContainer).contents().filter(function () {
        return this.nodeType == 3;
    }).get(0); // get first text element -> 'Filters :'

    if (window.matchMedia("(orientation: portrait)").matches) {
        if (!quickListContainer.children('div.quicklist-filter-item').length) {
            $(textContent).remove();
            quicklistFilters = $('<div class="quicklist-filter-item hidden"></div>').append(quicklistFilters);

            quickListContainer.prepend($('<span id="toggle-collapse"><span class="fas fa-chevron-down"></span>' + $(textContent).text() +'</span>')).append(quicklistFilters);

            $(quickListContainer).children('span#toggle-collapse').on( 'click', function() {
                $(this).children('span.fas').toggleClass('fa-chevron-down fa-chevron-up');
                quicklistFilters.toggleClass('hidden');
            } );
        }
    } else {
        if (quickListContainer.children('span#toggle-collapse').length) {
            textContent = $('span#toggle-collapse').text();
            $('span#toggle-collapse, div.quicklist-filter-item').remove();
        }
        quickListContainer.append(textContent);
        quickListContainer.append(quicklistFilters);
    }
}