
<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * This is a very basic interface to the existing 'module_functions' class.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
class ModulesGqlHelper {

	public function __construct() {
	}

	public function execModuleGqlApi($args) {

		$apiObj = \FreePBX::Api();

		$action = $args[0];
		$module = $args[1];
		$track = $args[2];
		$txnId = $args[3];

		$bin = \FreePBX::Config()->get('AMPSBIN');
		$cmd = $bin.'/fwconsole ma '.$action.' '.$module.' --'.$track;
		$result = exec($cmd);
		$reason = explode(PHP_EOL,$result);

		if (!empty($result)) {
			$apiObj->setTransactionStatus($txnId,'Executed',$reason);
		} else {
			$apiObj->setTransactionStatus($txnId,'Failed',$reason);
		}
	}
}
