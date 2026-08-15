<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(): View
    {
        $equipment = Equipment::orderBy('created_at', 'desc')->get();
        return view('equipment.index', compact('equipment'));
    }
}
