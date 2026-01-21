$(document).ready(function () {
    if ($('form > div-table-responsive thead').length <= 0) {
        $('div.div-table-responsive > table').prepend('<thead class="sticky"></thead>');
        var thead = $('div.div-table-responsive > table > thead');

        $('tbody > tr.liste_titre_filter, tbody > tr.liste_titre').appendTo(thead);
    }
});
