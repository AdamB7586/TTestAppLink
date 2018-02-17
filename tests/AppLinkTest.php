<?php
namespace AppLink\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use UserAuth\User;
use Smarty;
use AppLink\AppLink;

class AppLinkTest extends TestCase{
    
    protected $db;
    protected $template;
    protected $user;
    protected $appLink;

    protected function setUp() {
        $this->db = new Database($GLOBALS['HOSTNAME'], $GLOBALS['USERNAME'], $GLOBALS['PASSWORD'], $GLOBALS['DATABASE']);
        $this->template = New Smarty();
        $this->user = new User($this->db);
        $this->appLink = new AppLink($this->db, $this->template, $this->user);
    }
    
    protected function tearDown() {
        $this->appLink = null;
    }
    
    /**
     * @covers AppLink\AppLink::setDataUrl
     */
    public function testSetDataUrl(){
        $this->markTestIncomplete();
    }
}
