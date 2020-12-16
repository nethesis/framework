<?php

namespace FreePBX\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\EnumType;

class Modules extends Base {
	protected $description = 'Provide functionality to your PBX Modules';
	public static function getScopes() {
		return [
				'read:modules' => [
						'description' => _('Read module information'),
				],
				'write:modules' => [
						'description' => _('Module install/update/uninstall operations'),
				]
		];
	}
	public function mutationCallback() {
		if($this->checkReadScope('modules')) {
			return function() {
				return [				
				'moduleOperations' => Relay::mutationWithClientMutationId([
						'name' => 'moduleOperations',
						'description' => _('This will perform a module install/uninstall/enable/disable/downloadinstall based on action,module and track'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' =>$this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					]),
				'installOrUninstall' => Relay::mutationWithClientMutationId([
						'name' => 'installUninstall',
						'description' => _('This will perform install/uninstall module operation.'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					]),
				'enableOrDisable' => Relay::mutationWithClientMutationId([
						'name' => 'enable-disable',
						'description' => _('This will perform Enable/disable module operation.'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					]),
				'deleteOrUpgrade' => Relay::mutationWithClientMutationId([
						'name' => 'delete-upgrade',
						'description' => _('This will perform delete/upgrade module operation'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					])
				];
			};
		}
	}
	
	private function getMutationFieldModule() {
		return [
			'module' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Module name on which you want to perform any action')
			],
			'action' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Action you want to perform on a module [install/uninstall/enable/disable/remove]')
			],
			'track' => [
				'type' => Type::string(),
				'description' => _('Track module (edge/stable) ')
			]
		];
	}

	public function queryCallback() {
		if($this->checkReadScope('modules')) {
			return function() {
				return [
					'fetchAllModuleStatus' => [
						'type' => $this->typeContainer->get('module')->getConnectionType(),
						'description' => $this->description,
						'args' => array_merge(
							Relay::connectionArgs(),
							[
								'status' => [
									'type' => $this->getEnumStatuses(),
									'description' => 'Performed Module operation status',
									'defaultValue' => false
								]
							]
						),
						'resolve' => function($root, $args) {
							$modules = $this->freepbx->Modules->getModulesByStatus($args['status']);
							array_walk($modules, function(&$value, $key) {
								if(!isset($value['rawname'])) {
									$value['rawname'] = $key;
								}
							});
							return Relay::connectionFromArray(array_values($modules), $args);
						},
					],
					'fetchModuleStatus' => [
						'type' => $this->typeContainer->get('module')->getObject(),
						'description' => $this->description,
						'args' => [
							'rawname' => [
								'type' => Type::string(),
								'description' => 'The module rawname',
							]
						],
						'resolve' => function($root, $args) {
							$module = $this->freepbx->Modules->getInfo($args['rawname']);
							if(!empty($module)){
								return ['module'=> $module['builtin']['status'],'status'=>true];
							}else{
								return ['message'=> null,'status'=>false];
							}
						}
					],
					'getApiStatus' => [
						'type' => $this->typeContainer->get('module')->getObject(),
						'description' => 'Return the status of the API running Asyncronous',
						'args' => [
							'txnId' => [
								'type' => Type::nonNull(Type::id()),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							try{
								$status = $this->freepbx->api->getTransactionStatus($args['txnId']);
								if($status != null){
									return ['message' => $status, 'status' => true] ;
								}else{
									return ['message' => 'Sorry unable to fetch the status', 'status' => true] ;
								}
							}catch(Exception $ex){
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}		
						}
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('module');
		$user->setDescription('Used to manage module specific operations');

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->setGetNodeCallback(function($id) {
			$module = $this->freepbx->Modules->getInfo($id);
			return !empty($module[$id]) ? $module[$id] : null;
		});

		$user->addFieldCallback(function() {
			return [
				'status' => [
					'type' => Type::string(),
					'description' => _('Module Status')
				],
				'rawname' => [
					'type' => Type::string(),
					'description' => _('Raw name of the module')
				],
				'repo' => [
					'type' => Type::string(),
					'description' => _('The module repository information')
				],
				'name' => [
					'type' => Type::string(),
					'description' => _('The module user friendly name ')
				],
				'displayname' => [
					'type' => Type::string(),
					'description' => _('The module user friendly display name')
				],
				'version' => [
					'type' => Type::string(),
					'description' => _('The module release version ')
				],
				'dbversion' => [
					'type' => Type::string(),
					'description' => _('The module release version ')
				],
				'publisher' => [
					'type' => Type::string(),
					'description' => _('The module publisher name ')
				],
				'license' => [
					'type' => Type::string(),
					'description' => _('The module license type ')
				],
				'licenselink' => [
					'type' => Type::string(),
					'description' => _('The module license information url ')
				],
				'changelog' => [
					'type' => Type::string(),
					'description' => _('The module release changelog ')
				],
				'category' => [
					'type' => Type::string(),
					'description' => _('The module category in FreePBX UI')
				],
				'message' =>[
					'type' => Type::string(),
					'description' => _('Message for the request')
				],
				'module' =>[
					'type' => $this->getEnumStatuses(),
					'description' => _('Message for the request')
				]
			];
		});

		$user->setConnectionResolveNode(function ($edge) {
			return $edge['node'];
		});

		$user->setConnectionFields(function() {
			return [
				'totalCount' => [
					'type' => Type::int(),
					'resolve' => function($value) {
						return count($this->freepbx->Modules->getModulesByStatus());
					}
				],
				'modules' => [
					'type' => Type::listOf($this->typeContainer->get('module')->getObject()),
					'description' => $this->description,
					'resolve' => function($root, $args) {
						$data = array_map(function($row){
							return $row['node'];
						},$root['edges']);
						return $data;
					}
				]
			];
		});
	}

	private function getEnumStatuses() {
		if(!empty($this->moduleStatuses)) {
			return $this->moduleStatuses;
		}
		$this->moduleStatuses = new EnumType([
			'name' => 'Module status',
			'description' => 'Module status',
			'values' => [
				'notInstalled' => [
					'value' => 0,
					'description' => _('The module is not installed')
				],
				'disabled' => [
					'value' => 1,
					'description' => _("The module is disabled")
				],
				'enabled' => [
					'value' => 2,
					'description' => _('The module is enabled')
				],
				'needUpgrade' => [
					'value' => 3,
					'description' => _("The module needs to be upgraded")
				],
				'broken' => [
					'value' => -1,
					'description' => _('The module is broken')
				]
			]
		]);
		return $this->moduleStatuses;
	}

	public function moduleAction($input){
		$module = strtolower($input['module']);
		$action = strtolower($input['action']);
		$track = (strtoupper(isset($input['track'])) == 'EDGE') ? 'edge' : 'stable';

		$txnId = $this->freepbx->api->addTransaction("Processing","Framework","gql-module-admin");

		$ret = $this->freepbx->api->setGqlApiHelper()->initiateGqlAPIProcess(array($module,$action,$track,$txnId));

		//TODO to confirm fwconsole api started or not...

		$msg = sprintf(_('Action[%s] on module[%s] has been initiated. Please check the status using getApiStatus api with the returned transaction id'),$action, $module);
		
		return ['message' => $msg, 'status' => True ,'transaction_id' => $txnId];
	}

	public function getOutputFields(){
		return [
					'status' => [
						'type' => Type::nonNull(Type::string()),
						'resolve' => function ($payload) {
							return $payload['status'];
						}
					],
					'message' => [
						'type' => Type::string(),
						'resolve' => function ($payload) {
						return $payload['message'];
					  }
					],
					'transaction_id' => [
						'type' => Type::string(),
						'resolve' => function ($payload) {
						return $payload['transaction_id'];
					  }
					]
				];
	}
}
