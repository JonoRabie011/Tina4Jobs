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

//        file_get_contents(/var/www/html/vendor/tina4components/tina4jobsmodule/Tina4Job//InstallerPlugin.phpbin/tina4jobs): Failed to open stream: No such file or directory

        define("JOBS_MODULE_PATH", str_replace("Tina4Job" . DIRECTORY_SEPARATOR . "/InstallerPlugin.php", "", __FILE__));

        if (TINA4_PROJECT_ROOT !== TINA4_DOCUMENT_ROOT) {
            $tina4Checksum = md5(file_get_contents( JOBS_MODULE_PATH . "bin" . DIRECTORY_SEPARATOR . "tina4jobs"));
            $destChecksum = "";

            if (file_exists(JOBS_MODULE_PATH . "bin" . DIRECTORY_SEPARATOR . "tina4jobs")) {
                $checkContent = file_exists(TINA4_DOCUMENT_ROOT  . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") ? file_get_contents(TINA4_DOCUMENT_ROOT  . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") : "";
                $destChecksum = md5($checkContent);
            }

            if ($tina4Checksum !== $destChecksum) {
                \Tina4\Utilities::recurseCopy(JOBS_MODULE_PATH  . "bin", TINA4_DOCUMENT_ROOT . "bin");
            }
        }
        // Place any setup code here, like creating directories, setting configurations, etc.
    }
}
