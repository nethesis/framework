<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\ObjectType;

class System extends Base {
	public static function getScopes() {
		return [
				'read:system' => [
						'description' => _('Read system information'),
				],
				'write:system' => [
						'description' => _('Update System Informations'),
				]
		];
	}

	public function mutationCallback() {
		if($this->checkReadScope('system')) {
			return function() {
				return [
				'updateintialsetup' => Relay::mutationWithClientMutationId([
					'name' => 'updateAdminAuth',
					'description' => _('This will Set the Administator Auth credentials'),
					'inputFields' => $this->getMutationFieldAuth(),
					'outputFields' =>[],
					'mutateAndGetPayload' => function ($input) {
						return $this->updateintialsetup($input);
					}
				])
				];
			};
		}
	}


	public function queryCallback() {
		if($this->checkReadScope("system")) {
			return function() {
				return [
					'system' => [
						'type' => $this->typeContainer->get('system')->getObject(),
						'description' => 'General System information',
						'resolve' => function($root, $args) {
							return []; //trick the resolver into not thinking this is null
						}
					]
				];
			};
		}
	}

	private function getMutationFieldinitalsetup() {
		return [
			'username' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('PBX GUI administrator username')
			],
			'password' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('PBX GUI administrator password')
			],
			'email' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Notification Email address')
			],
			'system_ident' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('System Identity')
			],
			'auto_module_updates' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Authomatic module updates(enabled,disabled,emailonly')
			],
			'auto_module_security_updates' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Authomatic Module Security updates Email address(enabled,disabled)')
			],
			'unsigned_module_emails' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Send Security Emails for Unsigned Modules(enabled,disabled)')
			],
			'update_every' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Check for update on Every (day,monday,tuesday,wednesday,thursday,friday,saterday,sunday)')
			],
			'update_period' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Update sytem time(0to4,4to8,8to12,12to16,16to20,20to0)'
			],
		];
	}
	public function initializeTypes() {
		$user = $this->typeContainer->create('system', 'object');
		$user->setDescription('System Information');

		$user->addFieldCallback(function() {
			return [
				'version' => [
					'type' => Type::string(),
					'description' => 'Version of the PBX',
					'resolve' => function ($root, $args) {
						return getVersion();
					}
				],
				'engine' => [
					'type' => Type::string(),
					'description' => 'Version of Asterisk',
					'resolve' => function ($root, $args) {
						return engine_getinfo()['version'];
					}
				],
				'needReload' => [
					'type' => Type::boolean(),
					'description' => 'Does the system need to be reloaded?',
					'resolve' => function ($root, $args) {
						return check_reload_needed();
					}
				]
			];
		});
	}

	private function updateintialsetup($settings) {
		$db = $this->freepbx->Database();
		$sth = $db->prepare("INSERT INTO `ampusers` (`username`, `password_sha1`, `sections`) VALUES ( ?, ?, '*')");

		$username = htmlentities(strip_tags($settings['username']));

		$sth->execute(array($username, sha1($settings['password'])));

		$um = new \FreePBX\Builtin\UpdateManager();
		$um->updateUpdateSettings($settings);
		$um->setNotificationEmail($settings['email']);
		// need to make in OOBE as framework completed
		$this->completeOOBE('framework');

	}
	private function completeOOBE($mod = false) {
		if (!$mod) {
			throw new \Exception("No module given to mark as complete");
		}
		$complete = $this->getConfig("completed");
		if (!is_array($complete)) {
			$complete = array($mod => $mod);
		} else {
			$complete[$mod] = $mod;
		}

		$this->setConfig("completed", $complete);
	}
}
