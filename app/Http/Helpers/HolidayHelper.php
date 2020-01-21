<?php
namespace App\Http\Helpers;

class HolidayHelper
{
	public function listCurrent() 
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
            $holidays[] = date("m/d/Y", strtotime('Dec 24th'));
            $christmasTimeStamp = strtotime("Dec 25th " . date("Y", strtotime('+ ' . $y . ' year')));
            $holidays[] = date("m/d/Y", $christmasTimeStamp);
            if (date("l", $christmasTimeStamp) == 'Thursday') { // If Thursday then Friday off too
                $holidays[] = date("m/d/Y", $christmasTimeStamp + (60 * 60 * 24));
            }
        }
        return $holidays;
    }

    public function isHoliday($timestamp) 
    {
        return in_array(date('m/d/Y', $timestamp), $this->listCurrent());
    }

    /**
     * Finds closest production day during the same week
     * @param int $productionDay timestamp
     * @return int
     */
    public function closestProductionDay($productionDay) 
    {
        //preference goes to day before
        $reverse = true;
        $weekNo = date('W', $productionDay);
        $i = 0;
        while (
            $this->isHoliday($productionDay) ||
            in_array(date('w', $productionDay), array(0,6)) ||
            date('W', $productionDay) != $weekNo
        ) {
            $i++;
            $productionDay = strtotime(($reverse ? '-' : '+') . ' ' . $i . ' days', $productionDay);
            $reverse = !$reverse;
        }
        return $productionDay;
    }

    public function nextProductionDay($productionDate) 
    {
        while (
            $this->isHoliday($productionDate) ||
            in_array(date('w', $productionDate), array(0, 6))
        ) {
            $productionDate = strtotime('+ ' . '1 days', $productionDate);
        }
        return $productionDate;
    }
}







