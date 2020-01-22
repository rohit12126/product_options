<?php
namespace App\Core\Service\State\Design;

class Template
{
    public static function isComplete()
    {        
        if (!session()->has('userProjectId')) return false;
        return true;
    }

    public static function reset()
    {        
        session()->forget('userProjectId');
    }
}