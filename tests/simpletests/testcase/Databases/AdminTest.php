<?php

/**
 * Function area     : Database.
 * Sub Function area : Admin.
 *
 * @author     Augmentum SpikeSource Team
 * @copyright  Copyright (c) 2005 by Augmentum, Inc.
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once('../Public/SetPrecondition.php');
}

/**
 * This class is to test the Admin about PostgreSql implementation.
 */

class AdminTest extends PreconditionSet
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
     * TestCaseId: DAV001
     * This test is used to test the admin about Vacuum and full.
     */
    function testAdminVacuumAna()
    {
        global $webUrl, $lang, $SERVER, $DATABASE;

        // Locate the list page of admin.
        $this->assertTrue($this->get(
            "$webUrl/database.php",
            array('database' => $DATABASE,
                'subject' => 'database',
                'action' => 'admin',
                'server' => $SERVER)
        ));
        $this->assertTrue($this->setField('vacuum_analyze', true));
        $this->assertTrue($this->setField('vacuum_full', true));
        $this->assertTrue($this->clickSubmit($lang['strvacuum']));
        $this->assertText($lang['strvacuumgood']);

        return true;
    }


    /**
     * TestCaseId: DCS002
     * This test is used to test the admin about freeze.
     */
    function testAdminFreeze()
    {
        global $webUrl, $lang, $SERVER, $DATABASE;

        // Locate the list page of admin.
        $this->assertTrue($this->get(
            "$webUrl/database.php",
            array('database' => $DATABASE,
                'subject' => 'database',
                'action' => 'admin',
                'server' => $SERVER)
        ));
        $this->assertTrue($this->setField('vacuum_freeze', true));
        $this->assertTrue($this->clickSubmit($lang['strvacuum']));
        $this->assertText($lang['strvacuumgood']);

        return true;
    }


    /**
     * TestCaseId: DCS003
     * This test is used to test the admin about Analyze.
     */
    function testAdminAnalyze()
    {
        global $webUrl, $lang, $SERVER, $DATABASE;

        // Locate the list page of admin.
        $this->assertTrue($this->get(
            "$webUrl/database.php",
            array('database' => $DATABASE,
                'subject' => 'database',
                'action' => 'admin',
                'server' => $SERVER)
        ));
        $this->assertTrue($this->clickSubmit($lang['stranalyze']));
        $this->assertText($lang['stranalyzegood']);

        return true;
    }


    /**
     * TestCaseId: DCS004
     * This test is used to test the admin about Cluster.
     */
    function testAdminCluster()
    {
        global $webUrl, $lang, $SERVER, $DATABASE;

        // Locate the list page of admin.
        $this->assertTrue($this->get("$webUrl/database.php", array(
                'server' => $SERVER,
                'database' => $DATABASE,
                'subject' => 'database',
                'action' => 'admin')));
        $this->assertTrue($this->clickSubmit($lang['strcluster']));
        $this->assertText($lang['strclusteredgood']);

        return true;
    }


    /**
     * TestCaseId: DCS005
     * This test is used to test the admin about Reindex.
     */
    function testAdminReindex()
    {
        global $webUrl, $lang, $SERVER, $DATABASE;

        // Locate the list page of admin.
        $this->assertTrue($this->get("$webUrl/database.php", array(
                'database' => $DATABASE,
                'subject' => 'database',
                'action' => 'admin',
                'server' => $SERVER)));
        $this->assertTrue($this->setField('reindex_force', true));
        $this->assertTrue($this->clickSubmit($lang['strreindex']));
        $this->assertText($lang['strreindexgood']);

        return true;
    }
}
