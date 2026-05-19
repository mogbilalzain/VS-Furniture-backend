<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Settings",
 *     description="Global site settings endpoints"
 * )
 */
class SettingsController extends Controller
{
    /**
     * Public endpoint that exposes only the maintenance flag, so any visitor
     * (without an auth token) can know whether to render the maintenance page.
     *
     * @OA\Get(
     *     path="/settings/maintenance",
     *     summary="Get maintenance mode status",
     *     tags={"Settings"},
     *     @OA\Response(response=200, description="Maintenance status retrieved")
     * )
     */
    public function maintenance()
    {
        $enabled = (bool) Setting::get('maintenance_mode', false);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
            ],
        ]);
    }

    /**
     * Admin-only listing of every key/value pair currently stored.
     *
     * @OA\Get(
     *     path="/admin/settings",
     *     summary="Get all settings (admin)",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Settings retrieved")
     * )
     */
    public function index()
    {
        $rows = Setting::orderBy('key')->get();

        $data = [];
        foreach ($rows as $row) {
            $data[$row->key] = Setting::get($row->key);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Toggle the maintenance flag.
     *
     * @OA\Put(
     *     path="/admin/settings/maintenance",
     *     summary="Update maintenance mode (admin)",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"enabled"},
     *             @OA\Property(property="enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Maintenance mode updated")
     * )
     */
    public function updateMaintenance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $enabled = (bool) $request->boolean('enabled');

        Setting::set('maintenance_mode', $enabled, 'boolean');

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Maintenance mode enabled' : 'Maintenance mode disabled',
            'data' => [
                'enabled' => $enabled,
            ],
        ]);
    }
}
