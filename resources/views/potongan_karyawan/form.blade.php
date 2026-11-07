<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('potongan-karyawan.index') }}"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                            title="Kembali ke Daftar Potongan">
                            <i class="fa-solid fa-arrow-left text-sm"></i>
                        </a>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ isset($header) ? 'Kelola Potongan Karyawan - ' . \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->translatedFormat('F Y') : __('Buat Periode Potongan Baru') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Form penginputan potongan bulanan karyawan dan perhitungan denda keterlambatan presensi.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fa-solid fa-calendar-check text-blue-500"></i>
                            Periode: {{ isset($header) ? \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->translatedFormat('F Y') : date('F Y') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card Kalkulasi Otomatis Keterlambatan Presensi -->
            <div class="bg-gradient-to-br from-amber-50/80 via-white to-orange-50/60 border border-amber-200/90 rounded-xl p-5 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500 text-white shadow-xs">
                                <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Perhitungan Otomatis Keterlambatan Presensi</h3>
                                <p class="text-xs text-slate-600">
                                    Hitung akumulasi denda keterlambatan (presensi masuk &gt; 08:30 WIB) secara otomatis berdasarkan jadwal cut-off.
                                </p>
                            </div>
                        </div>

                        <!-- Info Pill Badges -->
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-amber-200 text-xs font-medium text-slate-700 shadow-2xs">
                                <i class="fa-regular fa-calendar text-amber-600"></i>
                                Cut-off: <strong id="cutoff-label">{{ $cutoffPeriod['label'] ?? 'Tgl 25 s/d 25' }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-amber-200 text-xs font-medium text-slate-700 shadow-2xs">
                                <i class="fa-solid fa-coins text-amber-600"></i>
                                Tarif Denda: <strong id="rate-label">Rp {{ number_format($lateRate ?? 15000, 0, ',', '.') }} / keterlambatan</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-amber-200 text-xs font-medium text-slate-700 shadow-2xs">
                                <i class="fa-solid fa-clock text-amber-600"></i>
                                Batas Masuk: <strong class="text-red-600">&gt; 08:30 WIB (Terlambat)</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Aksi Hitung -->
                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <button type="button" id="btn-calc-late"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-bolt text-sm"></i>
                            <span id="btn-calc-late-text">Hitung Potongan Keterlambatan</span>
                        </button>

                        @isset($header)
                            <button type="button" id="btn-sync-save-late"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Hitung dan langsung simpan ke database">
                                <i class="fa-solid fa-floppy-disk text-sm"></i>
                                <span id="btn-sync-save-text">Hitung &amp; Simpan</span>
                            </button>
                        @endisset
                    </div>
                </div>

                <!-- Live Result Alert Box (Muncul setelah kalkulasi berhasil) -->
                <div id="calc-result-box" class="hidden mt-4 pt-4 border-t border-amber-200/80 animate-fade-in">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white/90 p-3.5 rounded-lg border border-amber-200 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-600">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <div>
                                <span class="font-bold text-slate-800 block" id="calc-result-headline">Kalkulasi Selesai</span>
                                <span class="text-slate-600" id="calc-result-summary">Nilai potongan keterlambatan telah otomatis diisikan ke kolom tabel.</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btn-open-detail-modal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-md transition">
                                <i class="fa-solid fa-list-check text-slate-500"></i>
                                <span>Lihat Rincian Presensi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Utama Pengajuan/Penyimpanan Potongan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <form id="potongan-form" method="POST" action="{{ isset($header) ? route('potongan-karyawan.update', $header->id) : route('potongan-karyawan.store') }}">
                    @csrf
                    @isset($header) @method('PUT') @endisset

                    <div class="p-6 text-gray-900 space-y-6">
                        <!-- Periode Input (Tahun & Bulan) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div>
                                <x-input-label for="year" :value="__('Tahun')" />
                                <x-text-input id="year" name="year" type="number" class="block w-full mt-1"
                                    value="{{ $header->year ?? date('Y') }}" min="2000" max="2100" required autofocus />
                                <x-input-error class="mt-1" :messages="$errors->get('year')" />
                            </div>

                            <div>
                                <x-input-label for="month" :value="__('Bulan')" />
                                @php
                                    $monthOptions = [];
                                    for ($i = 1; $i <= 12; $i++) {
                                        $monthOptions[$i] = \Carbon\Carbon::createFromDate(2024, $i, 1)->translatedFormat('F');
                                    }
                                    $selectedMonth = isset($header) ? $header->month : date('n');
                                @endphp
                                <x-select-input id="month" name="month" :options="$monthOptions" :selected="$selectedMonth" :noSelect2="true" class="mt-1 block w-full" required />
                                <x-input-error class="mt-1" :messages="$errors->get('month')" />
                            </div>
                        </div>

                        <!-- Tabel Input Potongan Per Karyawan -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                                <table class="w-full text-sm text-left text-gray-500 divide-y divide-slate-200">
                                    <thead class="text-xs text-slate-700 uppercase bg-slate-100 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 min-w-[200px]">Karyawan</th>
                                            @foreach($components as $payrollComponent)
                                                @php
                                                    $isLateComponent = (stripos($payrollComponent->name, 'keterlambatan') !== false) || ($payrollComponent->id == 14);
                                                @endphp
                                                <th class="px-4 py-3 min-w-[170px] {{ $isLateComponent ? 'bg-amber-100/70 border-x border-amber-200 text-amber-900' : '' }}">
                                                    <div class="flex items-center gap-1.5">
                                                        <span>{{ $payrollComponent->name }}</span>
                                                        @if($isLateComponent)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white" title="Dihitung otomatis via Presensi">
                                                                Otomatis
                                                            </span>
                                                        @endif
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($employees as $employee)
                                            <tr class="hover:bg-slate-50 transition employee-row" data-emp-id="{{ $employee->id }}">
                                                <td class="px-4 py-3">
                                                    <div class="font-semibold text-slate-900">{{ $employee->name }}</div>
                                                    <div class="text-xs text-slate-500">{{ $employee->jabatan->jabatan ?? '-' }}</div>
                                                </td>
                                                @foreach($components as $payrollComponent)
                                                    @php
                                                        $deduction = $existingDeductions->firstWhere(function($item) use ($employee, $payrollComponent) {
                                                            return $item['employee_id'] == $employee->id && $item['component_id'] == $payrollComponent->id;
                                                        });
                                                        $deductionValue = $deduction ? $deduction['value'] : '';
                                                        $isLateCol = (stripos($payrollComponent->name, 'keterlambatan') !== false) || ($payrollComponent->id == 14);
                                                    @endphp
                                                    <td class="px-4 py-2.5 {{ $isLateCol ? 'bg-amber-50/40 border-x border-amber-100' : '' }}">
                                                        <div class="relative">
                                                            <input type="text"
                                                                id="deduction_{{ $employee->id }}_{{ $payrollComponent->id }}"
                                                                name="deductions[{{ $employee->id }}_{{ $payrollComponent->id }}]"
                                                                data-emp-id="{{ $employee->id }}"
                                                                data-comp-id="{{ $payrollComponent->id }}"
                                                                data-is-late="{{ $isLateCol ? '1' : '0' }}"
                                                                class="deduction-input block w-full currency-mask text-right border-slate-300 rounded-lg shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 text-sm py-1.5 {{ $isLateCol ? 'font-medium text-slate-800 border-amber-300 focus:ring-amber-500 focus:border-amber-500' : '' }}"
                                                                value="{{ $deductionValue }}"
                                                                placeholder="0.00"
                                                            />
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Tombol Simpan -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-xs text-slate-500">
                            <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                            Pastikan data potongan sudah sesuai sebelum menyimpan.
                        </div>

                        <div class="flex items-center gap-3">
                            <x-secondary-link href="{{ route('potongan-karyawan.index') }}">
                                {{ __('Batal') }}
                            </x-secondary-link>

                            <x-primary-button id="btn-submit-form" class="inline-flex items-center gap-2">
                                <i class="fa-solid fa-check text-xs"></i>
                                <span id="btn-submit-text">{{ isset($header) ? __('Simpan Potongan') : __('Buat Periode') }}</span>
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Rincian Presensi Terlambat -->
    <div id="modal-late-detail" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div id="modal-backdrop" class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-xs"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500 text-white">
                            <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-slate-800 text-base" id="modal-title">Rincian Presensi Keterlambatan</h3>
                            <p class="text-xs text-slate-500" id="modal-period-text">Periode Cut-Off</p>
                        </div>
                    </div>
                    <button type="button" id="btn-close-modal" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <div id="modal-content-list" class="space-y-4">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 text-right">
                    <button type="button" id="btn-close-modal-bottom" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Inisialisasi Inputmask Rupiah Currency
                $('.currency-mask').inputmask({
                    alias: 'numeric',
                    groupSeparator: ",",
                    digits: 2,
                    digitsOptional: false,
                    placeholder: "0.00",
                    rightAlign: true,
                    autoGroup: true,
                    prefix: "",
                    allowMinus: false,
                    removeMaskOnSubmit: true
                });

                // Format existing values
                $('.currency-mask').each(function() {
                    const value = $(this).val();
                    if (value !== '' && !value.includes('.')) {
                        const numValue = parseFloat(value);
                        if (!isNaN(numValue)) {
                            $(this).val(numValue.toFixed(2));
                        }
                    }
                });

                // State Variable
                let lastCalculationData = null;
                const lateComponentId = {{ $lateComponent->id ?? 14 }};
                const calcLateUrl = "{{ route('potongan-karyawan.calculate-late') }}";
                @isset($header)
                    const syncLateUrl = "{{ route('potongan-karyawan.sync-late', $header->id) }}";
                @endisset

                // Helper: Update label periode cut-off saat bulan/tahun diubah
                function updateCutoffLabel() {
                    const year = $('#year').val();
                    const month = $('#month').val();
                    if (year && month) {
                        const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
                        const m = parseInt(month, 10);
                        const prevM = m === 1 ? 12 : m - 1;
                        const prevY = m === 1 ? parseInt(year, 10) - 1 : year;
                        $('#cutoff-label').text(`25 ${monthNames[prevM - 1]} ${prevY} s/d 25 ${monthNames[m - 1]} ${year}`);
                    }
                }

                $('#year, #month').on('change', updateCutoffLabel);

                // Handler: Klik Tombol "Hitung Potongan Keterlambatan"
                $('#btn-calc-late').on('click', function () {
                    const year = $('#year').val();
                    const month = $('#month').val();

                    if (!year || !month) {
                        alert('Harap pilih Tahun dan Bulan terlebih dahulu.');
                        return;
                    }

                    const $btn = $(this);
                    const originalText = $('#btn-calc-late-text').text();
                    $btn.prop('disabled', true);
                    $('#btn-calc-late-text').html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menghitung Presensi...');

                    $.ajax({
                        url: calcLateUrl,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            year: year,
                            month: month
                        },
                        success: function (res) {
                            $btn.prop('disabled', false);
                            $('#btn-calc-late-text').text(originalText);

                            if (res.success) {
                                lastCalculationData = res;

                                // Tampilkan alert box ringkasan
                                $('#calc-result-box').removeClass('hidden');
                                $('#calc-result-headline').text(
                                    `Hasil Perhitungan: ${res.summary.total_late_events}x Keterlambatan pada ${res.summary.total_late_employees} Karyawan`
                                );
                                $('#calc-result-summary').text(
                                    `Total denda: Rp ${res.summary.formatted_total} (Periode ${res.period.label}). Kolom 'Keterlambatan' telah otomatis terisi.`
                                );

                                // Isi input tabel sesuai hasil denda per karyawan
                                let updatedInputs = 0;
                                $.each(res.deductions, function (empId, data) {
                                    const inputSelector = `input[data-emp-id="${empId}"][data-comp-id="${res.component_id}"]`;
                                    const $input = $(inputSelector);
                                    if ($input.length) {
                                        $input.val(data.formatted_amount);
                                        $input.trigger('input');

                                        // Beri efek highlight visual
                                        if (data.late_count > 0) {
                                            $input.addClass('bg-amber-100 font-bold text-amber-900 ring-2 ring-amber-400');
                                            setTimeout(() => {
                                                $input.removeClass('ring-2 ring-amber-400');
                                            }, 2500);
                                            updatedInputs++;
                                        }
                                    }
                                });

                                // Notifikasi toast/alert
                                if (res.summary.total_late_events > 0) {
                                    alert(`Perhitungan selesai!\nDitemukan ${res.summary.total_late_events} kali keterlambatan (> 08:30 WIB).\nTotal Potongan: Rp ${res.summary.formatted_total}\n\nNilai denda telah dimasukkan ke dalam form. Silakan klik tombol Simpan Potongan untuk menyimpan ke database.`);
                                } else {
                                    alert(`Tidak ditemukan presensi terlambat (> 08:30 WIB) pada periode ${res.period.label}. Seluruh potongan keterlambatan diisi 0.00.`);
                                }
                            }
                        },
                        error: function (xhr) {
                            $btn.prop('disabled', false);
                            $('#btn-calc-late-text').text(originalText);
                            alert('Terjadi kesalahan saat menghitung keterlambatan. Silakan coba kembali.');
                        }
                    });
                });

                // Handler: Klik Tombol "Hitung & Simpan" (Langsung Sync ke Database)
                @isset($header)
                $('#btn-sync-save-late').on('click', function () {
                    if (!confirm('Hitung denda keterlambatan presensi dan langsung simpan ke database untuk periode ini?')) {
                        return;
                    }

                    const $btn = $(this);
                    const originalText = $('#btn-sync-save-text').text();
                    $btn.prop('disabled', true);
                    $('#btn-sync-save-text').html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...');

                    $.ajax({
                        url: syncLateUrl,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (res) {
                            $btn.prop('disabled', false);
                            $('#btn-sync-save-text').text(originalText);

                            if (res.success) {
                                alert(res.message + `\nTotal: ${res.data.summary.total_late_events}x keterlambatan (Rp ${res.data.summary.formatted_total}).`);
                                location.reload();
                            }
                        },
                        error: function () {
                            $btn.prop('disabled', false);
                            $('#btn-sync-save-text').text(originalText);
                            alert('Gagal menyinkronkan data keterlambatan.');
                        }
                    });
                });
                @endisset

                // Handler: Buka Modal Detail Presensi Terlambat
                $('#btn-open-detail-modal').on('click', function () {
                    if (!lastCalculationData) return;

                    const res = lastCalculationData;
                    $('#modal-period-text').text(`Periode: ${res.period.label} | Denda: Rp ${res.rate_formatted} / keterlambatan`);

                    const $list = $('#modal-content-list');
                    $list.empty();

                    let hasLate = false;
                    $.each(res.deductions, function (empId, item) {
                        if (item.late_count > 0) {
                            hasLate = true;
                            let recordsHtml = '';
                            item.records.forEach((rec, idx) => {
                                recordsHtml += `
                                    <tr class="border-b border-slate-100 text-xs">
                                        <td class="py-1.5 px-3 font-mono font-medium">${rec.date_label}</td>
                                        <td class="py-1.5 px-3 text-red-600 font-bold">${rec.check_in_time} WIB</td>
                                        <td class="py-1.5 px-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-100 text-red-700">Terlambat (&gt; 08:30)</span>
                                        </td>
                                        <td class="py-1.5 px-3 text-right font-semibold text-slate-800">Rp ${res.rate_formatted}</td>
                                    </tr>
                                `;
                            });

                            $list.append(`
                                <div class="border border-slate-200 rounded-xl overflow-hidden">
                                    <div class="bg-slate-50 px-4 py-2.5 flex items-center justify-between border-b border-slate-200">
                                        <div>
                                            <span class="font-bold text-slate-800 text-sm">${item.name}</span>
                                            <span class="text-xs text-slate-500 ml-2">${item.jabatan}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">${item.late_count}x Terlambat</span>
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Total: Rp ${parseFloat(item.deduction_amount).toLocaleString('id-ID')}</span>
                                        </div>
                                    </div>
                                    <table class="w-full">
                                        <thead class="bg-slate-100/60 text-[11px] uppercase text-slate-600">
                                            <tr>
                                                <th class="py-1.5 px-3 text-left">Tanggal</th>
                                                <th class="py-1.5 px-3 text-left">Jam Masuk</th>
                                                <th class="py-1.5 px-3 text-left">Status</th>
                                                <th class="py-1.5 px-3 text-right">Potongan Denda</th>
                                            </tr>
                                        </thead>
                                        <tbody>${recordsHtml}</tbody>
                                    </table>
                                </div>
                            `);
                        }
                    });

                    if (!hasLate) {
                        $list.html('<div class="p-4 text-center text-slate-500 text-sm">Tidak ada karyawan yang terlambat pada periode ini.</div>');
                    }

                    $('#modal-late-detail').removeClass('hidden');
                });

                // Tutup Modal
                $('#btn-close-modal, #btn-close-modal-bottom, #modal-backdrop').on('click', function () {
                    $('#modal-late-detail').addClass('hidden');
                });

                // Proteksi Anti-Double-Submit pada Form Utama
                let formSubmitting = false;
                $('#potongan-form').on('submit', function (e) {
                    if (formSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    formSubmitting = true;
                    $('#btn-submit-form').prop('disabled', true);
                    $('#btn-submit-text').html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan Potongan...');
                });
            });
        </script>
    @endpush
</x-app-layout>
