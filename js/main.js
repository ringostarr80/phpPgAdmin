'use strict';

let initialX = 0;
let initialBrowserWidth = 0;
const minBrowserWidth = 20;
const ownTitle = document.title;

document.addEventListener('DOMContentLoaded', function() {
    const separator = document.getElementById('separator');
    const browser = document.getElementById('browser');
    if (separator && browser) {
        separator.addEventListener('mousedown', mouseDownEventHandler);
    }
});

// Captured, as load events don't bubble. On the document, as the frames don't exist yet when the script runs.
document.addEventListener('load', frameLoadEventHandler, true);

function adjustDetailWidth() {
    let separatorWidth = 0;
    const separator = document.getElementById('separator');
    if (separator) {
        separatorWidth = separator.clientWidth;
    }

    const detailWidth = window.innerWidth - currentBrowserWidth() - separatorWidth;
    if (window.length > 0) {
        const windowCount = window.length;
        for (let i = 0; i < windowCount; i++) {
            if (window[i].name === 'detail') {
                window[i].frameElement.style.width = `${detailWidth}px`;
            }
        }
    }
}

function currentBrowserWidth() {
    if (window.length > 0) {
        const windowCount = window.length;
        for (let i = 0; i < windowCount; i++) {
            if (window[i].name === 'browser') {
                return window[i].innerWidth;
            }
        }
    }

    return 0;
}

/**
 * Gives the window the title of the page in the detail frame. Its own title, if the page has none or is one of another
 * site, whose title can't be read.
 *
 * @param {Event} evt
 */
function frameLoadEventHandler(evt) {
    const frame = evt.target;
    if (frame instanceof HTMLIFrameElement && frame.name === 'detail') {
        document.title = frame.contentDocument?.title || ownTitle;
    }
}

/**
 * @param {MouseEvent} evt 
 */
function mouseDownEventHandler(evt) {
    initialX = evt.screenX;

    window.addEventListener('mousemove', mouseMoveEventHandler);
    window.addEventListener('mouseup', mouseUpEventHandler);
    if (window.length > 0) {
        const windowCount = window.length;
        for (let i = 0; i < windowCount; i++) {
            if (window[i].name === 'browser') {
                initialBrowserWidth = window[i].innerWidth;
            }
            window[i].addEventListener('mousemove', mouseMoveEventHandler);
            window[i].addEventListener('mouseup', mouseUpEventHandler);
        }

        adjustDetailWidth();
    }
};

/**
 * @param {MouseEvent} evt 
 */
function mouseMoveEventHandler(evt) {
    const deltaX = evt.screenX - initialX;

    if (window.length > 0) {
        const windowCount = window.length;
        for (let i = 0; i < windowCount; i++) {
            if (window[i].name !== 'browser') {
                continue;
            }

            const browser = window[i].frameElement;
            if (browser instanceof HTMLIFrameElement) {
                const newBrowserWidth = Math.max(minBrowserWidth, initialBrowserWidth + deltaX);
                browser.style.width = `${newBrowserWidth}px`;
                break;
            }
        }
    }

    adjustDetailWidth();
};

function mouseUpEventHandler() {
    window.removeEventListener('mousemove', mouseMoveEventHandler);
    window.removeEventListener('mouseup', mouseUpEventHandler);
    if (window.length > 0) {
        const windowCount = window.length;
        for (let i = 0; i < windowCount; i++) {
            window[i].removeEventListener('mousemove', mouseMoveEventHandler);
            window[i].removeEventListener('mouseup', mouseUpEventHandler);
        }
    }
};
