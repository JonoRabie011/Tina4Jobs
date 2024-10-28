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

        if(file_exists($modulePath . DIRECTORY_SEPARATOR . "docker". DIRECTORY_SEPARATOR . "Dockerfile")) {
            rmdir($modulePath . DIRECTORY_SEPARATOR . "docker");
        }
    }
}
