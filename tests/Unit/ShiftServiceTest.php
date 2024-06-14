<?php

namespace Tests\Unit;

use App\Services\ShiftService;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\ShiftServiceException;

class ShiftServiceTest extends TestCase
{
    protected $shiftService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shiftService = new ShiftService();
    }


    public function testEmptyFile()
    {
        $filename = 'dataset.json';
        Storage::disk('local')->put($filename, json_encode([]));

        $result = $this->shiftService->processShifts();

        $this->assertIsArray($result);

        Storage::disk('local')->delete($filename);
    }

    public function testValidShifts()
    {
        $data = [
            [
                "ShiftID" => 1,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-22T08:00:00Z",
                "EndTime" => "2022-08-22T16:00:00Z"
            ],
            [
                "ShiftID" => 2,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-23T08:00:00Z",
                "EndTime" => "2022-08-23T16:00:00Z"
            ],
        ];

        $filename = 'dataset.json';
        Storage::disk('local')->put($filename, json_encode($data));

        $result = $this->shiftService->processShifts();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('EmployeeID', $result[0]);
        $this->assertArrayHasKey('StartOfWeek', $result[0]);
        $this->assertArrayHasKey('RegularHours', $result[0]);
        $this->assertArrayHasKey('OvertimeHours', $result[0]);
        $this->assertArrayHasKey('InvalidShifts', $result[0]);

        Storage::disk('local')->delete($filename);
    }

    public function testShiftsCrossingMidnight()
    {
        $data = [
            [
                "ShiftID" => 1,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-21T22:00:00Z",
                "EndTime" => "2022-08-22T02:00:00Z"
            ],
        ];

        $filename = 'dataset.json';
        Storage::disk('local')->put($filename, json_encode($data));

        $result = $this->shiftService->processShifts();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('EmployeeID', $result[0]);
        $this->assertArrayHasKey('StartOfWeek', $result[0]);
        $this->assertArrayHasKey('RegularHours', $result[0]);
        $this->assertArrayHasKey('OvertimeHours', $result[0]);
        $this->assertArrayHasKey('InvalidShifts', $result[0]);

        Storage::disk('local')->delete($filename);
    }

    public function testOverlappingShifts()
    {
        $data = [
            [
                "ShiftID" => 1,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-22T08:00:00Z",
                "EndTime" => "2022-08-22T16:00:00Z"
            ],
            [
                "ShiftID" => 2,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-22T10:00:00Z",
                "EndTime" => "2022-08-22T18:00:00Z"
            ],
        ];

        $filename = 'dataset.json';
        Storage::disk('local')->put($filename, json_encode($data));

        $result = $this->shiftService->processShifts();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('EmployeeID', $result[0]);
        $this->assertArrayHasKey('StartOfWeek', $result[0]);
        $this->assertArrayHasKey('RegularHours', $result[0]);
        $this->assertArrayHasKey('OvertimeHours', $result[0]);
        $this->assertArrayHasKey('InvalidShifts', $result[0]);
        $this->assertNotEmpty($result[0]['InvalidShifts']);

        Storage::disk('local')->delete($filename);
    }

    public function testWeekBoundaryHandling()
    {
        $data = [
            [
                "ShiftID" => 1,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-21T22:00:00Z",
                "EndTime" => "2022-08-22T02:00:00Z"
            ],
            [
                "ShiftID" => 2,
                "EmployeeID" => 123,
                "StartTime" => "2022-08-28T22:00:00Z",
                "EndTime" => "2022-08-29T02:00:00Z"
            ],
        ];

        $filename = 'dataset.json';
        Storage::disk('local')->put($filename, json_encode($data));

        $result = $this->shiftService->processShifts();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        Storage::disk('local')->delete($filename);
    }
}
