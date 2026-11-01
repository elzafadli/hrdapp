<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        if ($request->ajax()) {
            $query = Holiday::where('year', $year)->orderBy('holiday_date', 'asc');

            if ($request->filled('type')) {
                if ($request->type === 'cuti_bersama') {
                    $query->where('is_cuti_bersama', true);
                } elseif ($request->type === 'libur_nasional') {
                    $query->where('is_cuti_bersama', false);
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('formatted_date', function ($row) {
                    $date = Carbon::parse($row->holiday_date);
                    return '<div class="font-semibold text-gray-900">' . $date->translatedFormat('l, d F Y') . '</div>'
                        . '<div class="text-[11px] text-gray-400 font-mono">' . $date->format('Y-m-d') . '</div>';
                })
                ->addColumn('badge_type', function ($row) {
                    if ($row->is_cuti_bersama) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <i class="fa-solid fa-umbrella-beach text-[10px]"></i> Cuti Bersama
                        </span>';
                    }
                    return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                        <i class="fa-solid fa-calendar-xmark text-[10px]"></i> Libur Nasional
                    </span>';
                })
                ->addColumn('action', function ($row) {
                    $csrf = csrf_field();
                    $methodDelete = method_field('DELETE');

                    $editBtn = '<button type="button" onclick="editHoliday(' . htmlspecialchars(json_encode($row)) . ')"
                        class="inline-flex items-center px-2.5 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-semibold transition mr-1.5"
                        title="Edit Hari Libur">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>';

                    $deleteBtn = '<form action="' . route('holidays.destroy', $row->id) . '" method="POST" class="inline-block" onsubmit="return confirm(\'Hapus hari libur: ' . addslashes($row->name) . '?\');">
                        ' . $csrf . '
                        ' . $methodDelete . '
                        <button type="submit"
                            class="inline-flex items-center px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition"
                            title="Hapus Hari Libur">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </form>';

                    return '<div class="flex items-center">' . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['formatted_date', 'badge_type', 'action'])
                ->make(true);
        }

        // Summary Statistics for the selected year
        $totalHolidays = Holiday::where('year', $year)->count();
        $nationalHolidays = Holiday::where('year', $year)->where('is_cuti_bersama', false)->count();
        $cutiBersama = Holiday::where('year', $year)->where('is_cuti_bersama', true)->count();

        // Available years in database
        $years = Holiday::select('year')->distinct()->orderBy('year', 'desc')->pluck('year')->toArray();
        if (!in_array((int) date('Y'), $years)) {
            $years[] = (int) date('Y');
        }
        if (!in_array((int) date('Y') + 1, $years)) {
            $years[] = (int) date('Y') + 1;
        }
        rsort($years);

        return view('holidays.index', compact('year', 'years', 'totalHolidays', 'nationalHolidays', 'cutiBersama'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_date'    => 'required|date|unique:holidays,holiday_date',
            'name'            => 'required|string|max:255',
            'is_cuti_bersama' => 'nullable|boolean',
            'description'     => 'nullable|string',
        ]);

        $date = Carbon::parse($validated['holiday_date']);
        $validated['year'] = (int) $date->format('Y');
        $validated['is_cuti_bersama'] = $request->boolean('is_cuti_bersama');

        Holiday::create($validated);

        return redirect()->route('holidays.index', ['year' => $validated['year']])
            ->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'holiday_date'    => 'required|date|unique:holidays,holiday_date,' . $holiday->id,
            'name'            => 'required|string|max:255',
            'is_cuti_bersama' => 'nullable|boolean',
            'description'     => 'nullable|string',
        ]);

        $date = Carbon::parse($validated['holiday_date']);
        $validated['year'] = (int) $date->format('Y');
        $validated['is_cuti_bersama'] = $request->boolean('is_cuti_bersama');

        $holiday->update($validated);

        return redirect()->route('holidays.index', ['year' => $validated['year']])
            ->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday)
    {
        $year = $holiday->year;
        $holiday->delete();

        return redirect()->route('holidays.index', ['year' => $year])
            ->with('success', 'Hari libur berhasil dihapus.');
    }

    /**
     * Auto-generate daftar hari libur nasional resmi & cuti bersama standar SKB 3 Menteri.
     */
    public function autoGenerate(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        // Template hari libur nasional standar Indonesia per tahun
        $holidays = $this->getDefaultIndonesianHolidays($year);

        $insertedCount = 0;
        foreach ($holidays as $item) {
            $exists = Holiday::where('holiday_date', $item['holiday_date'])->exists();
            if (!$exists) {
                Holiday::create([
                    'holiday_date'    => $item['holiday_date'],
                    'name'            => $item['name'],
                    'year'            => $year,
                    'is_cuti_bersama' => $item['is_cuti_bersama'],
                    'description'     => $item['description'] ?? 'Kalender Resmi SKB 3 Menteri',
                ]);
                $insertedCount++;
            }
        }

        return redirect()->route('holidays.index', ['year' => $year])
            ->with('success', "Berhasil generate {$insertedCount} hari libur nasional & cuti bersama untuk tahun {$year}.");
    }

    private function getDefaultIndonesianHolidays(int $year): array
    {
        // Kalender hari libur Indonesia master resmi SKB 3 Menteri 2026
        if ($year === 2026) {
            return [
                ['holiday_date' => '2026-01-01', 'name' => 'Tahun Baru 2026 Masehi', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-01-16', 'name' => 'Isra Mi\'raj Nabi Muhammad SAW', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-02-17', 'name' => 'Tahun Baru Imlek 2577 Kongzili', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-02-18', 'name' => 'Cuti Bersama Tahun Baru Imlek', 'is_cuti_bersama' => true],
                ['holiday_date' => '2026-03-20', 'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-03-21', 'name' => 'Hari Raya Idul Fitri 1447 H (Hari ke-1)', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-03-22', 'name' => 'Hari Raya Idul Fitri 1447 H (Hari ke-2)', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-03-23', 'name' => 'Cuti Bersama Idul Fitri 1447 H', 'is_cuti_bersama' => true],
                ['holiday_date' => '2026-03-24', 'name' => 'Cuti Bersama Idul Fitri 1447 H', 'is_cuti_bersama' => true],
                ['holiday_date' => '2026-04-03', 'name' => 'Wafat Yesus Kristus (Jumat Agung)', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-05-01', 'name' => 'Hari Buruh Internasional', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-05-14', 'name' => 'Kenaikan Yesus Kristus', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-05-28', 'name' => 'Hari Raya Idul Adha 1447 H', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-05-31', 'name' => 'Hari Raya Waisak 2570 BE', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-06-16', 'name' => 'Tahun Baru Islam 1448 H', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-08-17', 'name' => 'Hari Kemerdekaan Republik Indonesia ke-81', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-08-25', 'name' => 'Maulid Nabi Muhammad SAW', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-12-25', 'name' => 'Hari Raya Natal', 'is_cuti_bersama' => false],
                ['holiday_date' => '2026-12-26', 'name' => 'Cuti Bersama Hari Raya Natal', 'is_cuti_bersama' => true],
            ];
        }

        // Untuk tahun lainnya (misal 2027, 2028 dst), coba sinkronisasi otomatis dari API kalender resmi
        try {
            $response = Http::timeout(3)->get("https://date.nager.at/api/v3/publicholidays/{$year}/ID");
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && count($data) > 0) {
                    $holidays = [];
                    foreach ($data as $item) {
                        $holidays[] = [
                            'holiday_date'    => $item['date'],
                            'name'            => $item['localName'] ?: $item['name'],
                            'is_cuti_bersama' => false,
                            'description'     => 'Kalender Nasional Resmi ' . $year,
                        ];
                    }
                    return $holidays;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal fetch holiday API untuk tahun {$year}: " . $e->getMessage());
        }

        // Generic calendar fallback jika offline atau API tidak tersedia
        return [
            ['holiday_date' => "{$year}-01-01", 'name' => "Tahun Baru {$year} Masehi", 'is_cuti_bersama' => false],
            ['holiday_date' => "{$year}-05-01", 'name' => 'Hari Buruh Internasional', 'is_cuti_bersama' => false],
            ['holiday_date' => "{$year}-06-01", 'name' => 'Hari Lahir Pancasila', 'is_cuti_bersama' => false],
            ['holiday_date' => "{$year}-08-17", 'name' => 'Hari Kemerdekaan Republik Indonesia', 'is_cuti_bersama' => false],
            ['holiday_date' => "{$year}-12-25", 'name' => 'Hari Raya Natal', 'is_cuti_bersama' => false],
            ['holiday_date' => "{$year}-12-26", 'name' => 'Cuti Bersama Hari Raya Natal', 'is_cuti_bersama' => true],
        ];
    }
}

