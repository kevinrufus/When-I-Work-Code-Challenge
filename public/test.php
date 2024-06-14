<?php

function readJsonFile($filename) {
    if (!file_exists($filename)) {
        throw new Exception("File not found: $filename");
    }
    $data = file_get_contents($filename);
    return json_decode($data, true);
}

function rfc3339ToDateTime($rfc3339, $timezone = 'UTC') {
    $date = new DateTime($rfc3339);
    $date->setTimezone(new DateTimeZone($timezone));
    return $date;
}

function calculateHours($start, $end) {
    $start = new DateTime($start);
    $end = new DateTime($end);
    $interval = $start->diff($end);
    return $interval->days * 24 + $interval->h + $interval->i / 60 + $interval->s / 3600;
}

function isOverlapping($shift1, $shift2) {
    return ($shift1['StartTime'] < $shift2['EndTime'] && $shift1['EndTime'] > $shift2['StartTime']);
}

function getStartOfWeek($date) {
    $dt = new DateTime($date);
    $dt->setTimezone(new DateTimeZone('America/Chicago'));
    $dayOfWeek = $dt->format('w'); // 0 (for Sunday) through 6 (for Saturday)
    $dt->modify("-$dayOfWeek days");
    $dt->setTime(0, 0, 0);
    return $dt->format('Y-m-d');
}

function processShifts($shifts) {
    $employees = [];

    foreach ($shifts as $shift) {
        $shift['StartTime'] = rfc3339ToDateTime($shift['StartTime'], 'America/Chicago')->format('Y-m-d H:i:s');
        $shift['EndTime'] = rfc3339ToDateTime($shift['EndTime'], 'America/Chicago')->format('Y-m-d H:i:s');
        
        $weekStart = getStartOfWeek($shift['StartTime']);
        $employees[$shift['EmployeeID']][$weekStart][] = $shift;
    }

    return $employees;
}

function calculateEmployeeHours($employeeShifts) {
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
                    if (isOverlapping($shifts[$i], $shifts[$j])) {
                        $invalidShifts[] = $shifts[$i]['ShiftID'];
                        $invalidShifts[] = $shifts[$j]['ShiftID'];
                    }
                }
            }

            $invalidShifts = array_unique($invalidShifts);

            foreach ($shifts as $shift) {
                if (!in_array($shift['ShiftID'], $invalidShifts)) {
                    $hours = calculateHours($shift['StartTime'], $shift['EndTime']);
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

try {
    $filename = '../app/dataset.json';
    $shifts = readJsonFile($filename);
    $employeeShifts = processShifts($shifts);
    $results = calculateEmployeeHours($employeeShifts);

    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
