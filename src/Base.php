<?php

namespace Overloader;

class Base
{
    public static function load(array $vendors_to_overload)
    {
        if (empty($vendors_to_overload)) {
            return;
        }

        foreach ($vendors_to_overload as $vendor_name) {
            assert(is_string($vendor_name));
        }

        $root = __DIR__ . '/../../../..';

        $json = json_decode(file_get_contents($root . '/composer.json'));

        $all_packages = array_keys(get_object_vars($json->require));

        if (empty($all_packages)) {
            return;
        }

        $packages_to_overload = [];

        foreach ($all_packages as $package) {
            list($vendor_name, $project_name) = explode('/', $package);
            if (!empty($project_name)) {
                if (in_array($vendor_name, $vendors_to_overload, true)) {
                    $packages_to_overload[$vendor_name][] = $project_name;
                }
            }
        }

        foreach ($packages_to_overload as $vendor_name => $projects) {
            foreach ($projects as $project_name) {
                static::overLoadVendor($root, $vendor_name, $project_name);
            }
        }
    }

    protected static function overLoadVendor($root, $vendor_name, $project_name)
    {
        $autoloader_path = "$root/vendor/$vendor_name/$project_name/vendor/autoload.php";

        if (file_exists($autoloader_path)) {
            require_once $autoloader_path;
        }
    }
}
