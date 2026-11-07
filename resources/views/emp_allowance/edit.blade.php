<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('emp-allowance.index') }}"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                            title="Kembali ke Daftar Tunjangan">
                            <i class="fa-solid fa-arrow-left text-sm"></i>
                        </a>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ __('Kelola Tunjangan Karyawan') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Atur nominal tunjangan tetap dan tunjangan variabel untuk karyawan bersangkutan.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="fa-solid fa-money-bill-wave text-emerald-500"></i>
                            Payroll Allowance Settings
                        </span>
                    </div>
                </div>
            </div>

            <!-- Employee Profile Info Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-xl shadow-sm shrink-0">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base leading-tight">{{ $employee->name }}</h3>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                                    <i class="fa-solid fa-briefcase text-slate-500 text-[11px]"></i>
                                    {{ $employee->jabatan->jabatan ?? '-' }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200">
                                    <i class="fa-solid fa-diagram-project text-blue-500 text-[11px]"></i>
                                    {{ $employee->project->name ?? 'General' }}
                                </span>
                                @if(!empty($employee->nomor_induk_karyawan))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-50 text-slate-600 text-xs font-mono border border-slate-200">
                                        NIK: {{ $employee->nomor_induk_karyawan }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Live Total Card -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex items-center gap-4 shrink-0">
                        <div>
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Tunjangan</span>
                            <span id="live-total-display" class="font-extrabold text-lg text-emerald-700 font-mono">
                                Rp {{ number_format($totalAllowance ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-calculator text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Formulir Tunjangan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <form id="allowance-form" method="POST" action="{{ route('emp-allowance.update', $employee->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="p-6 text-gray-900 space-y-5">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">Daftar Komponen Tunjangan</h4>
                                <p class="text-xs text-slate-500">Masukkan nominal tunjangan (Rp) pada komponen yang berlaku untuk karyawan ini.</p>
                            </div>
                            <button type="button" id="btn-reset-zeros" class="text-xs text-slate-500 hover:text-slate-800 font-medium underline">
                                Kosongkan Semua
                            </button>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-sm text-left text-gray-500 divide-y divide-slate-200">
                                <thead class="text-xs text-slate-700 uppercase bg-slate-100">
                                    <tr>
                                        <th class="px-5 py-3 w-12 text-center">No</th>
                                        <th class="px-5 py-3">Nama Komponen</th>
                                        <th class="px-5 py-3 w-36 text-center">Kategori</th>
                                        <th class="px-5 py-3 w-64 text-right">Nominal Tunjangan (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($components as $index => $payrollComponent)
                                        @php
                                            $val = $allowances[$payrollComponent->id] ?? '';
                                        @endphp
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="px-5 py-3.5 text-center text-xs font-mono text-slate-400">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <div class="font-bold text-slate-800 text-sm">
                                                    {{ $payrollComponent->name }}
                                                </div>
                                                @if(!empty($payrollComponent->description))
                                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                                        {{ $payrollComponent->description }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ ucfirst($payrollComponent->type) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <div class="relative max-w-xs ml-auto">
                                                    <x-text-input
                                                        id="component_{{ $payrollComponent->id }}"
                                                        name="allowances[{{ $payrollComponent->id }}]"
                                                        type="text"
                                                        class="allowance-input block w-full currency-mask text-right font-mono text-sm py-1.5 border-slate-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"
                                                        value="{{ $val }}"
                                                        placeholder="0.00"
                                                    />
                                                </div>
                                                <x-input-error class="mt-1" :messages="$errors->get('allowances.'.$payrollComponent->id)" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-5 py-8 text-center text-slate-400 text-xs">
                                                Belum ada komponen payroll bertipe tunjangan (allowance) di Master Setting.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                                    <tr>
                                        <td colspan="3" class="px-5 py-3 text-right text-xs uppercase tracking-wider text-slate-600">
                                            Total Akumulasi Tunjangan:
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <span id="footer-total-display" class="text-base text-emerald-700 font-mono">
                                                Rp {{ number_format($totalAllowance ?? 0, 0, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Tombol Aksi -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-xs text-slate-500">
                            <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                            Tunjangan yang bernilai 0 atau dikosongkan tidak akan diikutsertakan.
                        </div>

                        <div class="flex items-center gap-3">
                            <x-secondary-link href="{{ route('emp-allowance.index') }}">
                                {{ __('Batal') }}
                            </x-secondary-link>

                            <x-primary-button id="btn-submit-allowance" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500">
                                <i class="fa-solid fa-check text-xs"></i>
                                <span id="btn-submit-text">{{ __('Simpan Tunjangan') }}</span>
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Inisialisasi Inputmask Currency
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

                // Format initial values
                $('.currency-mask').each(function() {
                    const value = $(this).val();
                    if (value !== '' && !value.includes('.')) {
                        const numValue = parseFloat(value);
                        if (!isNaN(numValue)) {
                            $(this).val(numValue.toFixed(2));
                        }
                    }
                });

                // Live calculation of total allowance
                function calculateLiveTotal() {
                    let grandTotal = 0;
                    $('.allowance-input').each(function () {
                        const cleanVal = $(this).val().replace(/,/g, '');
                        const num = parseFloat(cleanVal);
                        if (!isNaN(num) && num > 0) {
                            grandTotal += num;
                        }
                    });

                    const formatted = 'Rp ' + grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    $('#live-total-display').text(formatted);
                    $('#footer-total-display').text(formatted);
                }

                $('.allowance-input').on('input change keyup', calculateLiveTotal);

                // Reset all to empty
                $('#btn-reset-zeros').on('click', function () {
                    if (confirm('Kosongkan seluruh nominal tunjangan untuk karyawan ini?')) {
                        $('.allowance-input').val('').trigger('input');
                    }
                });

                // Anti-Double Submit
                let isSubmitting = false;
                $('#allowance-form').on('submit', function (e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    isSubmitting = true;
                    $('#btn-submit-allowance').prop('disabled', true);
                    $('#btn-submit-text').html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan...');
                });
            });
        </script>
    @endpush
</x-app-layout>
