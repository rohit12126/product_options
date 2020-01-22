<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/9/17
 * Time: 2:25 PM
 */

namespace App\Core\Utility;


use Exception;

class UserPath
{

    public static function calculate($date, $lastDirPrefix, $lastDirOverride=null)
    {
        if (empty($date)) {
            throw new Exception(
                '$date must be set before call to ' . __CLASS__ . '::' . __FUNCTION__ . '()'
            );
        }
        if (empty($lastDirPrefix)) {
            throw new Exception('$lastDirPrefix must be set before call to ' . __FUNCTION__ . '()');
        }
        $d = getdate($date);
        if ($lastDirOverride) {
            $lastDir = $lastDirOverride;
        } else {
            $lastDir = uniqid($lastDirPrefix . '-');
        }
        return sprintf('%04d/%02d/%02d/%02d/%s', $d['year'], $d['mon'], $d['mday'], $d['hours'], $lastDir);
    }

}