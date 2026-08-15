<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Setting::all(['id', 'key', 'value']));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            '*' => 'required|array',
            '*.key' => 'required|string',
            '*.value' => 'required|string',
        ]);

        foreach ($validated as $item) {
            Setting::updateOrCreate(['key' => $item['key']], ['value' => $item['value']]);
        }

        return response()->json(['message' => 'Настройки сохранены']);
    }

    public function exportDb(): \Illuminate\Http\Response
    {
        $source = database_path('database.sqlite');
        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        $dest = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        copy($source, $dest);

        return response()->download($dest)->deleteFileAfterSend(false);
    }
}
