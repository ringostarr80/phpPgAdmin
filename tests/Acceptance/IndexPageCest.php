<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\{AcceptanceTester, MyConfigExtension};

final class IndexPageCest
{
    //*
    public function tryToTestIndexPage(AcceptanceTester $i): void
    {
        $i->amOnPage('/');
        $i->seeElement('html', ['lang' => 'en-US']);

        $i->seeElement('iframe', ['src' => 'browser.php', 'name' => 'browser']);
        $i->seeElement('iframe', ['src' => 'intro.php', 'name' => 'detail']);

        $i->switchToIframe('browser');

        $i->waitForElement('div.webfx-tree-children');
        $i->waitForText(MyConfigExtension::NOT_RUNNING_SERVER_DESC);
        $i->see(
            MyConfigExtension::NOT_RUNNING_SERVER_DESC,
            [
                'css' => 'div[class="webfx-tree-item"] div[class="webfx-tree-row"] a[class="webfx-tree-item-label"]'
            ]
        );

        $i->switchToIframe();
        $i->switchToIframe('detail');

        $i->see('Introduction', 'table.tabs tr:first-child td:first-child.active span.label');
        $i->see('Server', 'table.tabs tr:first-child td:nth-child(2) span.label');

        $i->seeNumberOfElements('select[name="language"] option', 29);
        $i->seeNumberOfElements('select[name="theme"] option', 5);

        $i->see('English', 'select[name="language"] option[value="english"]');
        $i->see('Deutsch', 'select[name="language"] option[value="german"]');
    }
    //*/

    //*
    public function tryToTestServerTab(AcceptanceTester $i): void
    {
        $i->amOnPage('/');
        $i->switchToIframe('detail');
        //$i->click('a[href="servers.php"]');
        $i->click('Server');

        $i->waitForElement('table.tabs tr:first-child td:nth-child(2).active span.label');

        $i->see('Introduction', 'table.tabs tr:first-child td:first-child span.label');
        $i->see('Server', 'table.tabs tr:first-child td:nth-child(2).active span.label');

        $i->seeElement('table#server-list');
        $i->seeElement('table#server-list thead');
        $i->seeElement('table#server-list tbody');
        $i->seeNumberOfElements('table#server-list tbody tr', [1, 5]);
        $i->see(MyConfigExtension::NOT_RUNNING_SERVER_DESC, 'table#server-list tbody tr:first-child td:nth-child(1)');
        $i->see('192.168.0.10', 'table#server-list tbody tr:first-child td:nth-child(2)');
        $i->see('5432', 'table#server-list tbody tr:first-child td:nth-child(3)');
        $i->see('', 'table#server-list tbody tr:first-child td:nth-child(4)');
        $i->see('', 'table#server-list tbody tr:first-child td:nth-child(5)');
    }
    //*/
}
