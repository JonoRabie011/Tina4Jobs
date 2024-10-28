<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

//Copy the bin folder if the vendor one has changed
if (TINA4_PROJECT_ROOT !== TINA4_DOCUMENT_ROOT) {
    $tina4Checksum = md5(file_get_contents(TINA4_PROJECT_ROOT. "tina4jobsmodule". DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs"));
    $destChecksum = "";

    if (file_exists(TINA4_DOCUMENT_ROOT . "tina4jobsmodule". DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs")) {
        $checkContent = file_exists(TINA4_DOCUMENT_ROOT . "tina4jobsmodule". DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") ? file_get_contents(TINA4_DOCUMENT_ROOT . "tina4jobsmodule". DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs") : "";

//        $rootPath = str_replace("vendor" . DIRECTORY_SEPARATOR . "tina4components" . DIRECTORY_SEPARATOR . "tina4jobsmodule" . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "tina4jobs", "", __FILE__);
//        $rootPath = str_replace("bin" . DIRECTORY_SEPARATOR . "tina4jobs", "", $rootPath);
        $destChecksum = md5($checkContent);
    }

    if ($tina4Checksum !== $destChecksum) {
        \Tina4\Utilities::recurseCopy(TINA4_PROJECT_ROOT . "tina4jobsmodule". DIRECTORY_SEPARATOR . "bin", TINA4_DOCUMENT_ROOT . "bin");
    }
}