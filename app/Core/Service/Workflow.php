<?php

namespace App\Core\Service;

class Workflow
{
  /**
   * Get the uri from the workflow stack.
   *
   * @param bool $remove - Pop the interstitial uri from the stack.
   * @return int|null  
   */
  public static function getInterstitialUri($remove = false)
  {
      $workflow = config('app.Workflow');
      
      if($workflow){
        if (is_array($workflow->interstitial) && count($workflow->interstitial)) {
            if ($remove) {
                return array_pop($workflow->interstitial);
            }
            return end($workflow->interstitial);
        }
      }      

      return null;
  }
  
  /**
   * Last in first out stack implementation.
   *
   * @param $firstGoTo
   * @param $thenAfterwards
   *
   * @return string|false  
   */
  public static function interstitial($firstGoTo = null, $thenAfterwards = null)
  {
      $workflow = config('app.Workflow');
        if($workflow){
        if (!is_array($workflow->interstitial)) {
            $workflow->interstitial = array();
        }
        
        // set
        if ($thenAfterwards) {
            array_push($workflow->interstitial, $thenAfterwards);
            return $firstGoTo;
        }
        
        // get
        if ($workflow->interstitial) {
            return array_pop($workflow->interstitial);
        }
      }
      
      return false;
  }
 
}
