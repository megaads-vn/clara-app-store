<?php

namespace Megaads\Clara\Utils;

use Illuminate\Support\Facades\File;

class ModuleUtil
{
    /**
     * Add config value to module setting
     */
    public static function setModuleConfig($configs = [])
    {
        $data = [];
        if (File::exists(base_path('module.json'))) {
            $jsonString = file_get_contents(base_path('module.json'));
            $data = json_decode($jsonString, true);
        }
        // Update Key
        foreach ($configs as $key => $value) {
            $data[$key] = $value;
        }
        // Write File
        $newJsonString = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents(base_path('module.json'), stripslashes($newJsonString));
    }
    /**
     * Get all module config values
     */
    public static function getAllModuleConfigs($configs = [])
    {
        $retval = [];
        if (File::exists(base_path('module.json'))) {
            $jsonString = file_get_contents(base_path('module.json'));
            $retval = json_decode($jsonString, true);
        }
        return $retval;
    }

    public static function getModuleSpecs($modulePath)
    {
        $retval = [];
        $moduleSpecFile = $modulePath . '/module.json';
        if (File::exists($moduleSpecFile)) {
            $jsonString = file_get_contents($moduleSpecFile);
            $retval = json_decode($jsonString, true);
        }
        return $retval;
    }
    public static function getUserModules()
    {
        $retval = [];
        $uuid = env('SHOP_UUID', 'NA');
        $retval = \Megaads\Clara\Models\App::where('shop_uuid', '=', $uuid)->get()->toArray();
        return $retval;
    }
}
