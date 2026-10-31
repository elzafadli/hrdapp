<x-app-layout>
        <div class="py-6"
        x-data="{
            cancelModal: false,
            cancelUrl: '',
            requestNumber: '',
            submitting: false,
            openCancel(url, num) {
                this.cancelUrl = url;
                this.requestNumber = num;
                this.submitting = false;
                this.cancelModal = true;
            }
        }">

    {{-- Modal Konfirmasi Batal Pengajuan --}}
    <div
        x-show="cancelModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        @keydown.escape.window="cancelModal = false"
    >
        <div
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
            @click="cancelModal = false"
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
            <div class="h-1.5 bg-gradient-to-r from-rose-400 via-rose-500 to-red-600"></div>

            <div class="px-6 pt-6 pb-5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-xl text-rose-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-900">Batalkan Pengajuan?</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pengajuan yang dibatalkan tidak dapat diproses lagi.</p>
                    </div>
                    <button type="button" @click="cancelModal = false" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100 shrink-0">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 flex items-center justify-between gap-3">
                    <span class="text-xs text-gray-500">No. Pengajuan</span>
                    <span class="text-xs font-bold font-mono text-gray-900" x-text="requestNumber"></span>
                </div>

                <form :action="cancelUrl" method="POST" class="mt-5" @submit="submitting = true">
                    @csrf
                    <div class="flex items-center gap-3">
                        <button type="button"
                            @click="cancelModal = false"
                            :disabled="submitting"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition disabled:opacity-50">
                            Kembali
                        </button>
                        <button type="submit"
                            :disabled="submitting"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow-sm shadow-rose-200 transition disabled:opacity-60">
                            <template x-if="!submitting">
                                <span class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-trash-can text-xs"></i> Ya, Batalkan
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    Membatalkan...
                                </span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alert Flash Message -->
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Top Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Cuti Khusus</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Pengajuan izin berbayar untuk keperluan pernikahan, duka cita, persalinan, dan keagamaan (tidak memotong saldo cuti tahunan).</p>
                </div>
                <div>
                    <a href="{{ route('special-leave.create') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-blue-500/20 transition ease-in-out duration-150">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Ajukan Cuti Khusus
                    </a>
                </div>
            </div>

            <!-- Kartu Statistik Ringkasan Cuti Khusus -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Pengajuan</span>
                        <span class="text-2xl font-extrabold text-gray-800 mt-1 block">{{ $stats['total'] }}</span>
                        <span class="text-[11px] text-gray-400 mt-0.5 block">Sepanjang waktu</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Disetujui</span>
                        <span class="text-2xl font-extrabold text-emerald-600 mt-1 block">{{ $stats['approved'] }}</span>
                        <span class="text-[11px] text-gray-400 mt-0.5 block">Selesai disetujui</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Menunggu Persetujuan</span>
                        <span class="text-2xl font-extrabold text-amber-500 mt-1 block">{{ $stats['pending'] }}</span>
                        <span class="text-[11px] text-gray-400 mt-0.5 block">Sedang diproses</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>

            

            <!-- Riwayat Pengajuan Cuti Khusus Saya -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 text-base">Riwayat Pengajuan Cuti Khusus Saya</h3>
                </div>

                <div class="p-6">
                    @if($leaveRequests->isEmpty())
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                                <i class="fa-solid fa-hand-holding-heart text-2xl"></i>
                            </div>
                            <h4 class="text-gray-700 font-semibold text-sm">Belum ada pengajuan cuti khusus</h4>
                            <p class="text-gray-400 text-xs mt-1">Anda belum pernah mengajukan cuti khusus.</p>
                            <div class="mt-4">
                                <a href="{{ route('special-leave.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-xs transition shadow-sm">
                                    Ajukan Cuti Khusus Sekarang
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-600">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50/80 border-b border-gray-100">
                                    <tr>
                                        <th class="px-4 py-3">No. Pengajuan</th>
                                        <th class="px-4 py-3">Tgl Pengajuan</th>
                                        <th class="px-4 py-3">Tipe Cuti Khusus</th>
                                        <th class="px-4 py-3">Rentang Tanggal</th>
                                        <th class="px-4 py-3">Total Hari</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($leaveRequests as $req)
                                        <tr class="hover:bg-gray-50/60 transition">
                                            <td class="px-4 py-3.5 font-semibold text-gray-900">
                                                {{ $req->request_number ?? 'CUTI-KHUSUS-' . $req->id }}
                                            </td>
                                            <td class="px-4 py-3.5 text-xs text-gray-500">
                                                {{ $req->created_at->format('d M Y') }}
                                            </td>
                                            <td class="px-4 py-3.5 font-semibold text-blue-700">
                                                {{ ($req->relationLoaded('specialLeaveType') ? $req->specialLeaveType?->name : ($req->special_leave_type_id ? \App\Models\SpecialLeaveType::find($req->special_leave_type_id)?->name : null)) ?? 'Cuti Khusus' }}
                                            </td>
                                            <td class="px-4 py-3.5 text-xs">
                                                @php
                                                    $startDateFmt = $req->full_day_start_date?->format('d/m/Y')
                                                        ?? $req->half_day_start_date?->format('d/m/Y')
                                                        ?? $req->dates->first()?->date?->format('d/m/Y');
                                                    $endDateFmt = $req->full_day_end_date?->format('d/m/Y')
                                                        ?? $req->half_day_end_date?->format('d/m/Y')
                                                        ?? $req->dates->last()?->date?->format('d/m/Y');
                                                @endphp
                                                <span class="font-medium text-gray-800">
                                                    {{ $startDateFmt }}{{ $endDateFmt && $endDateFmt !== $startDateFmt ? ' - ' . $endDateFmt : '' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 font-extrabold text-gray-900">
                                                {{ $req->total_days }} Hari
                                            </td>
                                            <td class="px-4 py-3.5">
                                                @if($req->status === 'completed' || $req->status === 'approved')
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                        <i class="fa-solid fa-circle-check text-[10px]"></i> Disetujui
                                                    </span>
                                                @elseif($req->status === 'rejected')
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                        <i class="fa-solid fa-circle-xmark text-[10px]"></i> Ditolak
                                                    </span>
                                                @elseif($req->status === 'cancelled')
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                        Dibatalkan
                                                    </span>
                                                @else
                                                    @php
                                                        $pendingApproval = $req->relationLoaded('approvals') ? $req->approvals->where('status', 'pending')->sortBy('level')->first() : null;
                                                        $targetJabatan = $pendingApproval?->jabatan?->jabatan;
                                                    @endphp
                                                    <div class="space-y-1">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                            <i class="fa-solid fa-clock text-[10px]"></i> Menunggu
                                                        </span>
                                                        @if($pendingApproval)
                                                            <div class="text-[11px] leading-tight text-gray-600 whitespace-nowrap">
                                                                <span class="text-gray-400">Approval:</span>
                                                                <strong class="text-gray-900 font-bold">{{ $targetJabatan ?? 'Approver' }}</strong>
                                                                <span class="inline-block px-1 rounded bg-blue-50 text-blue-700 text-[10px] font-mono font-bold">L{{ $pendingApproval->level }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                                <a href="{{ route('special-leave.show', $req->id) }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:text-blue-600 shadow-xs transition">
                                                    <i class="fa-solid fa-eye text-gray-400 text-[11px]"></i> Detail
                                                </a>
                                                @if($req->status === 'pending')
                                                    <button type="button"
                                                        @click="openCancel('{{ route('special-leave.cancel', $req->id) }}', '{{ $req->request_number ?? ('CK-' . $req->id) }}')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-rose-300 text-xs font-semibold text-rose-700 bg-white hover:bg-rose-50 shadow-xs transition cursor-pointer">
                                                        <i class="fa-solid fa-xmark text-[11px]"></i> Batal
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $leaveRequests->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>