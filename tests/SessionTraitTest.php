<?php
/* Sesh | https://gitlab.com/byjoby/sesh | MIT License */
declare(strict_types=1);
namespace Sesh;

use PHPUnit\Framework\TestCase;

include_once 'fake-session.php';

class SessionTraitTest extends TestCase
{
    public function setUp()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
    }

    public function testNameSpacing()
    {
        //namespacing without unique ids
        $h1 = new SessionTraitHarness1();
        $h2 = new SessionTraitHarness2();
        $this->assertNotEquals($h1->getToken('test'), $h2->getToken('test'));
        //namespacing with unique ids
        $h1a = new SessionTraitHarness1('a');
        $h1b = new SessionTraitHarness1('b');
        $h2a = new SessionTraitHarness2('a');
        $h2b = new SessionTraitHarness2('b');
        //check that h1a isn't the same as h1b or h2a
        $this->assertNotEquals($h1a->getToken('test'), $h1b->getToken('test'));
        $this->assertNotEquals($h1a->getToken('test'), $h2a->getToken('test'));
        //check that h1b isn't the same as h2a or h2b
        $this->assertNotEquals($h1b->getToken('test'), $h2a->getToken('test'));
        $this->assertNotEquals($h1b->getToken('test'), $h2b->getToken('test'));
        //second copies with the same parameters, should have same storage as above
        $h1a2 = new SessionTraitHarness1('a');
        $h1b2 = new SessionTraitHarness1('b');
        $h2a2 = new SessionTraitHarness2('a');
        $h2b2 = new SessionTraitHarness2('b');
        //check that the second set are the same
        $this->assertEquals($h1a->getToken('test'), $h1a2->getToken('test'));
        $this->assertEquals($h1b->getToken('test'), $h1b2->getToken('test'));
        $this->assertEquals($h2a->getToken('test'), $h2a2->getToken('test'));
        $this->assertEquals($h2b->getToken('test'), $h2b2->getToken('test'));
    }
}

class SessionTraitHarness1
{
    use SessionTrait,SessionPublicTrait;
    public function __construct($uid=null)
    {
        $this->sessionTraitInit($uid);
    }
}

class SessionTraitHarness2 extends SessionTraitHarness1
{
}
