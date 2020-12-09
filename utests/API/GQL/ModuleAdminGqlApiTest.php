<?php 

namespace FreepPBX\framework\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\framework;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class ModuleAdminGqlApiTest extends ApiBaseTestCase {
    protected static $sysadmin;
    
    public static function setUpBeforeClass() {
		  parent::setUpBeforeClass();
      self::$sysadmin = self::$freepbx->sysadmin;
    }
    
    public static function tearDownAfterClass() 
    {
      parent::tearDownAfterClass();
    }
}