<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use Illuminate\Http\Request;

class AttendanceSettingController extends Controller
{
    public function index()
    {
        $setting = AttendanceSetting::getActiveSetting();

        return view('attendance_settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'office_name'          => 'required|string|max:255',
            'latitude'             => 'required|numeric|between:-90,90',
            'longitude'            => 'required|numeric|between:-180,180',
            'radius_meters'        => 'required|integer|min:10|max:50000',
            'allow_outside_radius' => 'nullable|boolean',
        ]);

        $validated['allow_outside_radius'] = $request->boolean('allow_outside_radius');

        $setting = AttendanceSetting::first();
        if ($setting) {
            $setting->update($validated);
        } else {
            AttendanceSetting::create($validated);
        }

        return redirect()->route('attendance-settings.index')
            ->with('success', 'Pengaturan lokasi & radius presensi berhasil disimpan.');
    }
}

