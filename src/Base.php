<?php

namespace Overloader;

class Base
{
    protected static $composer_bin = "composer";

    protected static $automatic_create_autoload_dump = true;

    /**
     * @return boolean
     */
    public static function isAutomaticCreateAutoloadDump()
    {
        return self::$automatic_create_autoload_dump;
    }

    /**
     * @param boolean $automatic_create_autoload_dump
     */
    public static function setAutomaticCreateAutoloadDump($automatic_create_autoload_dump)
    {
        self::$automatic_create_autoload_dump = $automatic_create_autoload_dump;
    }


    /**
     * @return string
     */
    public static function getComposerBin()
    {
        return self::$composer_bin;
    }


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
        $autoloader_path = "$root/../vendor/$vendor_name/$project_name/vendor/autoload.php";

        if (file_exists($autoloader_path)) {

            require_once $autoloader_path;

        } elseif (true === static::$automatic_create_autoload_dump) {

            $project_dir = "$root/../vendor/$vendor_name/$project_name";

            if (is_dir($project_dir)) {

                $composer_bin = static::$composer_bin;

                $cmd = <<<COMMAND
{$composer_bin} dump -n -d {$project_dir}
COMMAND;

                shell_exec($cmd);

                if (file_exists($autoloader_path)) {
                    require_once $autoloader_path;
                }
            }
        }
    }
}
