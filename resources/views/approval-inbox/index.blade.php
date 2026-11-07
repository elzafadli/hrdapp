<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Inbox Approval Terpusat
        </h2>
    </x-slot>

    <div
        x-data="{
            approveModal: false,
            rejectModal: false,
            selectedId: null,
            selectedUrl: '',
            selectedNum: '',
            selectedName: '',
            selectedModule: '',
            selectedColor: '',
            approveNotes: 'Disetujui.',
            rejectNotes: '',
            submitting: false,
            openApprove(id, url, num, name, mod, col) {
                this.selectedId = id;
                this.selectedUrl = url;
                this.selectedNum = num;
                this.selectedName = name;
                this.selectedModule = mod;
                this.selectedColor = col;
                this.approveNotes = 'Disetujui.';
                this.submitting = false;
                this.approveModal = true;
            },
            openReject(id, url, num, name, mod, col) {
                this.selectedId = id;
                this.selectedUrl = url;
                this.selectedNum = num;
                this.selectedName = name;
                this.selectedModule = mod;
                this.selectedColor = col;
                this.rejectNotes = '';
                this.submitting = false;
                this.rejectModal = true;
            }
        }"
        class="py-6 px-4 sm:px-6 lg:px-8 space-y-5"
    >

        {{-- GLOBAL APPROVE MODAL --}}
        <div
            x-show="approveModal"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center"
            @keydown.escape.window="approveModal = false"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="approveModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            {{-- Modal Card --}}
            <div
                class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                @click.stop
            >
                <div class="h-1.5 bg-gradient-to-r from-emerald-400 via-teal-500 to-emerald-600"></div>

                <div class="px-6 pt-6 pb-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-check text-xl text-emerald-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900">Konfirmasi Persetujuan</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tindakan ini akan memproses persetujuan tahapan dokumen.</p>
                        </div>
                        <button type="button" @click="approveModal = false" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100 shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">No. Pengajuan</span>
                            <span class="text-xs font-bold font-mono text-gray-900" x-text="selectedNum"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">Pemohon</span>
                            <span class="text-xs font-semibold text-gray-800" x-text="selectedName"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">Modul</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                                  :class="selectedColor"
                                  x-text="selectedModule"></span>
                        </div>
                    </div>

                    <form :action="selectedUrl" method="POST" class="mt-4"
                          @submit="submitting = true">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Catatan Persetujuan
                            <span class="text-gray-400 font-normal">(opsional)</span>
                        </label>
                        <textarea
                            name="notes"
                            x-model="approveNotes"
                            rows="2"
                            class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none transition"
                            placeholder="Tulis catatan persetujuan..."></textarea>

                        <div class="flex items-center gap-3 mt-4">
                            <button type="button"
                                @click="approveModal = false"
                                :disabled="submitting"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition disabled:opacity-50">
                                Batal
                            </button>
                            <button type="submit"
                                :disabled="submitting"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-sm shadow-emerald-200 transition disabled:opacity-60">
                                <template x-if="!submitting">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-check text-xs"></i> Ya, Setujui
                                    </span>
                                </template>
                                <template x-if="submitting">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </template>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- GLOBAL REJECT MODAL --}}
        <div
            x-show="rejectModal"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center"
            @keydown.escape.window="rejectModal = false"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="rejectModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            {{-- Modal Card --}}
            <div
                class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                @click.stop
            >
                <div class="h-1.5 bg-gradient-to-r from-rose-400 via-red-500 to-rose-600"></div>

                <div class="px-6 pt-6 pb-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-xmark text-xl text-rose-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900">Konfirmasi Penolakan</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tulis alasan penolakan sebelum memproses penolakan.</p>
                        </div>
                        <button type="button" @click="rejectModal = false" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100 shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">No. Pengajuan</span>
                            <span class="text-xs font-bold font-mono text-gray-900" x-text="selectedNum"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">Pemohon</span>
                            <span class="text-xs font-semibold text-gray-800" x-text="selectedName"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">Modul</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                                  :class="selectedColor"
                                  x-text="selectedModule"></span>
                        </div>
                    </div>

                    <form :action="selectedUrl" method="POST" class="mt-4"
                          @submit="if(rejectNotes.trim()){ submitting = true; } else { $event.preventDefault(); }">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Alasan Penolakan <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            name="notes"
                            x-model="rejectNotes"
                            rows="3"
                            required
                            maxlength="500"
                            class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 resize-none transition"
                            placeholder="Tuliskan alasan penolakan..."></textarea>
                        <p class="text-[11px] text-gray-400 mt-1 text-right" x-text="rejectNotes.length + '/500'"></p>

                        <div class="flex items-center gap-3 mt-4">
                            <button type="button"
                                @click="rejectModal = false"
                                :disabled="submitting"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition disabled:opacity-50">
                                Batal
                            </button>
                            <button type="submit"
                                :disabled="submitting || rejectNotes.trim() === ''"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow-sm shadow-rose-200 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!submitting">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-xmark text-xs"></i> Konfirmasi Tolak
                                    </span>
                                </template>
                                <template x-if="submitting">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </template>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- HEADER --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700">
                        <i class="fa-solid fa-inbox text-sm"></i>
                    </span>
                    Inbox Persetujuan
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">Semua permohonan dari seluruh modul ESS yang perlu Anda tinjau</p>
            </div>
        </div>

        {{-- STATISTIK --}}
        @if(!empty($stats))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <a href="{{ route('approval-inbox.index', ['status' => 'pending', 'module' => $filterModule]) }}"
               class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex items-center gap-3 hover:shadow-md transition group {{ $filterStatus === 'pending' ? 'ring-2 ring-amber-400' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm shrink-0 group-hover:bg-amber-200 transition">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-amber-700">{{ $stats['pending'] }}</div>
                    <div class="text-[11px] text-gray-500">Menunggu Aksi</div>
                </div>
            </a>
            <a href="{{ route('approval-inbox.index', ['status' => 'approved', 'module' => $filterModule]) }}"
               class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex items-center gap-3 hover:shadow-md transition group {{ $filterStatus === 'approved' ? 'ring-2 ring-emerald-400' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm shrink-0 group-hover:bg-emerald-200 transition">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-emerald-700">{{ $stats['approved'] }}</div>
                    <div class="text-[11px] text-gray-500">Telah Disetujui</div>
                </div>
            </a>
            <a href="{{ route('approval-inbox.index', ['status' => 'rejected', 'module' => $filterModule]) }}"
               class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex items-center gap-3 hover:shadow-md transition group {{ $filterStatus === 'rejected' ? 'ring-2 ring-rose-400' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-sm shrink-0 group-hover:bg-rose-200 transition">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-rose-700">{{ $stats['rejected'] }}</div>
                    <div class="text-[11px] text-gray-500">Ditolak</div>
                </div>
            </a>
            <a href="{{ route('approval-inbox.index', ['status' => 'all', 'module' => $filterModule]) }}"
               class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex items-center gap-3 hover:shadow-md transition group {{ $filterStatus === 'all' ? 'ring-2 ring-blue-400' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm shrink-0 group-hover:bg-blue-200 transition">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-blue-700">{{ $stats['total'] }}</div>
                    <div class="text-[11px] text-gray-500">Total Semua</div>
                </div>
            </a>
        </div>
        @endif

        {{-- FILTER --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form method="GET" action="{{ route('approval-inbox.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-full sm:w-60">
                    <x-input-label for="filter_module" :value="__('Modul ESS')" class="text-xs font-semibold text-gray-600 mb-1" />
                    <x-select-input id="filter_module" name="module" :noSelect2="true" onchange="this.form.submit()" class="w-full">
                        <option value="all" {{ $filterModule === 'all' ? 'selected' : '' }}>Semua Modul</option>
                        <option value="leave" {{ $filterModule === 'leave' ? 'selected' : '' }}>Cuti Tahunan</option>
                        <option value="special_leave" {{ $filterModule === 'special_leave' ? 'selected' : '' }}>Cuti Khusus</option>
                        <option value="permit" {{ $filterModule === 'permit' ? 'selected' : '' }}>Izin</option>
                        <option value="sick" {{ $filterModule === 'sick' ? 'selected' : '' }}>Sakit</option>
                        <option value="overtime" {{ $filterModule === 'overtime' ? 'selected' : '' }}>Lembur</option>
                    </x-select-input>
                </div>
                <div class="w-full sm:w-52">
                    <x-input-label for="filter_status" :value="__('Status')" class="text-xs font-semibold text-gray-600 mb-1" />
                    <x-select-input id="filter_status" name="status" :noSelect2="true" onchange="this.form.submit()" class="w-full">
                        <option value="pending" {{ $filterStatus === 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved" {{ $filterStatus === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ $filterStatus === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    </x-select-input>
                </div>
            </form>
        </div>

        {{-- SESSION FLASH --}}
        @if(session('success'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium">
            <i class="fa-solid fa-circle-check text-emerald-600 shrink-0"></i>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-3 text-sm font-medium">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 shrink-0"></i>
            {{ session('error') }}
        </div>
        @endif

        {{-- TABEL APPROVAL --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-base">Daftar Permohonan</h3>
                <span class="text-xs text-gray-400">{{ $approvals->total() }} permohonan ditemukan</span>
            </div>

            @if($approvals->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                        <i class="fa-solid fa-inbox text-3xl"></i>
                    </div>
                    <h4 class="text-gray-600 font-semibold text-sm">Inbox Kosong</h4>
                    <p class="text-gray-400 text-xs mt-1">Tidak ada permohonan yang perlu ditinjau saat ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50/80 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3">Modul</th>
                                <th class="px-4 py-3">No. Pengajuan</th>
                                <th class="px-4 py-3">Pemohon</th>
                                <th class="px-4 py-3">Jabatan</th>
                                <th class="px-4 py-3">Tgl Pengajuan</th>
                                <th class="px-4 py-3">Level</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($approvals as $approval)
                            @php
                                $doc        = $approval->approvable;
                                $emp        = $doc?->employee;
                                $isOvertime = $doc instanceof \App\Models\OvertimeRequest;

                                if ($isOvertime) {
                                    $moduleLabel = 'Lembur';
                                    $moduleBg    = 'bg-purple-100 text-purple-700';
                                    $moduleIcon  = 'fa-moon';
                                    $detailUrl   = $doc ? route('overtime.show', $doc->id) : '#';
                                } elseif ($doc && $doc->leave_type === 'special') {
                                    $moduleLabel = 'Cuti Khusus';
                                    $moduleBg    = 'bg-pink-100 text-pink-700';
                                    $moduleIcon  = 'fa-hand-holding-heart';
                                    $detailUrl   = $doc ? route('special-leave.show', $doc->id) : '#';
                                } elseif ($doc && $doc->leave_type === 'permit') {
                                    $moduleLabel = 'Izin';
                                    $moduleBg    = 'bg-orange-100 text-orange-700';
                                    $moduleIcon  = 'fa-file-circle-check';
                                    $detailUrl   = $doc ? route('permit.show', $doc->id) : '#';
                                } elseif ($doc && $doc->leave_type === 'sick') {
                                    $moduleLabel = 'Sakit';
                                    $moduleBg    = 'bg-red-100 text-red-700';
                                    $moduleIcon  = 'fa-kit-medical';
                                    $detailUrl   = $doc ? route('sick.show', $doc->id) : '#';
                                } else {
                                    $moduleLabel = 'Cuti Tahunan';
                                    $moduleBg    = 'bg-blue-100 text-blue-700';
                                    $moduleIcon  = 'fa-umbrella-beach';
                                    $detailUrl   = $doc ? route('leave.show', $doc->id) : '#';
                                }

                                $canAct        = $approval->canUserApprove(auth()->user(), $doc);
                                $approveUrl    = route('approval-inbox.approve', $approval->id);
                                $rejectUrl     = route('approval-inbox.reject', $approval->id);
                                $requestNumber = $doc?->request_number ?? '-';
                                $empName       = $emp?->name ?? '-';
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition">
                                {{-- Modul --}}
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-bold {{ $moduleBg }}">
                                        <i class="fa-solid {{ $moduleIcon }} text-[10px]"></i>
                                        {{ $moduleLabel }}
                                    </span>
                                </td>
                                {{-- No Pengajuan --}}
                                <td class="px-4 py-3.5">
                                    <span class="font-mono text-xs font-semibold text-gray-800">{{ $requestNumber }}</span>
                                </td>
                                {{-- Pemohon --}}
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-gray-900 text-xs">{{ $empName }}</div>
                                    @if($emp?->employee_id)
                                        <div class="text-[11px] text-gray-400 font-mono">{{ $emp->employee_id }}</div>
                                    @endif
                                </td>
                                {{-- Jabatan Pemohon --}}
                                <td class="px-4 py-3.5">
                                    <span class="text-xs text-gray-600">{{ $emp?->jabatan?->jabatan ?? '-' }}</span>
                                </td>
                                {{-- Tanggal Pengajuan --}}
                                <td class="px-4 py-3.5 text-xs text-gray-500">
                                    {{ $doc?->created_at?->format('d M Y, H:i') ?? '-' }}
                                </td>
                                {{-- Level --}}
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                        Level {{ $approval->level }}
                                    </span>
                                    <div class="text-[11px] text-gray-500 mt-0.5 whitespace-nowrap font-medium">
                                        @if($approval->approver_emp_id)
                                            <span class="text-blue-600 font-semibold"><i class="fa-solid fa-user-shield text-[10px]"></i> Pengganti: {{ $approval->approverEmployee?->name ?? 'Karyawan' }}</span>
                                        @else
                                            {{ $approval->jabatan?->jabatan ?? '-' }}
                                        @endif
                                    </div>
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-3.5">
                                    @if($approval->status === 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i> Disetujui
                                        </span>
                                        @if($approval->action_at)
                                            <div class="text-[11px] text-gray-400 mt-0.5">{{ $approval->action_at->format('d M Y, H:i') }}</div>
                                        @endif
                                    @elseif($approval->status === 'rejected')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            <i class="fa-solid fa-circle-xmark text-[10px]"></i> Ditolak
                                        </span>
                                        @if($approval->action_at)
                                            <div class="text-[11px] text-gray-400 mt-0.5">{{ $approval->action_at->format('d M Y, H:i') }}</div>
                                        @endif
                                    @else
                                        @if($canAct)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 animate-pulse">
                                                <i class="fa-solid fa-clock text-[10px]"></i> Menunggu Aksi Anda
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                <i class="fa-solid fa-hourglass-half text-[10px]"></i> Menunggu Level Lain
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                {{-- Aksi --}}
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Tombol Detail --}}
                                        <a href="{{ $detailUrl }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:text-blue-600 shadow-xs transition">
                                            <i class="fa-solid fa-eye text-gray-400 text-[11px]"></i> Detail
                                        </a>

                                        @if($canAct)
                                            {{-- Tombol Setujui → Buka Modal --}}
                                            <button type="button"
                                                @click="openApprove({{ $approval->id }}, '{{ $approveUrl }}', '{{ $requestNumber }}', '{{ addslashes($empName) }}', '{{ $moduleLabel }}', '{{ $moduleBg }}')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 shadow-xs transition cursor-pointer">
                                                <i class="fa-solid fa-check text-[11px]"></i> Setujui
                                            </button>

                                            {{-- Tombol Tolak → Buka Modal --}}
                                            <button type="button"
                                                @click="openReject({{ $approval->id }}, '{{ $rejectUrl }}', '{{ $requestNumber }}', '{{ addslashes($empName) }}', '{{ $moduleLabel }}', '{{ $moduleBg }}')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-rose-300 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 shadow-xs transition cursor-pointer">
                                                <i class="fa-solid fa-xmark text-[11px]"></i> Tolak
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($approvals->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $approvals->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
