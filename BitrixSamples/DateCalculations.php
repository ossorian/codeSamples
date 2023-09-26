<?php

namespace Ac\Pm\Helpers;

use \Bitrix\Main\Type\DateTime;

final class DateCalculations
{
    const DAYS_IN_WEEK = 7;
    const WORKING_DAYS_IN_WEEK = 5;
    
    public static function isWorkingDaysFinished(DateTime $date1, DateTime $date2, int $workingDaysToBe): bool
    {
        if ($workingDaysToBe <= 0) {
            return true;
        }
        $workingDays = self::getWorkingDaysAmountBetweenDates($date1, $date2);
        return ($workingDays - $workingDaysToBe) > 0;
    }

    /**
    /* Метод расчета количества рабочийх дней между двумя датами
    */
    public static function getWorkingDaysAmountBetweenDates(DateTime $date1, DateTime $date2): int
    {
        $dayOneNumber = floor($date1->getTimestamp() / 86400);
        $dayTwoNumber = floor($date2->getTimestamp() / 86400);
        if ($dayOneNumber >= $dayTwoNumber) {
            return 0;
        }
    
        $weeksAmount = floor(($dayTwoNumber - $dayOneNumber) / self::DAYS_IN_WEEK);
        $lastWeekDays = 0;
    
        if ($daysLeft = ($dayTwoNumber - $dayOneNumber) % self::DAYS_IN_WEEK) {
            //Выходные дни недели даты постановки и даты запроса не учитываются, приводим к пятнице текущей недели
            $dayOfWeekOne = min([$date1->format('N'), 5]);
            $dayOfWeekTwo = min([$date2->format('N'), 5]);
            if ($dayOfWeekOne < $dayOfWeekTwo) {
                $lastWeekDays = $dayOfWeekTwo - $dayOfWeekOne;
            } else {
                $lastWeekDays = self::WORKING_DAYS_IN_WEEK - $dayOfWeekOne + $dayOfWeekTwo;
            }
        }
    
        return $workingDays = $weeksAmount * self::WORKING_DAYS_IN_WEEK + $lastWeekDays;
    }
}
