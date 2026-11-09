@props([
    'name' => 'project_id',
    'id' => null,
    'selected' => null,
    'value' => null,
    'projects' => null,
    'placeholder' => 'Pilih Project (Opsional)',
    'required' => false,
    'disabled' => false,
    'allowClear' => true,
    'companyId' => null,
    'modalTitle' => 'Pilih Project',
])

@php
    $inputId = $id ?? $name;
    $selectedValue = old($name, $selected ?? $value ?? null);
    $selectedId = $selectedValue !== null && $selectedValue !== '' ? (string)$selectedValue : '';

    if ($projects === null) {
        $projectList = \App\Models\Project::with('company')->orderBy('name')->get();
    } else {
        $projectList = $projects;
    }

    $projectItems = collect($projectList)->map(function ($p) {
        if (is_array($p)) {
            $company = $p['company'] ?? [];
            return [
                'id'           => (string) ($p['id'] ?? ''),
                'name'         => (string) ($p['name'] ?? ''),
                'no_project'   => (string) ($p['no_project'] ?? '-'),
                'vendor'       => (string) ($p['vendor'] ?? ''),
                'status'       => (string) ($p['status'] ?? ''),
                'company_id'   => (string) ($p['company_id'] ?? ($company['id'] ?? '')),
                'company_code' => (string) ($company['code'] ?? ($p['company_code'] ?? '')),
                'company_name' => (string) ($company['name'] ?? ($p['company_name'] ?? '')),
                'start_date'   => !empty($p['start_date']) ? substr((string)$p['start_date'], 0, 10) : '',
                'end_date'     => !empty($p['end_date']) ? substr((string)$p['end_date'], 0, 10) : '',
            ];
        }

        return [
            'id'           => (string) $p->id,
            'name'         => (string) $p->name,
            'no_project'   => (string) ($p->no_project ?: '-'),
            'vendor'       => (string) ($p->vendor ?: ''),
            'status'       => (string) ($p->status ?: ''),
            'company_id'   => (string) ($p->company_id ?: ''),
            'company_code' => (string) ($p->company?->code ?: ''),
            'company_name' => (string) ($p->company?->name ?: ''),
            'start_date'   => $p->start_date ? $p->start_date->format('d/m/Y') : '',
            'end_date'     => $p->end_date ? $p->end_date->format('d/m/Y') : '',
        ];
    })->values()->toArray();

    $initialSelectedProject = null;
    if (!empty($selectedId)) {
        foreach ($projectItems as $item) {
            if ($item['id'] === $selectedId) {
                $initialSelectedProject = $item;
                break;
            }
        }
    }
@endphp

<div x-data="projectModalPickerComponent({
        inputId: @js($inputId),
        name: @js($name),
        selectedId: @js($selectedId),
        initialProject: @js($initialSelectedProject),
        projects: @js($projectItems),
        allowClear: @js($allowClear),
        required: @js($required),
        disabled: @js($disabled),
        defaultCompany: @js($companyId ? (string)$companyId : '')
    })"
    class="relative w-full"
    @set-project.window="if (!$event.detail.name || $event.detail.name === '{{ $name }}') { selectById($event.detail.id); }"
    @set-company-filter.window="if (!$event.detail.name || $event.detail.name === '{{ $name }}') { companyFilter = $event.detail.companyId || ''; }"
    x-cloak
>
    {{-- Hidden form input holding project_id value --}}
    <input type="hidden"
        name="{{ $name }}"
        id="{{ $inputId }}"
        x-ref="hiddenInput"
        :value="selectedId"
        {{ $required ? 'required' : '' }}
        {{ $attributes->except(['class', 'name', 'id']) }} />

    {{-- TRIGGER: KETIKA BELUM ADA PROJECT TERPILIH --}}
    <div x-show="!selectedId"
        @click="!disabled && openModal()"
        class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-gray-500 hover:border-blue-500 hover:bg-blue-50/20 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition cursor-pointer group shadow-2xs"
        :class="disabled ? 'opacity-60 cursor-not-allowed bg-gray-50' : ''">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-blue-100 text-slate-500 group-hover:text-blue-600 flex items-center justify-center transition shrink-0">
                <i class="fa-solid fa-diagram-project text-xs"></i>
            </span>
            <span class="text-xs sm:text-sm text-gray-400 group-hover:text-gray-600 truncate italic">
                {{ $placeholder }}
            </span>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 group-hover:bg-blue-600 text-slate-700 group-hover:text-white transition shadow-2xs shrink-0">
            <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
            <span>Pilih Project</span>
        </span>
    </div>

    {{-- TRIGGER: KETIKA PROJECT SUDAH TERPILIH --}}
    <div x-show="selectedId"
        class="w-full flex items-center justify-between p-2.5 bg-white border border-blue-200 rounded-xl hover:border-blue-400 transition shadow-2xs group">
        <div class="flex items-center gap-3 min-w-0 pr-2">
            <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-diagram-project text-sm"></i>
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <template x-if="selectedProject?.company_code">
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200"
                            x-text="selectedProject?.company_code"></span>
                    </template>
                    <span class="text-xs sm:text-sm font-semibold text-gray-900 truncate"
                        x-text="selectedProject?.name"></span>
                </div>
                <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5 flex-wrap">
                    <template x-if="selectedProject?.no_project && selectedProject?.no_project !== '-'">
                        <span class="font-mono text-gray-600">
                            <i class="fa-solid fa-hashtag text-[10px] text-gray-400"></i>
                            <span x-text="selectedProject?.no_project"></span>
                        </span>
                    </template>
                    <template x-if="selectedProject?.vendor">
                        <span class="text-gray-400">•</span>
                    </template>
                    <template x-if="selectedProject?.vendor">
                        <span class="truncate">
                            <i class="fa-solid fa-building text-[10px] text-gray-400"></i>
                            <span x-text="selectedProject?.vendor"></span>
                        </span>
                    </template>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <button type="button" @click="!disabled && openModal()"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 hover:border-transparent transition cursor-pointer"
                title="Ganti Project">
                <i class="fa-solid fa-arrow-right-arrow-left text-[10px]"></i>
                <span class="hidden sm:inline">Ganti</span>
            </button>
            <template x-if="allowClear && !required && !disabled">
                <button type="button" @click="clearSelection()"
                    class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                    title="Hapus Pilihan">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </template>
        </div>
    </div>

    {{-- MODAL PICKER DIALOG (TELEPORTED TO BODY) --}}
    <template x-teleport="body">
        <div x-show="isOpen"
            x-cloak
            class="fixed inset-0 z-[999] overflow-y-auto"
            aria-labelledby="project-modal-title-{{ $inputId }}"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="closeModal()">

            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                x-show="isOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeModal()"></div>

            <!-- Modal Window -->
            <div class="flex min-h-screen items-center justify-center p-3 text-center sm:p-6">
                <div x-show="isOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl border border-gray-100 transition-all flex flex-col max-h-[88vh]">

                    <!-- Header -->
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-base shadow-2xs">
                                <i class="fa-solid fa-diagram-project"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="project-modal-title-{{ $inputId }}">
                                    {{ $modalTitle }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Cari dan pilih data project dari daftar aktif di bawah ini
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal()"
                            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-200/60 transition cursor-pointer"
                            title="Tutup (ESC)">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Toolbar / Search & Filter -->
                    <div class="p-4 sm:p-5 bg-white border-b border-gray-100 space-y-3 shrink-0">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <!-- Live Search Box -->
                            <div class="sm:col-span-6 relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input type="text"
                                    x-model="search"
                                    x-ref="searchInput"
                                    placeholder="Cari nama project, no. project, vendor..."
                                    class="w-full pl-9 pr-9 py-2 text-xs sm:text-sm bg-gray-50 hover:bg-white focus:bg-white border border-gray-200 focus:border-blue-500 rounded-xl focus:ring-2 focus:ring-blue-500/20 transition" />
                                <button type="button" x-show="search.length > 0" @click="search = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs cursor-pointer">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <!-- Filter Company -->
                            <div class="sm:col-span-3">
                                <select x-model="companyFilter"
                                    class="w-full py-2 px-3 text-xs sm:text-sm bg-gray-50 hover:bg-white focus:bg-white border border-gray-200 focus:border-blue-500 rounded-xl focus:ring-2 focus:ring-blue-500/20 transition">
                                    <option value="">Semua Perusahaan</option>
                                    <template x-for="c in uniqueCompanies" :key="c.id">
                                        <option :value="c.id" x-text="c.code + ' - ' + c.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Filter Status -->
                            <div class="sm:col-span-3">
                                <select x-model="statusFilter"
                                    class="w-full py-2 px-3 text-xs sm:text-sm bg-gray-50 hover:bg-white focus:bg-white border border-gray-200 focus:border-blue-500 rounded-xl focus:ring-2 focus:ring-blue-500/20 transition">
                                    <option value="">Semua Status</option>
                                    <option value="in_progress">Sedang Berjalan</option>
                                    <option value="invoice">Invoice</option>
                                    <option value="completed">Selesai</option>
                                    <option value="canceled">Dibatalkan</option>
                                </select>
                            </div>
                        </div>

                        <!-- Info bar -->
                        <div class="flex items-center justify-between text-xs text-gray-500 pt-0.5">
                            <div class="flex items-center gap-2">
                                <span>Menemukan: <strong class="text-gray-900" x-text="filteredProjects.length"></strong> project</span>
                                <span class="text-gray-300">|</span>
                                <span>Halaman <strong class="text-gray-900" x-text="page"></strong> dari <strong class="text-gray-900" x-text="totalPages"></strong></span>
                            </div>
                            <template x-if="search || companyFilter || statusFilter">
                                <button type="button" @click="resetFilters()"
                                    class="text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-rotate-left text-[11px]"></i> Reset Filter
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Scrollable Projects Table -->
                    <div class="overflow-y-auto flex-1 p-0 divide-y divide-gray-100">
                        <!-- Empty State -->
                        <template x-if="filteredProjects.length === 0">
                            <div class="py-12 px-4 text-center">
                                <div class="w-12 h-12 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-3">
                                    <i class="fa-solid fa-folder-open text-xl"></i>
                                </div>
                                <h4 class="text-sm font-bold text-gray-800">Tidak ada data project yang sesuai</h4>
                                <p class="text-xs text-gray-500 mt-1">Coba gunakan kata kunci lain atau reset filter pencarian Anda.</p>
                                <button type="button" @click="resetFilters()"
                                    class="mt-3.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                                    Reset Pencarian
                                </button>
                            </div>
                        </template>

                        <!-- Table -->
                        <template x-if="filteredProjects.length > 0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                            <th class="py-3 px-4">No. Proyek</th>
                                            <th class="py-3 px-4">Nama Project</th>
                                            <th class="py-3 px-4">Perusahaan</th>
                                            <th class="py-3 px-4">Vendor / Klien</th>
                                            <th class="py-3 px-4">Status & Periode</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-xs">
                                        <template x-for="proj in paginatedProjects" :key="proj.id">
                                            <tr @click="selectProject(proj)"
                                                class="hover:bg-blue-50/50 transition cursor-pointer group"
                                                :class="String(proj.id) === String(selectedId) ? 'bg-blue-50/80 font-medium' : ''">
                                                <!-- No. Proyek -->
                                                <td class="py-3 px-4 whitespace-nowrap">
                                                    <span class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"
                                                        x-text="proj.no_project || '-'"></span>
                                                </td>

                                                <!-- Nama Project -->
                                                <td class="py-3 px-4">
                                                    <div class="font-bold text-gray-900 group-hover:text-blue-600 transition" x-text="proj.name"></div>
                                                </td>

                                                <!-- Perusahaan -->
                                                <td class="py-3 px-4 whitespace-nowrap">
                                                    <template x-if="proj.company_code">
                                                        <span class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded"
                                                            :title="proj.company_name"
                                                            x-text="proj.company_code"></span>
                                                    </template>
                                                    <template x-if="!proj.company_code">
                                                        <span class="text-gray-400">-</span>
                                                    </template>
                                                </td>

                                                <!-- Vendor / Klien -->
                                                <td class="py-3 px-4 text-gray-600">
                                                    <span x-text="proj.vendor || '-'"></span>
                                                </td>

                                                <!-- Status & Periode -->
                                                <td class="py-3 px-4 whitespace-nowrap">
                                                    <div class="flex flex-col gap-1">
                                                        <div>
                                                            <template x-if="proj.status === 'in_progress'">
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Sedang Berjalan</span>
                                                            </template>
                                                            <template x-if="proj.status === 'invoice'">
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">Invoice</span>
                                                            </template>
                                                            <template x-if="proj.status === 'completed'">
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">Selesai</span>
                                                            </template>
                                                            <template x-if="proj.status === 'canceled'">
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">Dibatalkan</span>
                                                            </template>
                                                            <template x-if="!['in_progress','invoice','completed','canceled'].includes(proj.status)">
                                                                <span class="text-gray-400" x-text="proj.status || '-'"></span>
                                                            </template>
                                                        </div>
                                                        <template x-if="proj.start_date || proj.end_date">
                                                            <span class="text-[10px] text-gray-400">
                                                                <span x-text="proj.start_date"></span> s/d <span x-text="proj.end_date"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>

                                                <!-- Aksi -->
                                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                                    <template x-if="String(proj.id) === String(selectedId)">
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 text-white shadow-2xs">
                                                            <i class="fa-solid fa-check text-[11px]"></i> Terpilih
                                                        </span>
                                                    </template>
                                                    <template x-if="String(proj.id) !== String(selectedId)">
                                                        <button type="button" @click.stop="selectProject(proj)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white border border-blue-200 group-hover:border-transparent transition shadow-2xs cursor-pointer">
                                                            <span>Pilih</span>
                                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                        </button>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-3.5 bg-slate-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                        <!-- Pagination -->
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                @click="page = Math.max(1, page - 1)"
                                :disabled="page <= 1"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer">
                                <i class="fa-solid fa-chevron-left text-[10px] mr-1"></i> Prev
                            </button>

                            <div class="flex items-center gap-1 px-2 text-xs font-medium text-gray-600">
                                <span x-text="page"></span> / <span x-text="totalPages"></span>
                            </div>

                            <button type="button"
                                @click="page = Math.min(totalPages, page + 1)"
                                :disabled="page >= totalPages"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer">
                                Next <i class="fa-solid fa-chevron-right text-[10px] ml-1"></i>
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <template x-if="allowClear && selectedId">
                                <button type="button" @click="clearSelection(); closeModal()"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50 border border-rose-200 transition cursor-pointer">
                                    <i class="fa-solid fa-xmark mr-1"></i> Kosongkan Pilihan
                                </button>
                            </template>
                            <button type="button" @click="closeModal()"
                                class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 transition cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>

@once
<script>
    function projectModalPickerComponent(config) {
        return {
            isOpen: false,
            search: '',
            companyFilter: config.defaultCompany || '',
            statusFilter: '',
            selectedId: config.selectedId ? String(config.selectedId) : '',
            selectedProject: config.initialProject || null,
            projects: config.projects || [],
            allowClear: config.allowClear !== false,
            required: !!config.required,
            disabled: !!config.disabled,
            page: 1,
            perPage: 8,

            init() {
                if (this.selectedId && !this.selectedProject) {
                    this.updateSelectedProject();
                }

                this.$watch('search', () => { this.page = 1; });
                this.$watch('companyFilter', () => { this.page = 1; });
                this.$watch('statusFilter', () => { this.page = 1; });
            },

            updateSelectedProject() {
                if (!this.selectedId) {
                    this.selectedProject = null;
                    return;
                }
                this.selectedProject = this.projects.find(p => String(p.id) === String(this.selectedId)) || null;
            },

            get filteredProjects() {
                let list = this.projects;

                if (this.companyFilter) {
                    list = list.filter(p => String(p.company_id) === String(this.companyFilter) || p.company_code === this.companyFilter);
                }

                if (this.statusFilter) {
                    list = list.filter(p => (p.status || '').toLowerCase() === this.statusFilter.toLowerCase());
                }

                if (this.search && this.search.trim()) {
                    const q = this.search.trim().toLowerCase();
                    list = list.filter(p => {
                        const name = (p.name || '').toLowerCase();
                        const no = (p.no_project || '').toLowerCase();
                        const vendor = (p.vendor || '').toLowerCase();
                        const comp = ((p.company_code || '') + ' ' + (p.company_name || '')).toLowerCase();
                        return name.includes(q) || no.includes(q) || vendor.includes(q) || comp.includes(q);
                    });
                }

                return list;
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredProjects.length / this.perPage));
            },

            get paginatedProjects() {
                const start = (this.page - 1) * this.perPage;
                return this.filteredProjects.slice(start, start + this.perPage);
            },

            get uniqueCompanies() {
                const map = new Map();
                this.projects.forEach(p => {
                    if (p.company_id && p.company_code) {
                        map.set(String(p.company_id), { id: String(p.company_id), code: p.company_code, name: p.company_name });
                    }
                });
                return Array.from(map.values());
            },

            openModal() {
                if (this.disabled) return;
                this.isOpen = true;
                this.search = '';
                this.page = 1;
                document.body.classList.add('overflow-y-hidden');

                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        setTimeout(() => this.$refs.searchInput.focus(), 120);
                    }
                });
            },

            closeModal() {
                this.isOpen = false;
                document.body.classList.remove('overflow-y-hidden');
            },

            selectProject(project) {
                this.selectedId = String(project.id);
                this.selectedProject = project;
                this.closeModal();
                this.dispatchChangeEvent();
            },

            selectById(id) {
                this.selectedId = id ? String(id) : '';
                this.updateSelectedProject();
            },

            clearSelection() {
                this.selectedId = '';
                this.selectedProject = null;
                this.dispatchChangeEvent();
            },

            resetFilters() {
                this.search = '';
                this.companyFilter = '';
                this.statusFilter = '';
                this.page = 1;
            },

            dispatchChangeEvent() {
                this.$nextTick(() => {
                    const inputEl = this.$refs.hiddenInput;
                    if (inputEl) {
                        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                        inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    window.dispatchEvent(new CustomEvent('project-selected', {
                        detail: {
                            id: this.selectedId,
                            project: this.selectedProject,
                            name: inputEl ? inputEl.name : 'project_id'
                        }
                    }));
                });
            }
        };
    }
</script>
@endonce
