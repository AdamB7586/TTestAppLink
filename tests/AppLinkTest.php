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
        if(!self::$db->isConnected()){
             $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        if(self::$db->count('users') < 1){
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/user/database/database_mysql.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/hcldc/database/mysql_database.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/hcldc/tests/sample_data/mysql_data.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/ttest/database/database_mysql.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/ttest/tests/sample_data/data.sql'));
        }
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
