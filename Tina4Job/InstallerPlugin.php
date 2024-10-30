<?php

namespace Tina4Jobs;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\IO\IOInterface;

class InstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // Initialization code if needed when plugin is activated
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // You can leave this empty if nothing needs to happen on deactivation
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $projectRoot = getcwd();
        file_exists($projectRoot . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") ? unlink($projectRoot . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") : "";
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'initialize',
            'post-update-cmd' => 'initialize',
        ];
    }

    public static function initialize(Event $event)
    {

        $projectRoot = getcwd();
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $modulePath = $vendorDir . DIRECTORY_SEPARATOR . "tina4components" . DIRECTORY_SEPARATOR . "tina4jobsmodule";
        $tina4JobsBin = $modulePath . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs";

        $vendorChecksum = md5(file_get_contents( $tina4JobsBin));
        $destChecksum = "";

        if (file_exists($tina4JobsBin)) {
            $checkContent = file_exists($projectRoot . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") ? file_get_contents($projectRoot . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") : "";
            $destChecksum = md5($checkContent);
        }

        if ($vendorChecksum !== $destChecksum) {
            \Tina4\Utilities::recurseCopy($modulePath . DIRECTORY_SEPARATOR  . "bin", $projectRoot . DIRECTORY_SEPARATOR . "bin");
        }

        //Remove Docker folder
        self::deleteDirectory($modulePath . DIRECTORY_SEPARATOR . "docker");

        // Add custom Composer scripts
        self::addCustomComposerScripts($projectRoot);

        // Check for environment variables
        self::checkEnvironmentVariables($projectRoot);

    }

    /**
     * Add custom Composer scripts to the main project's composer.json
     * @param $projectRoot
     * @return void
     */
    static private function addCustomComposerScripts($projectRoot): void
    {
        $composerJsonPath = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerJsonPath)) {
            echo "composer.json not found in main project directory.\n";
            return;
        }

        // Load and decode composer.json
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error decoding composer.json: " . json_last_error_msg() . "\n";
            return;
        }

        // Modify or add custom scripts
        $composerJson['scripts']['tina4jobs'] = "tina4jobs";
        $composerJson['scripts']['start-jobs'] = "@tina4jobs";

        // Encode and save the modified composer.json
        file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        echo "Custom scripts added to composer.json\n";

        // Optionally, refresh Composer's autoload
        passthru('composer dump-autoload');
    }

    /**
     * Recursively delete a directory
     * @param $dir
     * @return bool
     */
    static private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        // Open the directory and loop through its contents
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                // Recursively delete subdirectory
                deleteDirectory($filePath);
            } else {
                // Delete file
                unlink($filePath);
            }
        }

        // Remove the now-empty directory
        return rmdir($dir);
    }


    static private function checkEnvironmentVariables($projectRoot): void
    {
        $envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

        $keys = [
            "REDIS_HOST" => "localhost",
            "REDIS_PORT" => "6379",
        ];


        if(file_exists($envPath)) {

            $existingKeys = self::readEnv($envPath);

            foreach ($keys as $key => $value) {
                if(!in_array($key, $existingKeys)) {
                    self::writeToEnv($envPath, $key, $value);
                }
            }
        } else {
            foreach ($keys as $key => $value) {
                self::writeToEnv($envPath, $key, $value);
            }
        }

        // Optionally, refresh Composer's autoload
        passthru('composer dump-autoload');
    }


    /**
     * Function to read current .env
     * @param $path
     * @return array
     */
    private static function readEnv($path): array
    {
        $existingKeys = [];

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (str_starts_with(trim($line), '#') || str_starts_with(trim($line), '[') || !str_contains(trim($line), 'REDIS')) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $existingKeys[] = trim($name);
        }

        return $existingKeys;
    }

    /**
     * Function to set up your ENV after install
     * @param $env
     * @param $key
     * @param $data
     * @return void
     */
    private static function writeToEnv($env, $key, $data): void
    {
        file_put_contents($env, "\n" . $key . "=" . $data, FILE_APPEND);
    }
}
