<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if ($message = Session::get('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-xs flex items-center gap-2" role="alert">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span class="font-medium text-xs">{{ $message }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-xs" role="alert">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-circle-xmark text-red-600"></i>
                        <span class="font-semibold text-xs">Terjadi Kesalahan:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Header & Action Bar -->
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-clipboard-list text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Master Jenis Izin Kerja</h1>
                            <p class="text-xs text-gray-500 mt-0.5">Kelola tipe perizinan kerja karyawan (urusan pribadi, dinas luar kota, datang terlambat, dispensasi resmi, dll.).</p>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="openCreateModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Tambah Jenis Izin
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 font-semibold text-gray-600">
                            <tr>
                                <th class="px-5 py-3 text-left">Nama Jenis Izin</th>
                                <th class="px-5 py-3 text-left">Kode</th>
                                <th class="px-5 py-3 text-center">Batas Maks. Hari</th>
                                <th class="px-5 py-3 text-center">Skema Upah</th>
                                <th class="px-5 py-3 text-center">Wajib Lampiran</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($types as $type)
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-gray-900">{{ $type->name }}</div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ $type->description ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-gray-600">
                                        {{ $type->code }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-blue-700 text-sm">
                                        @if($type->max_days)
                                            {{ (float) $type->max_days }} <span class="text-xs font-normal text-gray-400">Hari</span>
                                        @else
                                            <span class="text-xs font-medium text-gray-400 italic">Bebas</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($type->is_paid)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fa-solid fa-coins text-[10px]"></i> Dibayar Penuh (Paid)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="fa-solid fa-file-invoice-dollar text-[10px]"></i> Potong Gaji/Cuti (Unpaid)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($type->requires_attachment)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="fa-solid fa-paperclip text-[10px]"></i> Wajib Bukti
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">Opsional</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($type->is_active)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-500">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" data-item="{{ htmlspecialchars(json_encode($type), ENT_QUOTES, 'UTF-8') }}" onclick="editType(JSON.parse(this.dataset.item))"
                                                class="px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition" title="Edit Tipe Izin">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>

                                            @if($type->leave_requests_count == 0)
                                                <form action="{{ route('permit-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Hapus tipe izin: {{ addslashes($type->name) }}?');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition" title="Hapus">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="px-2 py-1 text-[11px] text-gray-400 bg-gray-50 rounded-lg border border-gray-100" title="Sudah digunakan dalam {{ $type->leave_requests_count }} pengajuan izin">
                                                    <i class="fa-solid fa-lock text-[10px]"></i> Digunakan
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fa-solid fa-clipboard-question text-3xl mb-2 text-gray-300"></i>
                                            <span class="font-medium">Belum ada jenis izin yang terdaftar.</span>
                                            <button type="button" onclick="openCreateModal()" class="mt-3 text-xs text-blue-600 hover:underline font-semibold">
                                                + Tambah Jenis Izin Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form (Tambah / Edit) -->
    <div id="typeModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeTypeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="typeForm" method="POST" action="{{ route('permit-types.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <h3 class="text-base font-bold text-gray-900" id="modalTitle">Tambah Jenis Izin Baru</h3>
                            <button type="button" onclick="closeTypeModal()" class="text-gray-400 hover:text-gray-500 text-lg">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <x-input-label for="name" :value="__('Nama Jenis Izin')" :required="true" />
                                <x-text-input id="name" name="name" type="text" placeholder="Contoh: Izin Keperluan Pribadi" class="mt-1 block w-full" required />
                                <x-input-error class="mt-1" :messages="$errors->get('name')" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="code" :value="__('Kode Unik')" :required="true" />
                                    <x-text-input id="code" name="code" type="text" placeholder="personal, duty, late" class="mt-1 block w-full lowercase" required />
                                    <x-input-error class="mt-1" :messages="$errors->get('code')" />
                                </div>
                                <div>
                                    <x-input-label for="max_days" :value="__('Batas Maks. Hari (Opsional)')" />
                                    <x-text-input id="max_days" name="max_days" type="number" step="0.5" min="0.5" placeholder="Kosongkan jika bebas" class="mt-1 block w-full" />
                                    <x-input-error class="mt-1" :messages="$errors->get('max_days')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="description" :value="__('Keterangan / Ketentuan')" />
                                <textarea id="description" name="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs text-xs" placeholder="Deskripsi atau petunjuk pengajuan izin ini..."></textarea>
                            </div>

                            <div class="pt-2 space-y-2.5 border-t border-gray-100">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="is_paid" name="is_paid" value="1" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    <span class="text-xs font-medium text-gray-700">Izin Dibayar Penuh (Paid / Dispensasi Resmi, tidak potong gaji)</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="requires_attachment" name="requires_attachment" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    <span class="text-xs font-medium text-gray-700">Wajib melampirkan berkas bukti (surat tugas, undangan, kartu ujian, dll.)</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    <span class="text-xs font-medium text-gray-700">Status Aktif (dapat dipilih di formulir izin ESS karyawan)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" onclick="closeTypeModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                            Simpan Jenis Izin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openCreateModal() {
                $('#typeForm').attr('action', "{{ route('permit-types.store') }}");
                $('#formMethod').val('POST');
                $('#modalTitle').text('Tambah Jenis Izin Baru');
                $('#name').val('');
                $('#code').val('');
                $('#max_days').val('');
                $('#description').val('');
                $('#is_paid').prop('checked', true);
                $('#requires_attachment').prop('checked', false);
                $('#is_active').prop('checked', true);
                $('#typeModal').removeClass('hidden');
            }

            function editType(item) {
                $('#typeForm').attr('action', "{{ url('permit-types') }}/" + item.id);
                $('#formMethod').val('PUT');
                $('#modalTitle').text('Edit Jenis Izin: ' + item.name);
                $('#name').val(item.name);
                $('#code').val(item.code);
                $('#max_days').val(item.max_days ? parseFloat(item.max_days) : '');
                $('#description').val(item.description || '');
                $('#is_paid').prop('checked', !!item.is_paid);
                $('#requires_attachment').prop('checked', !!item.requires_attachment);
                $('#is_active').prop('checked', !!item.is_active);
                $('#typeModal').removeClass('hidden');
            }

            function closeTypeModal() {
                $('#typeModal').addClass('hidden');
            }
        </script>
    @endpush
</x-app-layout>

