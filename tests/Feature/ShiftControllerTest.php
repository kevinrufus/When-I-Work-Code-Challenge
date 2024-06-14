<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class ShiftControllerTest extends TestCase
{
    public function testProcessShiftsEndpoint()
    {
        // Create a temporary JSON file for testing
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
            // Add more shifts as needed for testing
        ];

        $filename = 'dataset.json';
        Storage::disk('local')->put($filename, json_encode($data));

        // Make a request to the endpoint
        $response = $this->get('/api/process-shifts');

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result' => [
                '*' => [
                    'EmployeeID',
                    'StartOfWeek',
                    'RegularHours',
                    'OvertimeHours',
                    'InvalidShifts'
                ]
            ]
        ]);

        // Clean up
        Storage::disk('local')->delete($filename);
    }
}
