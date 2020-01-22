<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 12/28/16
 * Time: 1:16 PM
 */

namespace App\Core\Utility;


class ProductionDate
{

    private static function _holidayList()
    {
        $holidays = array();
        for ($y = 0; $y < 10; $y++) {
            $holidays[] = date("m/d/Y", strtotime("Jan 1st " . date("Y", strtotime('+ ' . $y . ' year'))));
            $holidays[] = date(
                "m/d/Y", strtotime("last monday of May " . date("Y", strtotime('+ ' . $y . ' year')))
            );
            $holidays[] = date("m/d/Y", strtotime("Jul 4th " . date("Y", strtotime('+ ' . $y . ' year'))));
            $holidays[] = date(
                "m/d/Y", strtotime("first monday of Sept " . date("Y", strtotime('+ ' . $y . ' year')))
            );
            $thanksgiving = date(
                "m/d/Y", strtotime(
                           "fourth thursday of Nov " . date("Y", strtotime('+ ' . $y . ' year'))
                       )
            );
            $holidays[] = $thanksgiving;
            $holidays[] = date("m/d/Y", strtotime("+1 day", strtotime($thanksgiving)));
            $christmasTimeStamp = strtotime("Dec 25th " . date("Y", strtotime('+ ' . $y . ' year')));
            $holidays[] = date("m/d/Y", $christmasTimeStamp);
            if (date("l", $christmasTimeStamp) == 'Thursday') { // If Thursday then Friday off too
                $holidays[] = date("m/d/Y", $christmasTimeStamp + (60 * 60 * 24));
            }
        }

        return $holidays;
    }


    public static function getNextAvailable($timestamp)
    {
        // if is after 11am, move day to next
        $test = date('G', $timestamp);
        if (11 <= date('G', $timestamp)) {
            $timestamp = strtotime('+1 day midnight', $timestamp);
        }

        $holidays = self::_holidayList();
        // step forward looking for next
        while (
            in_array(date('m/d/Y', $timestamp), $holidays) ||
            in_array(date('w', $timestamp), array(0, 6))
        ) {
            $timestamp = strtotime('+' . ' 1 days', $timestamp);
        }
        return $timestamp;
    }

    public static function closestProductionDay($productionDay)
    {
        //preference goes to day before
        $reverse = true;
        $weekNo = date('W', $productionDay);
        $i = 0;
        $holidays = self::_holidayList();
        while (
            in_array(date('m/d/Y', $productionDay), $holidays) ||
            in_array(date('w', $productionDay), array(0,6)) ||
            date('W', $productionDay) != $weekNo
        ) {
            $i++;
            $productionDay = strtotime(($reverse ? '-' : '+') . ' ' . $i . ' days', $productionDay);
            $reverse = !$reverse;
        }
        return $productionDay;
    }

}