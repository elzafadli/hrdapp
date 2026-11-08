<x-app-layout>
    <div class="py-6" x-data="{ showModal: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-2xs">
                            <i class="fa-solid fa-calculator text-lg"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ __('Hasil & Proses Payroll (Payroll Results)') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kelola daftar periode payroll, cut-off presensi, dan histori penggajian karyawan.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('potongan-karyawan.index') }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-2xs transition">
                            <i class="fa-solid fa-scissors text-xs text-slate-500"></i>
                            <span>Kelola Potongan Karyawan</span>
                        </a>

                        <button type="button" @click="showModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Proses Periode Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Session Alerts -->
            @if ($message = Session::get('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2.5 shadow-2xs" role="alert">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>{{ $message }}</span>
                </div>
            @endif

            @if ($warning = Session::get('warning'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2.5 shadow-2xs" role="alert">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base shrink-0"></i>
                    <span>{{ $warning }}</span>
                </div>
            @endif

            @if ($error = Session::get('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2.5 shadow-2xs" role="alert">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                    <span>{{ $error }}</span>
                </div>
            @endif

            <!-- Banner Ketentuan Cut-Off Penggajian (Tgl 25) -->
            <div class="bg-gradient-to-r from-amber-50 via-white to-indigo-50/50 border border-amber-200 rounded-xl p-4 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500 text-white shrink-0 mt-0.5">
                            <i class="fa-solid fa-calendar-days text-sm"></i>
                        </span>
                        <div>
                            <h4 class="font-bold text-xs sm:text-sm text-slate-800">
                                Jadwal Penggajian &amp; Cut-Off Presensi: <span class="text-indigo-600">Tanggal 25</span>
                            </h4>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Klik tombol <strong>Detail</strong> pada baris periode untuk melihat rincian gaji karyawan, cetak slip gaji, dan komparasi BPJS.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">
                            <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Cut-off: <strong>Tgl 25 s/d 25</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">
                            <i class="fa-solid fa-coins text-amber-500"></i> Denda: <strong>Rp 15.000 / event</strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card Utama Data Table: Daftar Periode Payroll -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 text-sm">
                            <i class="fa-solid fa-list-check"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base leading-snug">Daftar Periode Payroll Tersimpan</h3>
                            <p class="text-xs text-slate-500">Histori seluruh periode penggajian yang tersimpan dalam sistem</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            Total: <strong>{{ $periods->count() }} Periode</strong>
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="payroll-periods-table" class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                            <thead class="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-4 py-3 text-center w-12">No</th>
                                    <th class="px-4 py-3 text-left">Periode Gaji</th>
                                    <th class="px-4 py-3 text-left">Rentang Cut-Off</th>
                                    <th class="px-4 py-3 text-center">Karyawan</th>
                                    <th class="px-4 py-3 text-right">Total Payroll (THP)</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Terakhir Diproses</th>
                                    <th class="px-4 py-3 text-center w-40">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($periods as $index => $p)
                                    @php
                                        $orderKey = sprintf('%04d%02d', (int)$p->year, (int)$p->month);
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-4 py-3 text-center text-slate-500 font-semibold">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap" data-order="{{ $orderKey }}" data-sort="{{ $orderKey }}">
                                            <div class="font-bold text-slate-900 text-sm">
                                                {{ $p->period_label }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                                Tahun {{ $p->year }} • Periode {{ $p->month }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap" data-order="{{ $p->start_date }}">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                <i class="fa-regular fa-calendar text-amber-600"></i>
                                                {{ $p->cutoff_range }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap" data-order="{{ $p->total_employees }}">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                <i class="fa-solid fa-users text-blue-500"></i>
                                                {{ $p->total_employees }} Orang
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap font-bold text-indigo-700 font-mono" data-order="{{ $p->total_thp }}">
                                            Rp {{ number_format($p->total_thp, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            @if(($p->status ?? 'draft') === 'final')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800" title="Karyawan dapat melihat slip di ESS">
                                                    <i class="fa-solid fa-circle-check text-[10px]"></i> Final
                                                </span>
                                            @else
                                                <form action="{{ route('payroll-results.update-status') }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="month" value="{{ $p->month }}">
                                                    <input type="hidden" name="year" value="{{ $p->year }}">
                                                    <input type="hidden" name="status" value="final">
                                                    <button type="submit" onclick="return confirm('Finalisasi payroll periode {{ $p->period_label }}?\n\n- Status akan diubah ke FINAL (dapat diakses karyawan di ESS).\n- Saldo pinjaman karyawan akan dipotong otomatis.');"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 hover:bg-emerald-100 text-amber-800 hover:text-emerald-800 border border-amber-300 hover:border-emerald-300 transition cursor-pointer shadow-2xs"
                                                        title="Status masih Draft. Klik untuk Finalisasi & Potong Saldo Pinjaman">
                                                        <i class="fa-solid fa-clock text-[10px]"></i> Draft &bull; Finalkan?
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-slate-500 font-mono" data-order="{{ $p->last_processed_at ?? '' }}">
                                            {{ !empty($p->last_processed_at) ? \Carbon\Carbon::parse($p->last_processed_at)->translatedFormat('d M Y H:i') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Tombol Buka Detail di Tab Baru -->
                                                <a href="{{ route('payroll-results.show', ['month' => $p->month, 'year' => $p->year]) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer"
                                                    title="Buka Detail Payroll di Tab Baru">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                    <span>Detail</span>
                                                </a>

                                                <a href="{{ route('payroll-results.export', ['month' => $p->month, 'year' => $p->year]) }}"
                                                    class="inline-flex items-center justify-center w-7 h-7 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition"
                                                    title="Export Excel Periode Ini">
                                                    <i class="fa-solid fa-file-excel"></i>
                                                </a>

                                                <form action="{{ route('payroll-results.destroy-period') }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh data hasil payroll periode {{ $p->period_label }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="month" value="{{ $p->month }}">
                                                    <input type="hidden" name="year" value="{{ $p->year }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center w-7 h-7 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                                                        title="Hapus Seluruh Data Periode Ini">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <i class="fa-regular fa-folder-open text-3xl text-slate-300"></i>
                                                <p class="font-medium text-slate-600">Belum ada periode payroll yang tersimpan.</p>
                                                <p class="text-xs text-slate-400">Tekan tombol "Proses Periode Baru" di atas untuk memulai perhitungan payroll.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- MODAL: PROSES PERIODE BARU -->
            <!-- ========================================================================================= -->
            <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <!-- Backdrop -->
                <div x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="showModal = false"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl">

                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 text-sm">
                                    <i class="fa-solid fa-calculator"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-base" id="modal-title">Proses Periode Payroll Baru</h3>
                                    <p class="text-xs text-slate-500">Pilih periode cut-off penggajian untuk dihitung secara otomatis</p>
                                </div>
                            </div>
                            <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <form id="payroll-process-form" action="{{ route('payroll-results.process') }}" method="POST">
                            @csrf
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="process_month" :value="__('Pilih Bulan Penggajian')" class="font-semibold text-slate-700 text-xs mb-1" />
                                        <x-select-input name="month" id="process_month" :noSelect2="true" required class="w-full">
                                            @foreach(range(1, 12) as $m)
                                                <option value="{{ $m }}" {{ (int) $m === (int) $currentMonth ? 'selected' : '' }}>
                                                    {{ \DateTime::createFromFormat('!m', $m)->format('F') }} (Periode {{ $m }})
                                                </option>
                                            @endforeach
                                        </x-select-input>
                                    </div>

                                    <div>
                                        <x-input-label for="process_year" :value="__('Tahun Penggajian')" class="font-semibold text-slate-700 text-xs mb-1" />
                                        <x-text-input type="number" name="year" id="process_year" value="{{ $currentYear }}" required min="2020" max="2099" class="w-full" />
                                    </div>
                                </div>

                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-start gap-3">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox" name="is_thr" id="is_thr" value="1"
                                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer"
                                            onchange="updateThrMessage()">
                                    </div>
                                    <div class="text-xs">
                                        <label for="is_thr" class="font-bold text-slate-800 cursor-pointer">
                                            Proses THR (Tunjangan Hari Raya)
                                        </label>
                                        <p class="text-slate-500 mt-0.5">
                                            Centang opsi ini jika Anda hanya ingin memproses perhitungan tunjangan hari raya pada periode tersebut.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs sm:text-sm font-semibold rounded-lg transition cursor-pointer">
                                    Batal
                                </button>

                                <button type="submit" id="submitPayrollBtn"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                                    <i class="fa-solid fa-calculator text-xs"></i>
                                    <span id="submitPayrollBtnText">Jalankan Proses Payroll</span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            let isSubmittingPayroll = false;

            function updateThrMessage() {
                const isThr = document.getElementById('is_thr').checked;
                const submitBtnText = document.getElementById('submitPayrollBtnText');

                if (isThr) {
                    submitBtnText.textContent = 'Proses THR (Tunjangan Hari Raya)';
                } else {
                    submitBtnText.textContent = 'Jalankan Proses Payroll';
                }
            }

            // Anti double-submit form handler
            document.getElementById('payroll-process-form')?.addEventListener('submit', function (e) {
                if (isSubmittingPayroll) {
                    e.preventDefault();
                    return false;
                }

                const isThr = document.getElementById('is_thr').checked;
                const month = document.getElementById('process_month').value;
                const year = document.getElementById('process_year').value;

                const message = isThr
                    ? `Apakah Anda yakin ingin memproses THR untuk periode ${month}/${year}? Data THR periode ini akan diperbarui.`
                    : `Apakah Anda yakin ingin memproses payroll untuk periode ${month}/${year}? Perhitungan sebelumnya untuk periode ini akan digantikan.`;

                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }

                isSubmittingPayroll = true;
                const submitBtn = document.getElementById('submitPayrollBtn');
                const submitText = document.getElementById('submitPayrollBtnText');
                const origText = submitText.textContent;

                submitBtn.disabled = true;
                submitText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Sedang Memproses...';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                setTimeout(() => {
                    isSubmittingPayroll = false;
                    submitBtn.disabled = false;
                    submitText.textContent = origText;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                }, 15000);
            });

            // Inisialisasi DataTable untuk Daftar Periode Payroll
            $(document).ready(function () {
                if ($('#payroll-periods-table tbody tr td').length > 1) {
                    const periodTable = $('#payroll-periods-table').DataTable({
                        pageLength: 10,
                        order: [[1, 'desc']], // Mengurutkan berdasarkan kolom 1 (data-order: 202611 > 202610 ...)
                        columnDefs: [
                            { targets: 0, orderable: false, searchable: false }, // Kolom No tidak dapat di-sort
                            { targets: 1, type: 'num' }, // Pastikan sort kolom periode numerik berdasarkan data-order YYYYMM
                            { targets: 7, orderable: false, searchable: false } // Kolom Aksi tidak dapat di-sort
                        ],
                        language: {
                            search: "Cari Periode:",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ periode",
                            infoEmpty: "Tidak ada data periode",
                            zeroRecords: "Periode tidak ditemukan",
                            paginate: {
                                first: "Awal",
                                last: "Akhir",
                                next: "Lanjut",
                                previous: "Kembali"
                            }
                        }
                    });

                    // Penomoran dinamis untuk kolom No (kolom 0) saat sorting atau search
                    periodTable.on('order.dt search.dt', function () {
                        let i = 1;
                        periodTable.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                            this.data(i++);
                        });
                    }).draw();
                }
            });
        </script>
    @endpush
</x-app-layout>
