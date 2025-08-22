'use strict';

/**
 * @param {Event} evt
 */
function changeServer(evt) {
    const urlParams = new URLSearchParams();
    urlParams.set('action', 'sql');
    urlParams.set('server', server.options[server.selectedIndex].value);
    urlParams.set('database', database.options[database.selectedIndex].value);
    urlParams.set('query', query.value);
    urlParams.set('search_path', search_path.value);
    if (paginate.checked) {
        urlParams.set('paginate', 'on');
    }

    location.href = `sqledit.php?${urlParams.toString()}`;
}