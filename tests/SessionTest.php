<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
declare(strict_types=1);
namespace Sesh;

use PHPUnit\Framework\TestCase;

include_once 'fake-session.php';

class SessionTest extends TestCase
{
    public function setUp()
    {
        //fake user-agent and IP
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
        //fake session storage
        $GLOBALS['FAKESESSID'] = null;
        $GLOBALS['FAKESESSIONS'] = array();
    }

    /**
     * This also implicitly tests that persistent refs to $_SESSION are actually
     * being used.
     */
    public function testUserID()
    {
        $s1 = new SessionHarness();
        $s2 = new SessionHarness();
        $s1->userID('foo');
        $this->assertEquals('foo', $s2->userID());
        //deauthorize
        $s1->deauthorize();
        $this->assertNull($s1->userID());
        $this->assertNull($s2->userID());
    }

    public function testRegeneration()
    {
        $s1 = new SessionHarness();
        $s1->userID('foo');
        $oldSessid = session_id();
        //call to regenerate session id
        $s1->regenerateID();
        $newSessid = session_id();
        $GLOBALS['FAKESESSIONS'][$oldSessid]['Sesh\\SessionHarness']['storage']['foo'] = 'bar';
        $this->assertNotEquals($oldSessid, session_id());
        //setting back to the old session id and adding a new session should throw an exception
        $this->expectException(DestroyedSessionAccessException::class);
        set_session_id($oldSessid);
        $s2 = new SessionHarness();
        //should also be deauthorized
        $this->assertNull($s2->userID());
        //set back to new session id, should still be authorized
        set_session_id($newSessid);
        $s3 = new SessionHarness();
        $this->assertEquals('foo', $s3->userID());
    }
}

class SessionHarness extends Session
{
    protected $destroyedSessionGracePeriod = -1;//make destroyed sessions explode immediately for testing
    /**
     * break singleton pattern so we can test multiple copies
     */
    public function __construct()
    {
        parent::__construct();
    }
}
