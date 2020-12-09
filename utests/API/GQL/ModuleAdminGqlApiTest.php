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
    }
    
    public static function tearDownAfterClass() 
    {
      parent::tearDownAfterClass();
    }

    public function testModuleOperationswhenHookDoesNotExcuteShoudReturnErrors()
    {
      $module = 'core';
      $action = 'install';

      $mockRunhook = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\BMO\Hooks::class)
       ->disableOriginalConstructor()
       ->setMethods(array('runModuleSystemHook'))
       ->getMock();

     $mockRunhook->method('runModuleSystemHook')->willReturn(false);

     self::$freepbx->Modules->setRunHook($mockRunhook);   

      $response = $this->request("mutation {
        moduleOperations(input: { 
          module: \"{$module}\" 
          action: \"{$action}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"errors":[{"message":"Sorry could not initiate '.$action.' on '.$module.'","status":"false"}]}', $json);
    }

    public function testModuleOperationswhenHookExcuteShoudReturnTrue()
    {
      $module = 'core';
      $action = 'install';

     $mockRunhook = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\BMO\Hooks::class)
       ->disableOriginalConstructor()
       ->setMethods(array('runModuleSystemHook'))
       ->getMock();

     $mockRunhook->method('runModuleSystemHook')->willReturn(true);

     self::$freepbx->Modules->setRunHook($mockRunhook);  

      $response = $this->request("mutation {
        moduleOperations(input: { 
          module: \"{$module}\" 
          action: \"{$action}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"moduleOperations":{"status":"true","message":"'.$action.' on '.$module.' has been initiated,Kindly check the status details api with the transaction id."}}}', $json);
    }

    public function testModuleOperationswhenActionParamNotSentWillReturnErrors()
    {
      $module = 'core';

      $mockRunhook = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\BMO\Hooks::class)
       ->disableOriginalConstructor()
       ->setMethods(array('runModuleSystemHook'))
       ->getMock();

     $mockRunhook->method('runModuleSystemHook')->willReturn(true);

     self::$freepbx->Modules->setRunHook($mockRunhook);  

      $response = $this->request("mutation {
        moduleOperations(input: { 
          module: \"{$module}\"  }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"errors":[{"message":"Field moduleOperationsInput.action of required type String! was not provided.","status":false}]}', $json);
    }

    public function testModuleOperationsWhenModuleParamNotSentWillReturnErrors()
    {
      $action = 'install';

     $mockRunhook = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\BMO\Hooks::class)
       ->disableOriginalConstructor()
       ->setMethods(array('runModuleSystemHook'))
       ->getMock();

     $mockRunhook->method('runModuleSystemHook')->willReturn(true);

     self::$freepbx->Modules->setRunHook($mockRunhook);  

      $response = $this->request("mutation {
        moduleOperations(input: { 
          action: \"{$action}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"errors":[{"message":"Field moduleOperationsInput.module of required type String! was not provided.","status":false}]}', $json);
    }
    public function testModuleStatusWillReturnStatus()
    {
      $rawname = "module:core";

      $response = $this->request("query{
        module (rawname:\"{$rawname}\"){
          message status
        }
      }");

		  $json = (string)$response->getBody();

      $module = self::$freepbx->Modules->getInfo($rawname);

			if(!empty($module)){
        $res = $module['builtin']['status'] == 2 ? 'enabled' : 'disabled';
				$actual =  ['message'=> $res,'status'=>"true"];
			}else{
				$actual = ['message'=> null,'status'=>"false"];
      }        
	  	$this->assertEquals('{"data":{"module":'.json_encode($actual).'}}', $json);
    }
}