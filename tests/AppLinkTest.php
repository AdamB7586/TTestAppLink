<?php
namespace AppLink\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Configuration\Config;
use TheoryTest\Car\User;
use Smarty;
use AppLink\AppLink;

class AppLinkTest extends TestCase{
    
    protected static $db;
    protected static $user;
    protected static $appLink;

    public static function setUpBeforeClass() {
        self::$db = new Database($GLOBALS['HOSTNAME'], $GLOBALS['USERNAME'], $GLOBALS['PASSWORD'], $GLOBALS['DATABASE']);
        self::$user = new User(self::$db);
        self::$appLink = new AppLink(self::$db, new Config(self::$db), new Smarty(), self::$user);
    }
    
    public static function tearDownAfterClass() {
        self::$appLink = null;
    }
    
    /**
     * @covers AppLink\AppLink::setDataUrl
     */
    public function testSetDataUrl(){
        $this->markTestIncomplete();
    }
}
