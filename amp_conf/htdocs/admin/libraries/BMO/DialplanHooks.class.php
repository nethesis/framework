<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class DialplanHooks {

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Need to be instantiated with a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
	}

	public function getAllHooks($active_modules = null) {
		if ($active_modules == null) {
			throw new \Exception(_("Don't know about modules yet. It needs to be handed to me"));
		}

		// Note that OldHooks and NewHooks return a COMPLETELY DIFFERENT structure.
		$oldHooks = $this->getOldHooks($active_modules);
		$newHooks = $this->getBMOHooks();

		// Merge newHooks into oldHooks and return it.
		foreach ($newHooks as $module => $priority) {
			// Note that a module may want to hook in several times, so priority may be an array.
			if (is_array($priority)) {
				throw new \Exception(_("Multiple hooks unimplemented"));
			}

			// If the module is returning 'false', then it doesn't want to hook the dialplan.
			if ($priority === false) {
				continue;
			}

			// A 'true' return means 'yes, I do want to hook, at the default priority' which
			// is 500.
			if ($priority === true) {
				$priority = 500;
			}

			if (!is_numeric($priority)) {
				throw new \Exception(_("Priority needs to be either 'true', 'false' or a number"));
			}

			$oldHooks[$priority][$module][] = array("Class" => $module);
		}

		// Sort them by priority before returning them.
		ksort($oldHooks);

		return $oldHooks;
	}

	public function processHooks($engine, $hooks = null) {
		global $ext;

		if ($hooks == null)
			throw new \Exception("I wasn't given any modules to hook. Bug.");

		// The array should already be sorted before it's given to us. Don't
		// sort again. Just run through it!
		foreach ($hooks as $pri => $hook) {
			foreach ($hook as $module => $cmds) {
				\modgettext::push_textdomain(strtolower($module));
				foreach($cmds as $cmd) {
					// Is this an old-style function call? (_hookGet, _hook_core etc)
					if (isset($cmd['function'])) {
						$func = $cmd['function'];
						if (!function_exists($func)) {
							// Old style modules may be licenced, and as such their functions may not be there. Let's see if this
							// module is one of those.
							$funcarr = explode("_", $func);
							$x = $this->FreePBX->Modules->getInfo($funcarr[0]);
							if (isset($x[$funcarr[0]]) && $x[$funcarr[0]]['license'] == "Commercial") {
								continue;
							} else {
								out(_("HANDLED-ERROR: %s should exist, but it doesn't. This is a bug"), $func);
								continue;
							}
						}
						$this->FreePBX->Performance->Stamp("olddialplanHook-".$func."_start");
						$func($engine);
						$this->FreePBX->Performance->Stamp("olddialplanHook-".$func."_stop");
					} elseif (isset($cmd['Class'])) {
						// This is a new BMO Object!
						$class = $cmd['Class'];
						if (!method_exists($this->FreePBX->$class, "doDialplanHook")) {
							out(_("HANDLED-ERROR: %s->doDialplanHook() isn't there, but the module is saying it wants to hook. This is a bug"), $class);
							continue;
						}
						$this->FreePBX->Performance->Stamp($class."->doDialplanHook_start");
						$this->FreePBX->$class->doDialplanHook($ext, $engine, $pri);
						$this->FreePBX->Performance->Stamp($class."->doDialplanHook_stop");
					} else {
						// I have no idea what this is.
						throw new \Exception(sprintf(_("I was handed %s to hook. Don't know how to handle it"),json_encode($cmd)));
					}
				}
				\modgettext::pop_textdomain();
			}
		}
	}

	private function getOldHooks($active_modules) {
		// Moved from retrieve_conf

		// Check to make sure we actually were given modules.
		if(!is_array($active_modules))
			throw new \Exception("I'm unaware what I was given as $active_modules");

		// Loop through all our modules
		$hooksDiscovered = array();
		foreach($active_modules as $module => $mod_data) {
			// Some modules specify they want to run at
			// a specific priority, in module.xml.  Let them.
			if (isset($mod_data['methods'], $mod_data['methods']['get_config'])){
				foreach ($mod_data['methods']['get_config'] as $pri => $methods) {
					foreach($methods as $method) {
						$funclist[$pri][$module][] = array("function" => $method);
						$hooksDiscovered[$method] = true;
					}
				}
			}

			// Historically, Modules have been doing their dialplan hooks using either
			// modulename_get_config or modulename_hookGet_config.
			$getconf = $module."_get_config";
			$hookgetconf = $module."_hookGet_config";
			if (function_exists($getconf) && !isset($hooksDiscovered[$getconf])) {
				$funclist[100][$module][] = array("function" => $getconf);
			}
			if (function_exists($hookgetconf) && !isset($hooksDiscovered[$getconf])) {
				$funclist[600][$module][] = array("function" => $hookgetconf);
			}

		}
		// Return it!
		return $funclist;
	}

	public function getBMOHooks() {

		$allHooks = $this->FreePBX->Hooks->getAllHooks();
		return $allHooks['DialplanHooks'];

	}
}
