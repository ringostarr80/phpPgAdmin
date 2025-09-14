'use strict';

/**
 * @param {Event} evt
 */
function changeServer(evt) {
    const urlParams = new URLSearchParams();
    urlParams.set('server', server.options[server.selectedIndex].value);
    urlParams.set('database', database.options[database.selectedIndex].value);

    location.href = `history.php?${urlParams.toString()}`;
}