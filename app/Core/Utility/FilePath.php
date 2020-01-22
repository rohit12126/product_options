<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/17/16
 * Time: 9:56 AM
 */

namespace App\Core\Utility;


class FilePath
{

    public static function calculatePath($lastDir = null, $pad = true)
    {
        $d = getdate(time());
        $lastDir = $lastDir ? $lastDir : uniqid('df-');
        if ($pad) {
            return sprintf(
                '%04d/%02d/%02d/%02d/%s', $d['year'], $d['mon'], $d['mday'], $d['hours'], $lastDir
            );
        } else {
            return sprintf(
                '%d/%d/%d/%d/%s', $d['year'], $d['mon'], $d['mday'], $d['hours'], $lastDir
            );
        }
    }
}