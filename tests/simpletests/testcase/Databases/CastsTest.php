<?php

/**
 * Function area     : Database.
 * Sub Function area : Casts.
 *
 * @author     Augmentum SpikeSource Team
 * @copyright  Copyright (c) 2005 by Augmentum, Inc.
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once('../Public/SetPrecondition.php');
}

/**
 * This class is to test the Casts displayed list.
 */
class CastsTest extends PreconditionSet
{
    /**
     * Set up the preconditon.
     */
    function setUp()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;
        $this->login(
            $SUPER_USER_NAME,
            $SUPER_USER_PASSWORD,
            "$webUrl/login.php"
        );

        return true;
    }


    /**
     * Release the relational resource.
     */
    function tearDown()
    {
        // Logout this system.
        $this->logout();

        return true;
    }


    /**
     * TestCaseId: DLU001
     * This test is used to test Casts Displayed page.
     *
     * Note: It's strange here, because it only display one sentecse.
     */
    function testLanguage()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Locate the list page of language.
        $this->assertTrue($this->get("$webUrl/casts.php", array(
                        'server' => $SERVER,
                        'database' => $DATABASE,
                        'subject' => 'database')));

        $this->assertText($lang['strsourcetype']);
        $this->assertText($lang['strtargettype']);
        $this->assertText($lang['strimplicit']);


        return true;
    }
}
