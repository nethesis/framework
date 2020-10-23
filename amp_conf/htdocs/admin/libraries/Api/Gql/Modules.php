<?php

namespace FreePBX\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\EnumType;

class Modules extends Base {
	protected $description = 'Modules provide functionality to your PBX';
	public static function getScopes() {
		return [
				'read:modules' => [
						'description' => _('Read module information'),
				],
				'write:modules' => [
						'description' => _('module upgrade/degrade operations'),
				]
		];
	}
	public function mutationCallback() {
		if($this->checkReadScope('modules')) {
			return function() {
				return [
				'moduleupgrade' => Relay::mutationWithClientMutationId([
						'name' => 'moduleupgrade',
						'description' => _('upgrage a given module from the given track(edge/stable)'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'upgraderesult' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['upgraderesult'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$module = strtolower($input['module']);
							$track = $input['track'];
							$bin = \FreePBX::Config()->get('AMPSBIN');
							$cmd = $bin.'/fwconsole ma downloadinstall '.$module;
							if(strtoupper($track) == 'EDGE'){
								$cmd = $cmd.' --edge';
							}else {
								$cmd = $cmd.' --stable';
							}
							exec($cmd, $output, $retval);
							$output = json_encode($output);
							return ['upgraderesult' => $output];
						}
					]),
				'moduletag' => Relay::mutationWithClientMutationId([
						'name' => 'moduletag',
						'description' => _('upgrage/degrade a module using tag'),
						'inputFields' => $this->getMutationFieldstag(),
						'outputFields' => [
							'upgraderesult' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['upgraderesult'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$module = strtolower($input['module']);
							$tag = $input['tag'];
							$bin = \FreePBX::Config()->get('AMPSBIN');
							$cmd = $bin.'/fwconsole ma downloadinstall '.$module.' --tag '.$tag;
							exec($cmd, $output, $retval);
							$output = json_encode($output);
							return ['upgraderesult' => $output];
						}
					]),
					'moduleaction' => Relay::mutationWithClientMutationId([
						'name' => 'moduleaction',
						'description' => _('perform a module action '),
						'inputFields' => $this->getMutationFieldmodule(),
						'outputFields' => [
							'actionstatus' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['actionstatus'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$module = strtolower($input['module']);
							$action = strtolower($input['action']);
							$bin = \FreePBX::Config()->get('AMPSBIN');
							$cmd = $bin.'/fwconsole ma '.$action.' '.$module;
							exec($cmd, $output, $retval);
							$output = json_encode($output);
							return ['actionstatus' => $output];
						}
					])
					
				];
			};
		}
	}
	private function getMutationFields() {
		return [
			'module' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('module Name which you want to upgrade')
			],
			'track' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Track name (edge/stable) ')
			],
		];
	}
	
	private function getMutationFieldmodule() {
		return [
			'module' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('module Name ')
			],
			'action' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Action you want perform on a module [install/uninstall/enable/disable/remove]')
			],
		];
	}

	private function getMutationFieldstag() {
		return [
			'module' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('module Name which you want to upgrade/degrade')
			],
			'tag' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Version number')
			],
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
							'id' => [
								'type' => Type::id(),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							$module = $this->freepbx->Modules->getInfo(Relay::fromGlobalId($args['id'])['id']);
							return !empty($module) ? $module : null;
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
				'id' => Relay::globalIdField('module', function($row) {
					return $row['rawname'];
				}),
				'status' => [
					'type' => $this->getEnumStatuses(),
					'description' => 'Module Status'
				],
				'rawname' => [
					'type' => Type::string(),
					'description' => 'The number to block'
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
			'name' => 'modulesStatuses',
			'description' => 'Module Statuses',
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
}
