<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-lg text-gray-900 leading-tight">
                            {{ $empLoan->exists ? 'Edit Pinjaman Karyawan' : 'Buat Pinjaman Karyawan Baru' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Lengkapi formulir informasi pinjaman karyawan, alokasi proyek, dan lampiran pendukung.</p>
                    </div>
                </div>

                <form method="POST"
                    action="{{ $empLoan->exists ? route('emp-loans.update', $empLoan) : route('emp-loans.store') }}"
                    enctype="multipart/form-data"
                    x-data="{ isSubmitting: false }"
                    @submit="isSubmitting = true">
                    @csrf
                    @if($empLoan->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900 space-y-5">
                        @if ($errors->any())
                            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-xs" role="alert">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fa-solid fa-circle-xmark text-red-600"></i>
                                    <span class="font-semibold text-xs">Terdapat beberapa kesalahan input:</span>
                                </div>
                                <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Row 1: Tanggal Pinjaman & Employee -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <x-input-label for="loan_date" :value="__('Tanggal Pinjaman')" :required="true" />
                                <x-text-input id="loan_date" name="loan_date" type="date" class="mt-1 block w-full"
                                    :value="old('loan_date', $empLoan->loan_date ? $empLoan->loan_date->format('Y-m-d') : date('Y-m-d'))" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('loan_date')" />
                            </div>

                            <div>
                                <x-input-label for="emp_id" :value="__('Employee')" :required="true" />
                                <x-select-input id="emp_id" name="emp_id" class="mt-1 block w-full"
                                    :options="$employees"
                                    :selected="old('emp_id', $empLoan->emp_id ?? $selectedEmpId)"
                                    placeholder="{{ __('Select Employee') }}" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('emp_id')" />

                                <!-- Info Badge Jabatan & Proyek Karyawan -->
                                <div id="emp-info-box" class="mt-2.5 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200 shadow-2xs">
                                        <i class="fa-solid fa-briefcase text-indigo-500"></i>
                                        Jabatan: <strong id="emp-jabatan-text" class="text-slate-900">-</strong>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200 shadow-2xs">
                                        <i class="fa-solid fa-diagram-project text-blue-500"></i>
                                        Proyek Karyawan: <strong id="emp-project-text" class="text-blue-900">-</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Project ID & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <x-input-label for="project_id" :value="__('Project ID / Proyek')" />
                                <x-select-input id="project_id" name="project_id" class="mt-1 block w-full"
                                    :options="$projects"
                                    :selected="old('project_id', $empLoan->project_id ?? ($defaultProjectId ?? ''))"
                                    placeholder="{{ __('Pilih Proyek (Opsional)') }}" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('project_id')" />
                                <p class="text-[11px] text-gray-500 mt-1">Otomatis menyesuaikan penugasan proyek karyawan atau dapat dipilih secara manual.</p>
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" :required="true" />
                                <x-select-input id="status" name="status" class="mt-1 block w-full"
                                    :options="['open' => 'Open', 'closed' => 'Closed']"
                                    :selected="old('status', $empLoan->status ?? 'open')" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('status')" />
                            </div>
                        </div>

                        <!-- Row 3: Amount & Duration -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <x-input-label for="amount" :value="__('Jumlah Pinjaman (Rp)')" :required="true" />
                                <x-currency-input id="amount" name="amount" class="mt-1 block w-full"
                                    :value="old('amount', $empLoan->amount)" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('amount')" />
                            </div>

                            <div>
                                <x-input-label for="duration" :value="__('Durasi (Bulan)')" :required="true" />
                                <x-text-input id="duration" name="duration" type="number" min="1" class="mt-1 block w-full"
                                    :value="old('duration', $empLoan->duration)" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('duration')" />
                            </div>
                        </div>

                        <!-- Row 4: Installment Amount (Auto) & Start Date -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <x-input-label for="installment_amount" :value="__('Cicilan Per Bulan (Auto-Calculated)')" />
                                <x-currency-input id="installment_amount" name="installment_amount" class="mt-1 block w-full bg-gray-50 text-gray-700 font-medium"
                                    :value="old('installment_amount', $empLoan->installment_amount)" readonly />
                                <x-input-error class="mt-1.5" :messages="$errors->get('installment_amount')" />
                            </div>

                            <div>
                                <x-month-year-selector
                                    label="Tanggal Mulai Cicilan (Payroll)"
                                    namePrefix="start"
                                    :defaultMonth="$empLoan->start_date ? $empLoan->start_date->format('m') : date('m')"
                                    :defaultYear="$empLoan->start_date ? $empLoan->start_date->format('Y') : date('Y')"
                                />
                                <x-input-error class="mt-1.5" :messages="$errors->get('start_date')" />
                            </div>
                        </div>

                        <!-- Row 5: Description -->
                        <div>
                            <x-input-label for="description" :value="__('Keterangan / Keperluan Pinjaman')" />
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs text-sm"
                                placeholder="Tuliskan keterangan detail keperluan pinjaman karyawan...">{{ old('description', $empLoan->description) }}</textarea>
                            <x-input-error class="mt-1.5" :messages="$errors->get('description')" />
                        </div>

                        <!-- Row 6: Attachment / Lampiran Dokumen -->
                        <div class="pt-3 border-t border-gray-100">
                            <x-input-label :value="__('Attachment / Berkas Lampiran')" />
                            <div class="mt-2 p-4 border border-dashed border-gray-300 rounded-xl bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 shadow-xs">
                                        <i class="fa-solid fa-paperclip text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-800" id="attachmentLabel">
                                            @if($empLoan->attachment)
                                                Berkas terlampir: {{ basename($empLoan->attachment) }}
                                            @else
                                                Unggah dokumen persetujuan / form pengajuan pinjaman
                                            @endif
                                        </p>
                                        <p class="text-[11px] text-gray-500" id="fileInfoText">
                                            Format: PDF, JPG, PNG, DOCX, XLSX (Maks. 5 MB)
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($empLoan->attachment)
                                        <a href="{{ route('emp-loans.download-attachment', $empLoan->id) }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-xs font-semibold shadow-xs transition">
                                            <i class="fa-solid fa-download text-xs text-blue-600"></i>
                                            Unduh Lampiran
                                        </a>
                                    @endif

                                    <label for="attachmentInput" class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                                        <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i>
                                        <span>{{ $empLoan->attachment ? 'Ganti Berkas' : 'Pilih Berkas' }}</span>
                                    </label>
                                    <input type="file" id="attachmentInput" name="attachment" class="hidden"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                        onchange="
                                            if (this.files && this.files[0]) {
                                                const file = this.files[0];
                                                if (file.size > 5 * 1024 * 1024) {
                                                    alert('Ukuran berkas melebihi batas maksimal 5 MB (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)');
                                                    this.value = '';
                                                    document.getElementById('fileInfoText').textContent = 'Format: PDF, JPG, PNG, DOCX, XLSX (Maks. 5 MB)';
                                                } else {
                                                    document.getElementById('attachmentLabel').textContent = file.name;
                                                    document.getElementById('fileInfoText').textContent = (file.size / 1024).toFixed(1) + ' KB - Siap diunggah';
                                                }
                                            }
                                        " />
                                </div>
                            </div>
                            <x-input-error class="mt-1.5" :messages="$errors->get('attachment')" />
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-5 border-t border-gray-100 flex items-center justify-end gap-3">
                            <a href="{{ $empLoan->exists 
                                    ? route('emp-loans.index', ['emp_id' => $empLoan->emp_id]) 
                                    : ($selectedEmpId ? route('emp-loans.index', ['emp_id' => $selectedEmpId]) : route('emp-loans.index')) }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition">
                                {{ __('Batal') }}
                            </a>

                            <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg font-semibold text-xs uppercase tracking-widest shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition">
                                <i class="fa-solid fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : '{{ __('Simpan Pinjaman') }}'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amount');
            const durationInput = document.getElementById('duration');
            const installmentInput = document.getElementById('installment_amount');
            const startMonthSelect = document.getElementById('start_month');
            const startYearSelect = document.getElementById('start_year');
            const startDateInput = document.getElementById('start_date');

            const employeeProjects = @json($employeeProjects ?? []);

            function updateStartDate() {
                if (!startMonthSelect || !startYearSelect || !startDateInput) return;
                const month = startMonthSelect.value;
                const year = startYearSelect.value;
                if (month && year) {
                    startDateInput.value = `${year}-${String(month).padStart(2, '0')}-01`;
                } else {
                    startDateInput.value = '';
                }
            }

            function calculateInstallment() {
                if (!amountInput || !durationInput || !installmentInput) return;
                const amount = parseFloat(amountInput.value.replace(/[^0-9.-]+/g, '')) || 0;
                const duration = parseInt(durationInput.value) || 0;

                if (amount > 0 && duration > 0) {
                    const installment = amount / duration;
                    installmentInput.value = installment.toFixed(2);
                    installmentInput.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    installmentInput.value = '';
                    installmentInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            if (amountInput) amountInput.addEventListener('input', calculateInstallment);
            if (durationInput) durationInput.addEventListener('input', calculateInstallment);
            if (startMonthSelect) startMonthSelect.addEventListener('change', updateStartDate);
            if (startYearSelect) startYearSelect.addEventListener('change', updateStartDate);

            const employeeDetails = @json($employeeDetails ?? []);

            function updateEmployeeInfo(empId, syncProject = true) {
                if (empId && employeeDetails[empId]) {
                    const emp = employeeDetails[empId];
                    $('#emp-jabatan-text').text(emp.jabatan || '-');
                    $('#emp-project-text').text(emp.project_name || '-');

                    // Otomatis sinkronkan project_id dari data employee
                    if (syncProject && emp.project_id) {
                        $('#project_id').val(emp.project_id).trigger('change');
                    }
                } else {
                    $('#emp-jabatan-text').text('-');
                    $('#emp-project-text').text('-');
                }
            }

            // Saat dropdown employee berubah: update jabatan & otomatis sinkronkan proyek dari data employee
            $('#emp_id').on('change', function() {
                const empId = $(this).val();
                updateEmployeeInfo(empId, true);
            });

            // Inisialisasi awal saat form dimuat
            const initialEmpId = $('#emp_id').val();
            if (initialEmpId) {
                const hasProjectSelected = Boolean($('#project_id').val());
                updateEmployeeInfo(initialEmpId, !hasProjectSelected);
            }

            updateStartDate();
        });
    </script>
    @endpush
</x-app-layout>
