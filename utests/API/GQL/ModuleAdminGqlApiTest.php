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
  
    public function testModuleOperationswhenHookExcuteShoudReturnTrue()
    {
      $module = 'core';
      $action = 'install';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\ModulesGqlHelper::class)
       ->disableOriginalConstructor()
       ->setMethods(array('processGqlApi'))
       ->getMock();

      $mockGqlHelper->method('processGqlApi')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        moduleOperations(input: { 
          module: \"{$module}\" 
          action: \"{$action}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"moduleOperations":{"status":"true","message":"'.$action.' on '.$module.' has been initiated. Please check the getApiStatus api with the transaction id."}}}', $json);
    }

    public function testModuleOperationswhenActionParamNotSentWillReturnErrors()
    {
      $module = 'core';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\ModulesGqlHelper::class)
       ->disableOriginalConstructor()
       ->setMethods(array('processGqlApi'))
       ->getMock();

      $mockGqlHelper->method('processGqlApi')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

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

      $mockGqlHelper = $this->getMockBuilder(Freepbx\framework\amp_conf\htdocs\admin\libraries\ModulesGqlHelper::class)
       ->disableOriginalConstructor()
       ->setMethods(array('processGqlApi'))
       ->getMock();

      $mockGqlHelper->method('processGqlApi')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

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