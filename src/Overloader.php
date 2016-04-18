<?php

namespace Overloader;

class Overloader
{
    /**
     * @param string[] $vendors_to_overload
     */
    public static function overload(array $vendors_to_overload)
    {
        if (count($vendors_to_overload) === 0) {
            return;
        }

        foreach ($vendors_to_overload as $vendor_name) {
            assert(is_string($vendor_name));
        }

        $all_packages = static::getAllPackagesFromComposer();

        if (count($all_packages) === 0) {
            return;
        }

        $packages_to_overload = static::compareInputVendorsAndPackages($vendors_to_overload, $all_packages);

        foreach ($packages_to_overload as $vendor_name => $packages) {
            foreach ($packages as $package_name) {
                static::overLoadPackage($vendor_name, $package_name);
            }
        }
    }

    /**
     * @return string[]
     */
    protected static function getAllPackagesFromComposer()
    {
        $project_root = static::getRoot();

        $json = json_decode(file_get_contents($project_root . '/composer.json'));

        if (empty($json->require)) {
            return [];
        }

        $all_vendors = array_keys(get_object_vars($json->require));

        return $all_vendors;
    }

    /**
     * @return string
     */
    protected static function getRoot()
    {
        return __DIR__ . '/../../../..';
    }

    /**
     * @param string[] $vendors_to_overload
     * @param string[] $composer_packages
     * @return string[][]
     */
    protected static function compareInputVendorsAndPackages(array $vendors_to_overload, array $composer_packages)
    {
        $packages_to_overload = [];

        foreach ($composer_packages as $package) {

            if (strpos($package, '/') === false) {
                continue;
            }

            list($vendor_name, $project_name) = explode('/', $package);

            if (!empty($project_name)) {
                if (in_array($vendor_name, $vendors_to_overload, true)) {
                    $packages_to_overload[$vendor_name][] = $project_name;
                }
            }
        }

        return $packages_to_overload;
    }

    /**
     * @param string $vendor_name
     * @param string $project_name
     */
    protected static function overLoadPackage($vendor_name, $project_name)
    {
        $autoloader_path = static::getRoot() . "/../vendor/$vendor_name/$project_name/vendor/autoload.php";

        if (file_exists($autoloader_path)) {

            //echo $autoloader_path, "\n";

            require_once $autoloader_path;
        }
    }

    /**
     * @param \Composer\Script\Event $event
     * @return void
     */
    public static function createDumpFiles(\Composer\Script\Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        if (
            !empty($extra['overloader']['vendors']) && is_array($extra['overloader']['vendors'])
        ) {
            $vendors_to_overload = [];
            foreach ($extra['overloader']['vendors'] as $vendor) {
                $vendors_to_overload[] = (string)$vendor;
            }

            $all_packages = static::getAllPackagesFromComposer();

            if (count($all_packages) === 0) {
                return;
            }

            $packages_to_overload = static::compareInputVendorsAndPackages($vendors_to_overload, $all_packages);

            if (empty($packages_to_overload)) {
                return;
            }

            foreach ($packages_to_overload as $vendor_name => $packages) {
                foreach ($packages as $package_name) {
                    static::reCreateDumpFile($vendor_name, $package_name);
                }
            }
        }
    }

    /**
     * @param string $vendor_name
     * @param string $project_name
     * @return void
     */
    protected static function reCreateDumpFile($vendor_name, $project_name)
    {
        assert(is_string($vendor_name));
        assert(is_string($project_name));

        $project_dir = static::getRoot() . "/../vendor/$vendor_name/$project_name";

        if (!is_dir($project_dir)) {
            return;
        }

        $composer_json_path = "$project_dir/composer.json";

        if (!file_exists($composer_json_path)) {
            return;
        }

        $cmd = <<<COMMAND
composer dump -n -d $project_dir --no-scripts 
COMMAND;

        echo $cmd;

        echo shell_exec($cmd);
    }
}
