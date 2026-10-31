<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Pengajuan Cuti Tahunan
        </h2>
    </x-slot>

    <div
        x-data="{
            approveModal: false,
            rejectModal: false,
            actionUrl: '',
            docNumber: '',
            empName: '',
            approveNotes: 'Disetujui.',
            rejectNotes: '',
            submitting: false,
            openApproveModal(id, url, num, name) {
                this.actionUrl = url;
                this.docNumber = num;
                this.empName = name;
                this.approveNotes = 'Disetujui.';
                this.submitting = false;
                this.approveModal = true;
            },
            openRejectModal(id, url, num, name) {
                this.actionUrl = url;
                this.docNumber = num;
                this.empName = name;
                this.rejectNotes = '';
                this.submitting = false;
                this.rejectModal = true;
            }
        }"
        class="py-6 px-4 sm:px-6 lg:px-8 space-y-6"
    >

        {{-- GLOBAL APPROVE MODAL --}}
        <div
            x-show="approveModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center"
            @keydown.escape.window="approveModal = false"
        >
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
                            <h3 class="text-base font-bold text-gray-900">Konfirmasi Persetujuan Cuti</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Tindakan ini akan menyetujui tahapan pengajuan cuti ini.</p>
                        </div>
                        <button type="button" @click="approveModal = false" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100 shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">No. Pengajuan</span>
                            <span class="text-xs font-bold font-mono text-gray-900" x-text="docNumber"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">Pemohon</span>
                            <span class="text-xs font-semibold text-gray-800" x-text="empName"></span>
                        </div>
                    </div>

                    <form :action="actionUrl" method="POST" class="mt-4" @submit="submitting = true">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Catatan Persetujuan <span class="text-gray-400 font-normal">(opsional)</span>
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
            class="fixed inset-0 z-50 flex items-center justify-center"
            @keydown.escape.window="rejectModal = false"
        >
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
                            <h3 class="text-base font-bold text-gray-900">Konfirmasi Penolakan Cuti</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Berikan alasan penolakan untuk karyawan pemohon.</p>
                        </div>
                        <button type="button" @click="rejectModal = false" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100 shrink-0">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">No. Pengajuan</span>
                            <span class="text-xs font-bold font-mono text-gray-900" x-text="docNumber"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">Pemohon</span>
                            <span class="text-xs font-semibold text-gray-800" x-text="empName"></span>
                        </div>
                    </div>

                    <form :action="actionUrl" method="POST" class="mt-4"
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

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 text-sm">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 text-sm">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Main Detail Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Header Card --}}
            <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center gap-4">
                    <a href="{{ route('leave.index') }}"
                        class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition shadow-xs">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Detail Pengajuan Cuti</h2>
                        <p class="text-xs text-gray-400">Nomor: {{ $leaveRequest->request_number ?? 'CUTI-' . $leaveRequest->id }}</p>
                    </div>
                </div>
                <div>
                    @if($leaveRequest->status === 'completed' || $leaveRequest->status === 'approved')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <i class="fa-solid fa-circle-check"></i> Disetujui
                        </span>
                    @elseif($leaveRequest->status === 'rejected')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                        </span>
                    @elseif($leaveRequest->status === 'cancelled')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                            Dibatalkan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                            <i class="fa-solid fa-clock"></i> Menunggu Persetujuan
                        </span>
                    @endif
                </div>
            </div>

            {{-- 2-Column Responsive Body --}}
            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

                {{-- Left Column: Data Detail Pengajuan --}}
                <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                    <!-- Data Pemohon -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-gray-50 border border-gray-100">
                        <div>
                            <span class="block text-xs text-gray-400 font-medium">Nama Pemohon</span>
                            <span class="font-bold text-sm text-gray-800">{{ $leaveRequest->employee?->name ?? 'Karyawan' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 font-medium">Jabatan</span>
                            <span class="font-bold text-sm text-gray-800">{{ $leaveRequest->employee?->jabatan?->jabatan ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 font-medium">Waktu Pengajuan</span>
                            <span class="font-semibold text-sm text-gray-700">{{ $leaveRequest->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>

                    <!-- Ringkasan Periode & Hari -->
                    <div class="space-y-3">
                        <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                            <i class="fa-solid fa-calendar-days text-blue-500"></i>
                            Rincian Hari Cuti
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100">
                                <span class="text-xs text-blue-600 font-semibold block">Cuti Sehari Penuh</span>
                                <span class="text-lg font-extrabold text-blue-900 mt-0.5 block">
                                    {{ (float) $leaveRequest->full_day_count }} <span class="text-xs font-medium">Hari</span>
                                </span>
                                @if($leaveRequest->full_day_count > 0)
                                    <span class="text-[11px] text-blue-700 block mt-1">
                                        {{ $leaveRequest->full_day_start_date?->format('d/m/Y') }} - {{ $leaveRequest->full_day_end_date?->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>

                            <div class="p-3.5 bg-amber-50/60 rounded-xl border border-amber-100">
                                <span class="text-xs text-amber-600 font-semibold block">Cuti Setengah Hari</span>
                                <span class="text-lg font-extrabold text-amber-900 mt-0.5 block">
                                    {{ (float) $leaveRequest->half_day_count }} <span class="text-xs font-medium">Hari</span>
                                </span>
                                @if($leaveRequest->half_day_count > 0)
                                    <span class="text-[11px] text-amber-700 block mt-1">
                                        {{ $leaveRequest->half_day_start_date?->format('d/m/Y') }} ({{ $leaveRequest->half_day_type === 'first_half' ? 'Pagi' : 'Siang' }})
                                    </span>
                                @endif
                            </div>

                            <div class="p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-100">
                                <span class="text-xs text-emerald-600 font-semibold block">Total Potong Saldo</span>
                                <span class="text-lg font-extrabold text-emerald-900 mt-0.5 block">
                                    {{ (float) $leaveRequest->total_days }} <span class="text-xs font-medium">Hari Kerja</span>
                                </span>
                                <span class="text-[11px] text-emerald-700 block mt-1">Hari off/libur tidak dihitung</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian Tanggal Efektif Terpotong -->
                    @if($leaveRequest->dates->isNotEmpty())
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Jadwal Tanggal Terpotong:</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($leaveRequest->dates as $d)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                        <i class="fa-regular fa-calendar text-[10px] text-gray-500"></i>
                                        {{ $d->date->format('d M Y') }}
                                        <span class="text-[10px] font-bold text-blue-600">({{ $d->day_type === 'half_day' ? '0.5 Hari' : '1 Hari' }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Alasan / Keterangan -->
                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-1">
                        <span class="text-xs text-gray-400 font-medium block">Alasan / Keterangan Pengajuan</span>
                        <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $leaveRequest->reason ?? '-' }}</p>
                    </div>

                    <!-- Berkas Lampiran -->
                    @if($leaveRequest->files->isNotEmpty())
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Berkas Pendukung:</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($leaveRequest->files as $file)
                                    <a href="{{ $file->url ?? Storage::url($file->file_path) }}" target="_blank"
                                        class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50/40 transition group">
                                        <i class="fa-solid fa-paperclip text-gray-400 group-hover:text-blue-500"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold text-gray-800 truncate group-hover:text-blue-600">{{ $file->file_name }}</p>
                                            <p class="text-[10px] text-gray-400">{{ number_format($file->file_size / 1024, 1) }} KB</p>
                                        </div>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-gray-300 group-hover:text-blue-500"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Right Column: Alur Approval Berjenjang & Jejak Audit --}}
                <div class="lg:col-span-5 xl:col-span-4 space-y-6">

                    <!-- Alur Persetujuan (Approval Workflow) -->
                    @if($leaveRequest->approvals->isNotEmpty())
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-4">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2 pb-2 border-b border-gray-100">
                                <i class="fa-solid fa-clipboard-check text-blue-600"></i>
                                Tahapan Persetujuan Berjenjang
                            </h3>

                            <div class="space-y-3">
                                @foreach($leaveRequest->approvals as $approval)
                                    @php
                                        $canApprove = $approval->canUserApprove(Auth::user(), $leaveRequest);
                                        $empApplicantName = $leaveRequest->employee?->name ?? 'Karyawan';
                                        $approveUrl = route('leave.approve', $approval->id);
                                        $rejectUrl = route('leave.reject', $approval->id);
                                    @endphp
                                    <div class="p-3.5 rounded-xl border {{ $approval->status === 'approved' ? 'border-emerald-200 bg-emerald-50/40' : ($approval->status === 'rejected' ? 'border-rose-200 bg-rose-50/40' : 'border-gray-200 bg-gray-50/50') }} space-y-2.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-bold px-1.5 py-0.5 rounded bg-gray-200 text-gray-700">L{{ $approval->level }}</span>
                                                <span class="font-bold text-xs text-gray-900">{{ $approval->jabatan_name }}</span>
                                            </div>
                                            <div>
                                                @if($approval->status === 'approved')
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                        <i class="fa-solid fa-circle-check text-[9px]"></i> Disetujui
                                                    </span>
                                                @elseif($approval->status === 'rejected')
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                        <i class="fa-solid fa-circle-xmark text-[9px]"></i> Ditolak
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                        <i class="fa-solid fa-clock text-[9px]"></i> Menunggu
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <p class="text-[11px] text-gray-500 leading-relaxed">
                                            @if($approval->status === 'approved')
                                                Disetujui oleh: <strong class="text-gray-800">{{ $approval->approver_name }}</strong> ({{ $approval->approver_jabatan_name }}) pada {{ $approval->action_at?->format('d M Y, H:i') }}
                                            @elseif($approval->status === 'rejected')
                                                Ditolak oleh: <strong class="text-rose-700">{{ $approval->approver_name }}</strong> ({{ $approval->approver_jabatan_name }})<br>Catatan: {{ $approval->notes }}
                                            @else
                                                Menunggu persetujuan jabatan <strong>{{ $approval->jabatan_name }}</strong>.
                                            @endif
                                        </p>

                                        <!-- Tombol Aksi Approver dengan Modal Keren -->
                                        @if($canApprove)
                                            <div class="pt-2 border-t border-gray-200/80">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button type="button"
                                                        @click="openApproveModal({{ $approval->id }}, '{{ $approveUrl }}', '{{ $leaveRequest->request_number }}', '{{ addslashes($empApplicantName) }}')"
                                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-emerald-300 text-xs font-bold text-emerald-700 bg-white hover:bg-emerald-50 shadow-xs transition cursor-pointer">
                                                        <i class="fa-solid fa-check text-[11px]"></i> Setujui
                                                    </button>
                                                    <button type="button"
                                                        @click="openRejectModal({{ $approval->id }}, '{{ $rejectUrl }}', '{{ $leaveRequest->request_number }}', '{{ addslashes($empApplicantName) }}')"
                                                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-rose-300 text-xs font-bold text-rose-700 bg-white hover:bg-rose-50 shadow-xs transition cursor-pointer">
                                                        <i class="fa-solid fa-xmark text-[11px]"></i> Tolak
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Riwayat Audit Log (Approval History) -->
                    @if($leaveRequest->approvalHistories->isNotEmpty())
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-3">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-100">
                                <i class="fa-solid fa-clock-rotate-left text-gray-500"></i>
                                Riwayat Log Aktivitas & Jejak Audit
                            </h3>
                            <div class="divide-y divide-gray-100 text-xs">
                                @foreach($leaveRequest->approvalHistories as $log)
                                    <div class="py-2.5 flex items-start justify-between gap-2">
                                        <div class="space-y-0.5 min-w-0">
                                            <div class="font-semibold text-gray-900 truncate">
                                                <span class="uppercase tracking-wider px-1.5 py-0.5 text-[10px] rounded {{ $log->action === 'approved' || $log->action === 'auto_completed' ? 'bg-emerald-100 text-emerald-800' : ($log->action === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-700') }}">
                                                    {{ $log->action }}
                                                </span>
                                                <span class="ml-1 text-gray-700">{{ $log->user_name ?? 'Sistem' }}</span>@if($log->jabatan_name) <span class="text-gray-400 font-normal">({{ $log->jabatan_name }})</span>@endif
                                            </div>
                                            @if($log->notes)
                                                <p class="text-gray-500 italic text-[11px] truncate">"{{ $log->notes }}"</p>
                                            @endif
                                        </div>
                                        <span class="text-gray-400 shrink-0 text-[10px] font-mono">{{ $log->created_at->format('d/m H:i') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
