<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, Language, RequestParameter, Themes, Website};

class Intro extends Website
{
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $divTopbar = $dom->createElement('div');
        $divTopbar->setAttribute('class', 'topbar');
        $tableTopbar = $dom->createElement('table');
        $tableTopbar->setAttribute('style', 'width: 100%');
        $trTopbar = $dom->createElement('tr');
        $tdTopbar = $dom->createElement('td');
        $spanAppname = $dom->createElement('span', self::APP_NAME);
        $spanAppname->setAttribute('class', 'appname');
        $spanVersion = $dom->createElement('span', self::APP_VERSION);
        $spanVersion->setAttribute('class', 'version');
        $tdTopbar->appendChild($spanAppname);
        $tdTopbar->appendChild($dom->createTextNode(' '));
        $tdTopbar->appendChild($spanVersion);
        $trTopbar->appendChild($tdTopbar);
        $tableTopbar->appendChild($trTopbar);
        $divTopbar->appendChild($tableTopbar);
        $body->appendChild($divTopbar);

        $divTrail = $dom->createElement('div');
        $divTrail->setAttribute('class', 'trail');
        $tableTrail = $dom->createElement('table');
        $trTrail = $dom->createElement('tr');
        $tdTrail = $dom->createElement('td');
        $tdTrail->setAttribute('class', 'crumb');
        $aTrail = $dom->createElement('a');
        $aTrail->setAttribute('href', 'redirect.php?subject=root');
        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Introduction'));
        $imgIcon->setAttribute('alt', 'Database Root');
        $spanIcon->appendChild($imgIcon);
        $aTrail->appendChild($spanIcon);
        $spanLabel = $dom->createElement('span', self::APP_NAME);
        $spanLabel->setAttribute('class', 'label');
        $aTrail->appendChild($spanLabel);
        $aTrail->appendChild($dom->createTextNode(':'));
        $tdTrail->appendChild($aTrail);
        $trTrail->appendChild($tdTrail);
        $tableTrail->appendChild($trTrail);
        $divTrail->appendChild($tableTrail);
        $body->appendChild($divTrail);

        $tableTabs = $dom->createElement('table');
        $tableTabs->setAttribute('class', 'tabs');
        $trTabs = $dom->createElement('tr');
        $tdTab = $dom->createElement('td');
        $tdTab->setAttribute('style', 'width: 50%');
        $tdTab->setAttribute('class', 'tab active');
        $aTab = $dom->createElement('a');
        $aTab->setAttribute('href', 'intro.php');
        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Introduction'));
        $imgIcon->setAttribute('alt', _('Introduction'));
        $spanIcon->appendChild($imgIcon);
        $aTab->appendChild($spanIcon);
        $spanLabel = $dom->createElement('span', _('Introduction'));
        $spanLabel->setAttribute('class', 'label');
        $aTab->appendChild($spanLabel);
        $tdTab->appendChild($aTab);
        $trTabs->appendChild($tdTab);
        $tdTab = $dom->createElement('td');
        $tdTab->setAttribute('style', 'width: 50%');
        $tdTab->setAttribute('class', 'tab');
        $aTab = $dom->createElement('a');
        $aTab->setAttribute('href', 'servers.php');
        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Servers'));
        $imgIcon->setAttribute('alt', _('Server'));
        $spanIcon->appendChild($imgIcon);
        $aTab->appendChild($spanIcon);
        $spanLabel = $dom->createElement('span', _('Server'));
        $spanLabel->setAttribute('class', 'label');
        $aTab->appendChild($spanLabel);
        $tdTab->appendChild($aTab);
        $trTabs->appendChild($tdTab);
        $tableTabs->appendChild($trTabs);
        $body->appendChild($tableTabs);

        $h1 = $dom->createElement('h1', self::APP_NAME . ' ' . self::APP_VERSION . ' (PHP ' . PHP_VERSION . ')');
        $body->appendChild($h1);

        $form = $dom->createElement('form');
        $form->setAttribute('method', 'get');
        $form->setAttribute('action', 'intro.php');
        $table = $dom->createElement('table');
        $tr = $dom->createElement('tr');
        $tr->setAttribute('class', 'data1');
        $th = $dom->createElement('th', _('Language'));
        $th->setAttribute('class', 'data');
        $tr->appendChild($th);
        $td = $dom->createElement('td');
        $select = $dom->createElement('select');
        $select->setAttribute('name', 'language');
        $select->setAttribute('onchange', 'this.form.submit()');

        $currentLocale = Config::locale();
        $languageIdsWithLocales = Language::getAvailableLanguageIdsWithLocales();
        foreach ($languageIdsWithLocales as $languageId => $locale) {
            putenv("LC_ALL={$locale}.UTF-8");
            setlocale(LC_ALL, ["{$locale}.UTF-8", $locale, substr($locale, 0, 2)]);
            textdomain('messages'); // clear textdomain cache
            $option = $dom->createElement('option', _('applang'));
            $option->setAttribute('value', $languageId);
            if ($locale === $currentLocale) {
                $option->setAttribute('selected', 'selected');
            }
            $select->appendChild($option);
        }
        // Reset locale
        putenv("LC_ALL={$currentLocale}.UTF-8");
        setlocale(LC_ALL, ["{$currentLocale}.UTF-8", $currentLocale, substr($currentLocale, 0, 2)]);
        textdomain('messages');

        $td->appendChild($select);
        $tr->appendChild($td);
        $table->appendChild($tr);
        $tr = $dom->createElement('tr');
        $tr->setAttribute('class', 'data2');
        $th = $dom->createElement('th', _('Theme'));
        $th->setAttribute('class', 'data');
        $tr->appendChild($th);
        $td = $dom->createElement('td');
        $select = $dom->createElement('select');
        $select->setAttribute('name', 'theme');
        $select->setAttribute('onchange', 'this.form.submit()');
        $themes = Themes::available();
        foreach ($themes as $theme => $label) {
            $option = $dom->createElement('option', $label);
            $option->setAttribute('value', $theme);
            if ($theme === Config::theme()) {
                $option->setAttribute('selected', 'selected');
            }
            $select->appendChild($option);
        }
        $td->appendChild($select);
        $tr->appendChild($td);
        $table->appendChild($tr);
        $form->appendChild($table);
        $body->appendChild($form);

        $p = $dom->createElement('p', _('Welcome to phpPgAdmin.'));
        $body->appendChild($p);

        $ul = $dom->createElement('ul');
        $ul->setAttribute('class', 'intro');
        $li = $dom->createElement('li');
        $a = $dom->createElement('a', _('phpPgAdmin Homepage'));
        $a->setAttribute('href', 'https://github.com/ringostarr80/phpPgAdmin');
        $li->appendChild($a);
        $ul->appendChild($li);
        $li = $dom->createElement('li');
        $a = $dom->createElement('a', _('PostgreSQL Homepage'));
        $a->setAttribute('href', 'https://www.postgresql.org/');
        $li->appendChild($a);
        $ul->appendChild($li);
        $li = $dom->createElement('li');
        $a = $dom->createElement('a', _('Report a Bug'));
        $a->setAttribute('href', 'https://github.com/ringostarr80/phpPgAdmin/issues');
        $li->appendChild($a);
        $ul->appendChild($li);
        $body->appendChild($ul);

        $a = $dom->createElement('a', _('back to top'));
        $a->setAttribute('href', '#');
        $a->setAttribute('class', 'bottom_link');
        $body->appendChild($a);

        $languageParam = RequestParameter::getString('language');
        if (!is_null($languageParam)) {
            $scriptElement = $dom->createElement('script');
            $scriptElement->setAttribute('type', 'text/javascript');
            $scriptElement->appendChild($dom->createCDATASection('parent.frames.browser.location.reload();'));
            $body->appendChild($scriptElement);
        }

        return $body;
    }
}
