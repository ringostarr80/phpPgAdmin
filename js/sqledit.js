'use strict';

// Like the legacy page, the window takes the name of its server on every load, under which js/topbar.js opens the SQL
// and the Find link (see WebsiteComponents::buildTopBar()). So the links of a server open in its window, even after
// another server was chosen in it.
window.name = `sqledit:${new URLSearchParams(location.search).get('server') ?? ''}`;

/**
 * Opens the window again for the chosen server and database, with what was entered in its form.
 *
 * @param {Event} evt
 */
function changeServer(evt) {
    const find = new URLSearchParams(location.search).get('action') === 'find';
    const urlParams = new URLSearchParams();
    urlParams.set('action', find ? 'find' : 'sql');
    urlParams.set('server', server.options[server.selectedIndex].value);
    urlParams.set('database', database.options[database.selectedIndex].value);

    // The forms of both actions have fields of their own, as in the legacy page.
    if (find) {
        urlParams.set('term', term.value);
        urlParams.set('filter', filter.value);
    } else {
        urlParams.set('query', query.value);
        urlParams.set('search_path', search_path.value);
        if (paginate.checked) {
            urlParams.set('paginate', 'on');
        }
    }

    location.href = `sqledit.php?${urlParams.toString()}`;
}