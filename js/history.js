'use strict';

// Like the legacy page, the window takes the name of its server on every load, under which js/topbar.js opens the
// History link (see WebsiteComponents::buildTopBar()). So the links of a server open in its window, even after another
// server was chosen in it. Unlike the legacy page, it leaves the name of the detail frame alone: the History link has
// no target, so it opens in that frame when the popup is blocked, and the other pages open their links in it by name.
if (window === window.top) {
    window.name = `history:${new URLSearchParams(location.search).get('server') ?? ''}`;
}

/**
 * @param {Event} evt
 */
function changeServer(evt) {
    const urlParams = new URLSearchParams();
    urlParams.set('server', server.options[server.selectedIndex].value);
    urlParams.set('database', database.options[database.selectedIndex].value);

    location.href = `history.php?${urlParams.toString()}`;
}