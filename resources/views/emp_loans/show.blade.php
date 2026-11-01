<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if ($message = Session::get('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2" role="alert">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                    <span class="font-medium text-sm">{{ $message }}</span>
                </div>
            @endif

            @if ($message = Session::get('warning'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2" role="alert">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg"></i>
                    <span class="font-medium text-sm">{{ $message }}</span>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-sm" role="alert">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-circle-xmark text-red-600"></i>
                        <span class="font-semibold text-sm">Terjadi Kesalahan:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Header & Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-4">
                    <a href="{{ route('emp-loans.index', ['emp_id' => $empLoan->emp_id]) }}"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
                        title="Kembali ke Daftar Pinjaman">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl font-bold text-gray-900">Detail Pinjaman Karyawan</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $empLoan->status === 'open' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-800 border border-gray-200' }}">
                                {{ ucfirst($empLoan->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            ID Pinjaman: #{{ $empLoan->id }} • Dibuat pada {{ $empLoan->created_at ? $empLoan->created_at->format('d M Y, H:i') : '-' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if(empty($empLoan->budget_id))
                        <form action="{{ route('emp-loans.post-to-budget', $empLoan->id) }}" method="POST" onsubmit="return confirm('Posting pinjaman ini ke modul Budgeting dengan status Draft?');">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold tracking-wide uppercase shadow-sm transition">
                                <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                Post ke Budgeting
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('emp-loans.edit', $empLoan->id) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-semibold tracking-wide uppercase shadow-sm transition">
                        <i class="fa-solid fa-pen"></i>
                        Edit
                    </a>

                    <a href="{{ route('emp-loans.index', ['emp_id' => $empLoan->emp_id]) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold tracking-wide uppercase transition">
                        Kembali
                    </a>
                </div>
            </div>

            <!-- Budget Integration Status Banner / Card -->
            @if(!empty($empLoan->budget_id))
                <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 border border-emerald-200 rounded-xl p-5 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i class="fa-solid fa-receipt text-lg"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-gray-900">Telah Diposting ke Modul Budgeting</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300">
                                        Draft
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">
                                    Dokumen anggaran otomatis tersimpan pada database FinControl Budgeting dengan status <b>Draft</b> untuk ditindaklanjuti oleh tim Keuangan / Finance.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-white/80 backdrop-blur-sm px-4 py-3 rounded-lg border border-emerald-200 flex-shrink-0">
                            <div>
                                <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Nomor Referensi Budget</span>
                                <span class="block font-mono font-bold text-sm text-emerald-800">{{ $empLoan->budget_reference_number }}</span>
                            </div>
                            <div class="h-8 w-px bg-emerald-200"></div>
                            <div>
                                <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Budget ID</span>
                                <span class="block font-mono font-bold text-sm text-gray-800">#{{ $empLoan->budget_id }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-gradient-to-r from-amber-50 via-yellow-50 to-orange-50 border border-amber-200 rounded-xl p-5 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-gray-900">Belum Diposting ke Budgeting</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700 border border-gray-300">
                                        Pending Action
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">
                                    Pinjaman ini belum dibuatkan pengajuan anggaran ke modul Budgeting FinControl. Klik tombol di samping untuk memposting pengajuan dengan status <b>Draft</b>.
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('emp-loans.post-to-budget', $empLoan->id) }}" method="POST" onsubmit="return confirm('Posting pinjaman ini ke Budgeting dengan status Draft?');" class="flex-shrink-0">
                            @csrf
                            <button type="submit"
                                class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold tracking-wide uppercase shadow-sm transition">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Post ke Budgeting (Draft)
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Main Content 2 Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left Column: Employee Info -->
                <div class="lg:col-span-5 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                        <i class="fa-solid fa-user text-blue-600"></i>
                        <h2 class="font-bold text-sm text-gray-900">Informasi Karyawan</h2>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 font-bold text-lg overflow-hidden flex-shrink-0">
                            @if($empLoan->employee && $empLoan->employee->photo)
                                <img src="{{ asset('storage/' . $empLoan->employee->photo) }}" alt="{{ $empLoan->employee->name }}" class="w-full h-full object-cover">
                            @else
                                <i class="fa-solid fa-user text-2xl"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">{{ $empLoan->employee ? $empLoan->employee->name : '-' }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">NIK: {{ $empLoan->employee->nik_karyawan ?? '-' }}</p>
                            <p class="text-xs font-medium text-blue-600 mt-0.5">{{ $empLoan->employee->jabatan->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 text-xs">
                        <div class="py-2.5 flex justify-between items-center">
                            <span class="text-gray-500">Alokasi Proyek Pinjaman</span>
                            <span class="font-semibold text-gray-800 text-right">
                                @if($empLoan->project)
                                    <span class="inline-flex items-center gap-1 text-blue-700 bg-blue-50 px-2 py-0.5 rounded font-bold">
                                        <i class="fa-solid fa-diagram-project text-[10px]"></i>
                                        {{ $empLoan->project->name }} {{ $empLoan->project->no_project ? '(' . $empLoan->project->no_project . ')' : '' }}
                                    </span>
                                @elseif($empLoan->employee && $empLoan->employee->project)
                                    {{ $empLoan->employee->project->name }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Departemen Asal</span>
                            <span class="font-semibold text-gray-800 text-right">{{ $empLoan->employee->project->name ?? '-' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Perusahaan</span>
                            <span class="font-semibold text-gray-800 text-right">{{ $empLoan->employee->company->name ?? '-' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-gray-500">Tanggal Bergabung</span>
                            <span class="font-semibold text-gray-800 text-right">{{ $empLoan->employee->formatted_join_date ?? '-' }}</span>
                        </div>
                    </div>

                    @if($empLoan->employee)
                        <div class="pt-2">
                            <a href="{{ route('employee.show', $empLoan->employee->id) }}" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                Lihat Profil Lengkap Karyawan
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Loan Information Summary -->
                <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                            <h2 class="font-bold text-sm text-gray-900">Rincian Finansial Pinjaman</h2>
                        </div>
                        <span class="text-xs text-gray-500 font-medium">Tanggal: {{ $empLoan->loan_date ? $empLoan->loan_date->format('d M Y') : '-' }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Nominal Pinjaman</span>
                            <p class="text-xl font-black text-gray-900 mt-1">Rp {{ number_format($empLoan->amount, 2, ',', '.') }}</p>
                        </div>

                        <div class="bg-blue-50/60 p-4 rounded-xl border border-blue-100">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Cicilan Per Bulan</span>
                            <p class="text-xl font-black text-blue-900 mt-1">Rp {{ number_format($empLoan->installment_amount, 2, ',', '.') }}</p>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Durasi Pinjaman</span>
                            <p class="text-base font-bold text-gray-800 mt-1">{{ $empLoan->duration ?? '-' }} Bulan</p>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Periode Cicilan</span>
                            <p class="text-base font-bold text-gray-800 mt-1">
                                {{ $empLoan->start_date ? $empLoan->start_date->format('M Y') : '-' }} s/d {{ $empLoan->end_date ? $empLoan->end_date->format('M Y') : '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <span class="block text-xs font-semibold text-gray-600 mb-1">Deskripsi / Keterangan:</span>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-xs text-gray-700">
                            {{ $empLoan->description ?: 'Tidak ada keterangan tambahan.' }}
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <span class="block text-xs font-semibold text-gray-600 mb-2">Lampiran Dokumen / Attachment:</span>
                        @if($empLoan->attachment)
                            <div class="flex items-center justify-between p-3.5 bg-blue-50/60 border border-blue-200 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm shadow-xs">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-gray-900 block">{{ basename($empLoan->attachment) }}</span>
                                        <span class="text-[11px] text-gray-500">Berkas pendukung perjanjian pinjaman</span>
                                    </div>
                                </div>
                                <a href="{{ route('emp-loans.download-attachment', $empLoan->id) }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                                    <i class="fa-solid fa-download text-xs"></i>
                                    Unduh Berkas
                                </a>
                            </div>
                        @else
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-xs text-gray-400 italic">
                                <i class="fa-solid fa-info-circle mr-1"></i> Tidak ada dokumen lampiran yang diunggah.
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Installment Simulation Schedule -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-indigo-600"></i>
                        <div>
                            <h2 class="font-bold text-sm text-gray-900">Jadwal & Simulasi Pemotongan Angsuran Pinjaman</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Simulasi jadwal potongan cicilan pinjaman bulanan pada sistem penggajian (payroll).</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ count($installments) }} Periode Angsuran
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-gray-600">Cicilan Ke</th>
                                <th class="px-4 py-3 font-semibold text-gray-600">Periode Bulan</th>
                                <th class="px-4 py-3 font-semibold text-gray-600">Tanggal Jatuh Tempo</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 text-right">Nominal Potongan</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 text-right">Sisa Pokok Pinjaman</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($installments as $item)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="px-4 py-3 font-bold text-gray-800">#{{ $item['no'] }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item['period'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 font-mono">{{ $item['due_date'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-700">Rp {{ number_format($item['amount'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-600">Rp {{ number_format($item['remaining'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                            Rencana
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                        Tidak ada data jadwal cicilan. Pastikan durasi dan tanggal mulai cicilan telah diisi dengan benar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-50 font-semibold text-gray-800">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right">Total Angsuran:</td>
                                <td class="px-4 py-3 text-right font-bold text-blue-800">Rp {{ number_format($empLoan->amount, 2, ',', '.') }}</td>
                                <td colspan="2" class="px-4 py-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
