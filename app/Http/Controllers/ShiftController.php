<?php

namespace App\Http\Controllers;

use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    protected $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function processShifts()
    {
        try {
            $results = $this->shiftService->processShifts();
            return response()->json(['result' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
