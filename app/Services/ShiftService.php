<?php

namespace App\Services;

use DateTime;
use DateTimeZone;
use Exception;
use Carbon\Carbon;
use App\Exceptions\ShiftServiceException;

class ShiftService
{
    public function processShifts()
    {
        try {
            $shifts = $this->readJsonFile();
            $employeeShifts = $this->groupShiftsByEmployeeAndWeek($shifts);
            return $this->calculateEmployeeHours($employeeShifts);
        } catch (Exception $e) {
            throw new ShiftServiceException($e->getMessage());
        }
    }

    private function readJsonFile()
    {
        $jsonFile=base_path('app/dataset.json');

        if (!file_exists($jsonFile)) {
            throw new ShiftServiceException("File not found");
        }

        $data=file_get_contents($jsonFile);
        return json_decode($data,true);
    }

    private function rfc3339ToDateTime($rfc3339, $timezone = 'UTC')
    {
        $date = new DateTime($rfc3339);
        $date->setTimezone(new DateTimeZone($timezone));
        return $date;
    }

    private function calculateHours($start, $end)
    {
        $start = new DateTime($start);
        $end = new DateTime($end);
        $interval = $start->diff($end);
        return $interval->days * 24 + $interval->h + $interval->i / 60 + $interval->s / 3600;
    }

    private function isOverlapping($shift1, $shift2)
    {
        return ($shift1['StartTime'] < $shift2['EndTime'] && $shift1['EndTime'] > $shift2['StartTime']);
    }

    private function getStartOfWeek($date)
    {
        $dt = new DateTime($date);
        $dt->setTimezone(new DateTimeZone('America/Chicago'));
        $dayOfWeek = $dt->format('w'); // 0 (for Sunday) through 6 (for Saturday)
        $dt->modify("-$dayOfWeek days");
        $dt->setTime(0, 0, 0);
        return $dt->format('Y-m-d');
    }

    private function groupShiftsByEmployeeAndWeek($shifts)
    {
        $employees = [];

        foreach ($shifts as $shift) {
            $startDateTime = $this->rfc3339ToDateTime($shift['StartTime'], 'America/Chicago');
            $endDateTime = $this->rfc3339ToDateTime($shift['EndTime'], 'America/Chicago');

            if ($startDateTime->format('I')!== $endDateTime->format('I')) {
                $endDateTime->setTime(23, 59, 59);
            }

            $adjustedEndDateTime = $this->adjustForDSTTransition($startDateTime, $endDateTime);

            $shift['StartTime'] = $startDateTime->format('Y-m-d H:i:s');
            $shift['EndTime'] = $adjustedEndDateTime->format('Y-m-d H:i:s');

            $weekStart = $this->getStartOfWeek($startDateTime->format('Y-m-d H:i:s'));

            $employees[$shift['EmployeeID']][$weekStart][] = $shift;
        }

        return $employees;
    }

    private function adjustForDSTTransition($startDateTime, $endDateTime)
    {
        $startCarbon = Carbon::parse($startDateTime->format('Y-m-d H:i:s'), 'America/Chicago');
        $endCarbon = Carbon::parse($endDateTime->format('Y-m-d H:i:s'), 'America/Chicago');

        $dstStartDate = Carbon::create(2023, 3, 12, 2, 0, 0)->setTimezone('America/Chicago');
        $dstEndDate = Carbon::create(2023, 11, 5, 2, 0, 0)->setTimezone('America/Chicago');

        if ($startCarbon <= $dstStartDate && $endCarbon >= $dstEndDate) {
            $endCarbon->addHour();
        }

        return DateTime::createFromInterface($endCarbon);
    }

    private function calculateEmployeeHours($employeeShifts)
    {
        $result = [];

        foreach ($employeeShifts as $employeeID => $weeks) {
            foreach ($weeks as $weekStart => $shifts) {
                usort($shifts, function ($a, $b) {
                    return strcmp($a['StartTime'], $b['StartTime']);
                });

                $totalHours = 0;
                $invalidShifts = [];
                $validShifts = [];

                for ($i = 0; $i < count($shifts); $i++) {
                    for ($j = $i + 1; $j < count($shifts); $j++) {
                        if ($this->isOverlapping($shifts[$i], $shifts[$j])) {
                            $invalidShifts[] = $shifts[$i]['ShiftID'];
                            $invalidShifts[] = $shifts[$j]['ShiftID'];
                        }
                    }
                }

                $invalidShifts = array_unique($invalidShifts);

                foreach ($shifts as $shift) {
                    if (!in_array($shift['ShiftID'], $invalidShifts)) {
                        $hours = $this->calculateHours($shift['StartTime'], $shift['EndTime']);
                        $totalHours += $hours;
                        $validShifts[] = $shift;
                    }
                }

                $regularHours = min(40, $totalHours);
                $overtimeHours = max(0, $totalHours - 40);

                $result[] = [
                    'EmployeeID' => $employeeID,
                    'StartOfWeek' => $weekStart,
                    'RegularHours' => round($regularHours, 2),
                    'OvertimeHours' => round($overtimeHours, 2),
                    'InvalidShifts' => $invalidShifts
                ];
            }
        }

        return $result;
    }
}
