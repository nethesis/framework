<?php
/**
 * This is the FreePBX Big Module Object.
 *
 * Framework built-in BMO Class.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Framework extends FreePBX_Helpers implements BMO {
	/** BMO Required Interfaces */
	public function install() {
	}
	public function uninstall() {
	}
	public function backup() {
	}
	public function restore($backup) {
	}
	public function runTests($db) {
		return true;
	}
	public function doConfigPageInit() {
	}

	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'authping':
			case 'scheduler':
			case 'sysupdate':
			case 'reload':
			case 'navbarToogle':
			return true;
		}
		return false;
	}

	public function ajaxHandler() {
		switch ($_REQUEST['command']) {
		case 'authping':
			return 'authpong';
		case 'scheduler':
			$s = new Builtin\UpdateManager();
			return $s->ajax($_REQUEST);
		case 'sysupdate':
			$s = new Builtin\SystemUpdates();
			return $s->ajax($_REQUEST);
		case 'reload':
			\FreePBX::Modules()->loadFunctionsInc('framework');
			return do_reload();
		case 'navbarToogle':
			$current = $this->getConfig("navbarToogle");
			if(empty($current)){
				$this->setConfig("navbarToogle", "no");
				$current = $this->getConfig("navbarToogle");
			}
			if(!empty($_REQUEST["click"]) && $_REQUEST["click"] == true){
				switch($current){
					case "yes":
						$this->setConfig("navbarToogle", "no");
						$current = "no";
						break;
					case "no":
						$this->setConfig("navbarToogle", "yes");
						$current = "yes";
						break;
				}
			}			
			return $current;
		}
		return false;
	}
}
