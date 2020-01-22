<?php
namespace App\Core\Utility;

use App\Core\Models\OrderCore\Api\User;

class KeyAuthenticate{
    public static function _authenticate($key = null){
        if(!$key){
            echo "API key required";
        }else{
            $apiUser=User::select()
                          ->where('token','=',$key)
                          ->first();
          if($apiUser){
            $apiUser->update(['date_last_usage'=> now()]);
            return true;
          } 
          echo "error API key invailid 401";
          
        }
    
    
      }
}