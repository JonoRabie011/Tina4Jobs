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
        // You can leave this empty if nothing needs to happen on uninstall
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
        define("JOBS_MODULE_PATH", str_replace("Tina4Job" . DIRECTORY_SEPARATOR . "Initialize.php", "", __FILE__));

        if (empty($rootPath)) {
            $rootPath = str_replace("vendor" . DIRECTORY_SEPARATOR . "tina4components" . DIRECTORY_SEPARATOR . "tina4jobsmodule" . DIRECTORY_SEPARATOR . "Tina4Job" . DIRECTORY_SEPARATOR . "Initialize.php", "", __FILE__);
//    $rootPath = str_replace("bin" . DIRECTORY_SEPARATOR . "tina4jobs", "", $rootPath);
        }

        require_once "{$rootPath}/vendor/autoload.php";

//echo "ROOT PATH: " . $rootPath . "\n";

//echo "FILE: " . __FILE__ . "\n";
//echo "JOBS_MODULE_PATH: " . JOBS_MODULE_PATH . "\n";
//echo "TINA4_PROJECT_ROOT: " . TINA4_PROJECT_ROOT . "\n";
//echo "TINA4_DOCUMENT_ROOT: " . TINA4_DOCUMENT_ROOT . "\n";


////Copy the bin folder if the vendor one has changed
        if (TINA4_PROJECT_ROOT !== TINA4_DOCUMENT_ROOT) {
            $tina4Checksum = md5(file_get_contents( JOBS_MODULE_PATH . "bin" . DIRECTORY_SEPARATOR . "tina4jobs"));
            $destChecksum = "";

            if (file_exists(JOBS_MODULE_PATH . "bin" . DIRECTORY_SEPARATOR . "tina4jobs")) {
                $checkContent = file_exists(TINA4_DOCUMENT_ROOT  . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") ? file_get_contents(TINA4_DOCUMENT_ROOT  . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") : "";

//        $rootPath = str_replace("vendor" . DIRECTORY_SEPARATOR . "tina4components" . DIRECTORY_SEPARATOR . "tina4jobsmodule" . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs", "", __FILE__);
//        $rootPath = str_replace("bin" . DIRECTORY_SEPARATOR . "tina4jobs", "", $rootPath);
                $destChecksum = md5($checkContent);
            }

            if ($tina4Checksum !== $destChecksum) {
                \Tina4\Utilities::recurseCopy(JOBS_MODULE_PATH  . "bin", TINA4_DOCUMENT_ROOT . "bin");
            }
        }
        // Place any setup code here, like creating directories, setting configurations, etc.
    }
}
