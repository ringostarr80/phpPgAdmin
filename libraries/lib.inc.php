<?php

/**
 * Function library read in upon startup
 *
 * $Id: lib.inc.php,v 1.123 2008/04/06 01:10:35 xzilla Exp $
 */

include_once './libraries/decorator.inc.php';
include_once './lang/translations.php';

// Do not show depreciation warnings.
//error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
error_reporting(E_ALL);

// Application name
$appName = 'phpPgAdmin';

// Application version
$appVersion = '7.14.7-mod';

// PostgreSQL and PHP minimum version
global $postgresqlMinVer;
$postgresqlMinVer = '7.4';
$phpMinVer = '7.2';

// Check the version of PHP
if (version_compare(phpversion(), $phpMinVer, '<')) {
    exit(sprintf('Version of PHP not supported. Please upgrade to version %s or later.', $phpMinVer));
}

// Check to see if the configuration file exists, if not, explain
if (file_exists('conf/config.inc.php')) {
    $conf = array();
    include './conf/config.inc.php';
} else {
    echo 'Configuration error: Copy conf/config.inc.php-dist to conf/config.inc.php and edit appropriately.';
    exit;
}

// Configuration file version.  If this is greater than that in config.inc.php, then
// the app will refuse to run.  This and $conf['version'] should be incremented whenever
// backwards incompatible changes are made to config.inc.php-dist.
$conf['base_version'] = 16;

// Always include english.php, since it's the master language file
if (!isset($conf['default_lang'])) {
    $conf['default_lang'] = 'english';
}
$lang = array();
require_once './lang/english.php';

// Create Misc class references
require_once './classes/Misc.php';
$misc = new Misc();

// Session start: if extra_session_security is on, make sure cookie_samesite
// is on (exit if we fail); otherwise, just start the session
$our_session_name = 'PPA_ID';
if (($conf['extra_session_security'] ?? true) === true) {
    if (version_compare(phpversion(), '7.3', '<')) {
        exit('phpPgAdmin cannot be fully secured while running under PHP versions before 7.3. Please upgrade PHP if possible. If you cannot upgrade, and you\'re willing to assume the risk of CSRF attacks, you can change the value of "extra_session_security" to false in your config.inc.php file.');
    }

    if (ini_get('session.auto_start')) {
        // If session.auto_start is on, and the session doesn't have
        // session.cookie_samesite set, destroy and re-create the session
        if (session_name() !== $our_session_name) {
            $setting = strtolower(ini_get('session.cookie_samesite'));

            if ($setting !== 'lax' && $setting !== 'strict') {
                session_destroy();
                session_name($our_session_name);
                ini_set('session.cookie_samesite', 'Strict');
                session_start();
            }
        }
    } else {
        session_name($our_session_name);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
} else {
    if (!ini_get('session.auto_start')) {
        session_name($our_session_name);
        session_start();
    }
}

$misc->setHREF();
$misc->setForm();

// Enforce PHP environment
ini_set('arg_separator.output', '&amp;');

// If login action is set, then set session variables
if (
    isset($_POST['loginServer']) && isset($_POST['loginUsername']) &&
    isset($_POST['loginPassword_' . md5($_POST['loginServer'])])
) {
    $_server_info = $misc->getServerInfo($_POST['loginServer']);

    $_server_info['username'] = $_POST['loginUsername'];
    $_server_info['password'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];

    $misc->setServerInfo(null, $_server_info, $_POST['loginServer']);

    // Check for shared credentials
    if (isset($_POST['loginShared'])) {
        $_SESSION['sharedUsername'] = $_POST['loginUsername'];
        $_SESSION['sharedPassword'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];
    }

    $_reload_browser = true;
}

/* select the theme */
unset($_theme);
if (!isset($conf['theme'])) {
    $conf['theme'] = 'default';
}

// 1. Check for the theme from a request var
if (isset($_REQUEST['theme']) && is_file("./themes/{$_REQUEST['theme']}/global.css")) {
    /* save the selected theme in cookie for a year */
    setcookie('ppaTheme', $_REQUEST['theme'], time() + 31536000);
    $_theme = $_SESSION['ppaTheme'] = $conf['theme'] = $_REQUEST['theme'];
}

// 2. Check for theme session var
if (!isset($_theme) && isset($_SESSION['ppaTheme']) && is_file("./themes/{$_SESSION['ppaTheme']}/global.css")) {
    $conf['theme']  = $_SESSION['ppaTheme'];
}

// 3. Check for theme in cookie var
if (!isset($_theme) && isset($_COOKIE['ppaTheme']) && is_file("./themes/{$_COOKIE['ppaTheme']}/global.css")) {
    $conf['theme']  = $_COOKIE['ppaTheme'];
}

// 4. Check for theme by server/db/user
$info = $misc->getServerInfo();

if (!is_null($info)) {
    $_theme = '';

    if (
        (isset($info['theme']['default']))
        and is_file("./themes/{$info['theme']['default']}/global.css")
    ) {
        $_theme = $info['theme']['default'];
    }

    if (
        isset($_REQUEST['database'])
        and isset($info['theme']['db'][$_REQUEST['database']])
        and is_file("./themes/{$info['theme']['db'][$_REQUEST['database']]}/global.css")
    ) {
        $_theme = $info['theme']['db'][$_REQUEST['database']];
    }

    if (
        isset($info['username'])
        and isset($info['theme']['user'][$info['username']])
        and is_file("./themes/{$info['theme']['user'][$info['username']]}/global.css")
    ) {
        $_theme = $info['theme']['user'][$info['username']];
    }

    if ($_theme !== '') {
        setcookie('ppaTheme', $_theme, time() + 31536000);
        $conf['theme'] = $_theme;
    }
}

// Determine language file to import:
unset($_language);

// 1. Check for the language from a request var
if (isset($_REQUEST['language']) && isset($appLangFiles[$_REQUEST['language']])) {
    /* save the selected language in cookie for a year */
    setcookie('webdbLanguage', $_REQUEST['language'], time() + 31536000);
    $_language = $_REQUEST['language'];
}

// 2. Check for language session var
if (!isset($_language) && isset($_SESSION['webdbLanguage']) && isset($appLangFiles[$_SESSION['webdbLanguage']])) {
    $_language = $_SESSION['webdbLanguage'];
}

// 3. Check for language in cookie var
if (!isset($_language) && isset($_COOKIE['webdbLanguage']) && isset($appLangFiles[$_COOKIE['webdbLanguage']])) {
    $_language  = $_COOKIE['webdbLanguage'];
}

// 4. Check for acceptable languages in HTTP_ACCEPT_LANGUAGE var
if (!isset($_language) && $conf['default_lang'] == 'auto' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    // extract acceptable language tags
    // (http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4)
    preg_match_all('/\s*([a-z]{1,8}(?:-[a-z]{1,8})*)(?:;q=([01](?:.[0-9]{0,3})?))?\s*(?:,|$)/', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), $_m, PREG_SET_ORDER);
    foreach ($_m as $_l) {  // $_l[1] = language tag, [2] = quality
        if (!isset($_l[2])) {
            $_l[2] = 1;  // Default quality to 1
        }
        if ($_l[2] > 0 && $_l[2] <= 1 && isset($availableLanguages[$_l[1]])) {
            // Build up array of (quality => language_file)
            $_acceptLang[$_l[2]] = $availableLanguages[$_l[1]];
        }
    }
    unset($_m);
    unset($_l);
    if (isset($_acceptLang)) {
        // Sort acceptable languages by quality
        krsort($_acceptLang, SORT_NUMERIC);
        $_language = reset($_acceptLang);
        unset($_acceptLang);
    }
}

// 5. Otherwise resort to the default set in the config file
if (!isset($_language) && $conf['default_lang'] != 'auto' && isset($appLangFiles[$conf['default_lang']])) {
    $_language = $conf['default_lang'];
}

// 6. Otherwise, default to english.
if (!isset($_language)) {
    $_language = 'english';
}

// Import the language file
if (isset($_language)) {
    include("./lang/{$_language}.php");
    $_SESSION['webdbLanguage'] = $_language;
}

// Check for config file version mismatch
if (!isset($conf['version']) || $conf['base_version'] > $conf['version']) {
    echo $lang['strbadconfig'];
    exit;
}

// Check php libraries
$php_libraries_requirements = [
    // required_function => name_of_the_php_library
    'pg_connect' => 'pgsql',
    'mb_strlen' => 'mbstring'];
$missing_libraries = [];
foreach ($php_libraries_requirements as $funcname => $lib) {
    if (!function_exists($funcname)) {
        $missing_libraries[] = $lib;
    }
}
if ($missing_libraries) {
    $missing_list = implode(', ', $missing_libraries);
    $error_missing_template_string = count($missing_libraries) <= 1 ? $lang['strlibnotfound'] : $lang['strlibnotfound_plural'];
    printf($error_missing_template_string, $missing_list);
    exit;
}

// Manage the plugins
require_once './classes/PluginManager.php';

// Create data accessor object, if necessary
if (!isset($_no_db_connection)) {
    if (!isset($_REQUEST['server'])) {
        echo $lang['strnoserversupplied'];
        exit;
    }
    $_server_info = $misc->getServerInfo();

    /* starting with PostgreSQL 9.0, we can set the application name */
    if (isset($_server_info['pgVersion']) && version_compare($_server_info['pgVersion'], '9', '>=')) {
        putenv("PGAPPNAME={$appName}_{$appVersion}");
    }

    // Redirect to the login form if not logged in
    if (!isset($_server_info['username'])) {
        include './login.php';
        exit;
    }

    // Connect to the current database, or if one is not specified
    // then connect to the default database.
    if (isset($_REQUEST['database'])) {
        $_curr_db = $_REQUEST['database'];
    } else {
        $_curr_db = $_server_info['defaultdb'];
    }

    include_once './classes/database/Connection.php';
    
    // Connect to database and set the global $data variable
    $data = $misc->getDatabaseAccessor($_curr_db);

    // If schema is defined and database supports schemas, then set the
    // schema explicitly.
    if (isset($_REQUEST['database']) && isset($_REQUEST['schema'])) {
        $status = $data->setSchema($_REQUEST['schema']);
        if ($status != 0) {
            echo $lang['strbadschema'];
            exit;
        }
    }
}

$plugin_manager = new PluginManager($_language);

/**
 * Safe unserializer wrapper
 *
 * It does not unserialize data containing objects
 *
 * Function from phpMyAdmin version 5.2.1
 *
 * @param string $data Data to unserialize
 *
 * @return mixed|null
 */
function safeUnserialize(string $data)
{
    /* validate serialized data */
    $length = strlen($data);
    $depth = 0;
    for ($i = 0; $i < $length; $i++) {
        $value = $data[$i];

        switch ($value) {
            case '}':
                /* end of array */
                if ($depth <= 0) {
                    return null;
                }

                $depth--;
                break;
            case 's':
                /* string */
                // parse sting length
                $strlen = intval(substr($data, $i + 2));
                // string start
                $i = strpos($data, ':', $i + 2);
                if ($i === false) {
                    return null;
                }

                // skip string, quotes and ;
                $i += 2 + $strlen + 1;
                if ($data[$i] !== ';') {
                    return null;
                }

                break;

            case 'b':
            case 'i':
            case 'd':
                /* bool, integer or double */
                // skip value to separator
                $i = strpos($data, ';', $i);
                if ($i === false) {
                    return null;
                }

                break;
            case 'a':
                /* array */
                // find array start
                $i = strpos($data, '{', $i);
                if ($i === false) {
                    return null;
                }

                // remember nesting
                $depth++;
                break;
            case 'N':
                /* null */
                // skip to end
                $i = strpos($data, ';', $i);
                if ($i === false) {
                    return null;
                }

                break;
            default:
                /* any other elements are not wanted */
                return null;
        }
    }

    // check unterminated arrays
    if ($depth > 0) {
        return null;
    }

    return unserialize($data);
}
