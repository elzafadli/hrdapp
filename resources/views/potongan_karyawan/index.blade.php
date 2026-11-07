<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-2xs">
                            <i class="fa-solid fa-receipt text-lg"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ __('Kelola Potongan Karyawan') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Daftar periode potongan bulanan karyawan, perhitungan akumulasi keterlambatan presensi, dan cicilan pinjaman aktif.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('potongan-karyawan.create') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Buat Periode Baru</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Banner Ketentuan Cut-Off Penggajian (Tgl 25) -->
            <div class="bg-gradient-to-r from-amber-50 via-white to-blue-50 border border-amber-200 rounded-xl p-4 shadow-2xs">
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
                                Periode perhitungan denda keterlambatan presensi dihitung otomatis mulai <strong>Start Date (25 bulan lalu)</strong> hingga <strong>End Date (25 bulan berjalan)</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">
                            <i class="fa-solid fa-coins text-amber-500"></i> Denda: <strong>Rp 15.000 / event</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">
                            <i class="fa-solid fa-clock text-red-500"></i> Toleransi: <strong>&gt; 08:30 WIB</strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card Utama Data Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="p-6 text-gray-900">
                    @if ($message = Session::get('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2 mb-4" role="alert">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @endif

                    <x-data-table id="potongan-karyawan-table" :headers="['No', 'Periode Gaji', 'Start Date', 'End Date', 'Rentang Cut-Off (25 s/d 25)', 'Aksi']" />
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#potongan-karyawan-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('potongan-karyawan.index') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center w-12' },
                        { data: 'period', name: 'period' },
                        { data: 'start_date', name: 'start_date', orderable: false, searchable: false },
                        { data: 'end_date', name: 'end_date', orderable: false, searchable: false },
                        { data: 'cutoff_range', name: 'cutoff_range', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center w-52' },
                    ],
                    order: [[1, 'desc']],
                });
            });
        </script>
    @endpush
</x-app-layout>
