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
        class="fixed inset-0 z-[100] flex items-center justify-center"
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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Izin Lembur</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Pengajuan permohonan kerja lembur (Surat Perintah Kerja Lembur / Mandiri) di luar jam operasional.</p>
                </div>
                <div>
                    <a href="{{ route('overtime.create') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-blue-500/20 transition ease-in-out duration-150">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Ajukan Lembur
                    </a>
                </div>
            </div>

            <!-- Kartu Statistik Ringkasan Lembur -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Pengajuan</span>
                        <span class="text-2xl font-extrabold text-gray-800 mt-1 block">{{ $stats['total'] }}</span>
                        <span class="text-[11px] text-gray-400 mt-0.5 block">Sepanjang waktu</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Jam Lembur</span>
                        <span class="text-2xl font-extrabold text-purple-600 mt-1 block">{{ number_format($stats['total_hours'], 1) }}</span>
                        <span class="text-[11px] text-gray-400 mt-0.5 block">Jam kerja disetujui</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-clock"></i>
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

            

            <!-- Tabel Riwayat Pengajuan Lembur Mandiri -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
                        Riwayat Pengajuan Lembur
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50/75 text-gray-500 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3.5">No. Pengajuan</th>
                                <th class="px-6 py-3.5">Tanggal Lembur</th>
                                <th class="px-6 py-3.5">Rentang Jam</th>
                                <th class="px-6 py-3.5 text-center">Istirahat</th>
                                <th class="px-6 py-3.5 text-center">Durasi Lembur</th>
                                <th class="px-6 py-3.5">Tipe Hari</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse($overtimeRequests as $item)
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 font-mono font-semibold text-xs text-blue-600">
                                        {{ $item->request_number ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium text-gray-800">
                                        {{ $item->date?->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-700">
                                        {{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-center text-gray-500">
                                        {{ $item->break_minutes }} mnt
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100">
                                            {{ $item->total_hours }} Jam
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        @if($item->day_type === 'holiday')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-rose-50 text-rose-700">Libur Nasional</span>
                                        @elseif($item->day_type === 'weekend')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-700">Akhir Pekan (Off)</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700">Hari Kerja</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($item->status === 'completed' || $item->status === 'approved')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i> Disetujui
                                            </span>
                                        @elseif($item->status === 'pending')
                                            @php
                                                $pendingApproval = $item->relationLoaded('approvals') ? $item->approvals->where('status', 'pending')->sortBy('level')->first() : null;
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
                                        @elseif($item->status === 'rejected')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                <i class="fa-solid fa-circle-xmark text-[10px]"></i> Ditolak
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                                <i class="fa-solid fa-ban text-[10px]"></i> Dibatalkan
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                                <a href="{{ route('overtime.show', $item->id) }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:text-blue-600 shadow-xs transition">
                                                    <i class="fa-solid fa-eye text-gray-400 text-[11px]"></i> Detail
                                                </a>
                                                @if($item->status === 'pending')
                                                    <button type="button"
                                                        @click="openCancel('{{ route('overtime.cancel', $item->id) }}', '{{ $item->request_number ?? ('LB-' . $item->id) }}')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-rose-300 text-xs font-semibold text-rose-700 bg-white hover:bg-rose-50 shadow-xs transition cursor-pointer">
                                                        <i class="fa-solid fa-xmark text-[11px]"></i> Batal
                                                    </button>
                                                @endif
                                            </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-business-time text-3xl text-gray-300"></i>
                                            <p class="text-sm">Belum ada riwayat pengajuan izin lembur.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($overtimeRequests->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $overtimeRequests->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>