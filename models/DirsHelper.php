<?php


namespace models;


class DirsHelper
{
    /**
     * @param $path
     * @param false $onlyFolders
     * @param array $additionalMasks
     * @return array
     */
    static function scan($path, bool $onlyFolders = false, array $additionalMasks = []): array
    {
        $data = [];

        if (!is_dir($path)) {
            return $data;
        }

        foreach (scandir($path) as $p) {
            if (
                $p !== '.' && $p !== '..'
                && (!$onlyFolders || ($onlyFolders && is_dir($path . '/' . $p)))
                && (!count($additionalMasks) || (count($additionalMasks) && !in_array($p, $additionalMasks)))
            ) {
                $data[] = $p;
            }
        }

        return $data;
    }

    /**
     * @param $src
     */
    static function remove($src) {
        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    self::remove($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}