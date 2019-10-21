<?php

namespace Megaads\Clara;

use Megaads\Clara\Event\EventManager;
use Megaads\Clara\Utils\ModuleUtil;

class Module extends EventManager
{
    /**
     * Get all modules.
     */
    public function all()
    {
        $moduleConfigs = ModuleUtil::getAllModuleConfigs();
        return $moduleConfigs['modules'];
    }
    /**
     * Get the current module detail.
     */
    public function this()
    {
        $retval = null;
        $moduleConfigs = ModuleUtil::getAllModuleConfigs();
        $moduleName = $this->getCaller();
        foreach ($moduleConfigs['modules'] as $item) {
            if ($item['name'] === $moduleName) {
                $retval = $item;
                break;
            }
        }
        return $retval;
    }
    /**
     * Get module is calling current function.
     */
    public function getCaller()
    {
        return getCallerModule();
    }
    public function option($option = "", $value = null)
    {
        if ($value == null) {
            return getModuleOption($option);
        } else {
            return setModuleOption($option, $value);
        }
    }
    public function allOptions($module = null)
    {
        return getAllModuleOptions($module);
    }
    public function moduleName($namespace)
    {
        return ModuleUtil::getModuleName($namespace);
    }
    public function missingRequiredModules($moduleName)
    {
        $retval = [];
        $loadedModules = ModuleUtil::getUserModules();
        $moduleSpecs = ModuleUtil::getModuleSpecs(app_path() . '/Modules/' . $moduleName);
        if (array_key_exists('require-modules', $moduleSpecs)) {
            $requiredModules = $moduleSpecs['require-modules'];
            foreach ($requiredModules as $key => $requiredModule) {
                $isLoaded = false;
                foreach ($loadedModules as $loadedModule) {
                    if ($requiredModule['name'] == $loadedModule['name']) {
                        $isLoaded = true;
                        break;
                    }
                }
                if (!$isLoaded) {
                    $retval[] = $requiredModule;
                }
            }
        }
        return $retval;
    }
}
