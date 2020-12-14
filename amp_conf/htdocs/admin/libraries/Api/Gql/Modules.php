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
						'description' => _('Module upgrade/degrade operations'),
				]
		];
	}
	public function mutationCallback() {
		if($this->checkReadScope('modules')) {
			return function() {
				return [				
				'moduleOperations' => Relay::mutationWithClientMutationId([
						'name' => 'moduleOperations',
						'description' => _('Will Perform a module install/uninstall/enable/disable/downloadinstall based on action,module and track'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' =>$this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					]),
				'installOrUninstall' => Relay::mutationWithClientMutationId([
						'name' => 'installUninstall',
						'description' => _('Will Perform a module install/uninstall based on action,module and track'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					]),
				'enableOrDisable' => Relay::mutationWithClientMutationId([
						'name' => 'enable-disable',
						'description' => _('Will Perform a module enable/disable based on action,module and track'),
						'inputFields' => $this->getMutationFieldModule(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							return $this->moduleAction($input);
						}
					]),
				'deleteOrUpgrade' => Relay::mutationWithClientMutationId([
						'name' => 'delete-upgrade',
						'description' => _('Will Perform a module delete/upgrade based on action,module and track'),
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
				'description' => _('Module name which you want to upgrade/degrade')
			],
			'action' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Action you want perform on a module [install/uninstall/enable/disable/remove]')
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
					'allModules' => [
						'type' => $this->typeContainer->get('module')->getConnectionType(),
						'description' => $this->description,
						'args' => array_merge(
							Relay::connectionArgs(),
							[
								'status' => [
									'type' => $this->getEnumStatuses(),
									'description' => 'The final known disposition of the CDR record',
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
					'module' => [
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
								return ['message'=> $module['builtin']['status'],'status'=>true];
							}else{
								return ['message'=> null,'status'=>false];
							}
						}
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('module');
		$user->setDescription('Used to manage a system wide list of blocked callers');

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
					'description' => 'Module Status'
				],
				'rawname' => [
					'type' => Type::string(),
					'description' => 'Raw name of the module'
				],
				'repo' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'name' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'displayname' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'version' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'dbversion' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'publisher' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'license' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'licenselink' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'changelog' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'category' => [
					'type' => Type::string(),
					'description' => 'The number to block'
				],
				'message' =>[
					'type' =>  $this->getEnumStatuses(),
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
					'description' => 'The module is not installed'
				],
				'disabled' => [
					'value' => 1,
					'description' => "The module is disabled"
				],
				'enabled' => [
					'value' => 2,
					'description' => 'The module is enabled'
				],
				'needUpgrade' => [
					'value' => 3,
					'description' => "The module needs to be upgraded"
				],
				'broken' => [
					'value' => -1,
					'description' => 'The module is broken'
				]
			]
		]);
		return $this->moduleStatuses;
	}

	public function moduleAction($input){
		$module = strtolower($input['module']);
		$action = strtolower($input['action']);
		$track = (strtoupper(isset($input['track'])) == 'EDGE') ? 'edge' : 'stable';

		$txnId = $this->freepbx->api->addTransaction("Processing","Framework","gql-run-module-admin");

		$this->initiateGqlAPIProcess(array($module,$action,$track,$txnId));
		
		return ['message' => "$action on $module has been initiated. Please check the getApiStatus api with the transaction id.", 'status' => True ,'transaction_id' => $txnId];
	}

	// run as background job	
	public function initiateGqlAPIProcess($args) {
		$bin = $this->freepbx->Config()->get('AMPSBIN');
		shell_exec($bin.'/fwconsole api gql '.$args[0].' '.$args[1].' '.$args[2].' '.$args[3].' >/dev/null 2>/dev/null &');
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
