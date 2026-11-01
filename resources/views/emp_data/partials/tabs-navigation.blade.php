{{-- TAB NAVIGATION BAR (MANDIRI DENGAN WARNING DIRTY) --}}
<div class="bg-white rounded-2xl border border-gray-200 p-1.5 shadow-xs mb-6 flex flex-wrap items-center gap-1">

    {{-- TAB 1: DATA KARYAWAN --}}
    <button type="button" @click="trySwitchTab('data-karyawan')"
        class="flex-1 min-w-[130px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
        :class="activeTab === 'data-karyawan' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'">
        <i class="fa-regular fa-user"></i>
        <span>Data Karyawan</span>
    </button>

    {{-- TAB 2: PENUGASAN & PAYROLL (DI KANAN DATA KARYAWAN) --}}
    <button type="button" @click="trySwitchTab('penugasan-payroll')"
        class="flex-1 min-w-[150px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'penugasan-payroll' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-briefcase'"></i>
        <span>Penugasan & Payroll</span>
    </button>

    {{-- TAB 2: KARIR --}}
    <button type="button" @click="trySwitchTab('karir')"
        class="flex-1 min-w-[110px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'karir' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-briefcase'"></i>
        <span>Karir</span>
        <span x-show="careers.length > 0" class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
            :class="activeTab === 'karir' ? 'bg-white text-blue-700' : 'bg-blue-100 text-blue-700'"
            x-text="careers.length">
        </span>
    </button>

    {{-- TAB 3: LAMA BEKERJA --}}
    <button type="button" @click="trySwitchTab('lama-bekerja')"
        class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'lama-bekerja' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-regular fa-calendar-check'"></i>
        <span>Lama Bekerja</span>
    </button>

    {{-- TAB 4: PENDIDIKAN (BISA MULTI) --}}
    <button type="button" @click="trySwitchTab('pendidikan')"
        class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'pendidikan' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-graduation-cap'"></i>
        <span>Pendidikan</span>
        <span x-show="educations.length > 0" class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
            :class="activeTab === 'pendidikan' ? 'bg-white text-blue-700' : 'bg-emerald-100 text-emerald-700'"
            x-text="educations.length">
        </span>
    </button>

    {{-- TAB 5: PENGALAMAN KERJA (BISA MULTI) --}}
    <button type="button" @click="trySwitchTab('pengalaman-kerja')"
        class="flex-1 min-w-[145px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'pengalaman-kerja' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-building-user'"></i>
        <span>Pengalaman Kerja</span>
        <span x-show="experiences.length > 0"
            class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
            :class="activeTab === 'pengalaman-kerja' ? 'bg-white text-blue-700' : 'bg-indigo-100 text-indigo-700'"
            x-text="experiences.length">
        </span>
    </button>

    {{-- TAB 6: TRAINING & SKILL (BISA MULTI) --}}
    <button type="button" @click="trySwitchTab('pelatihan-skill')"
        class="flex-1 min-w-[140px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'pelatihan-skill' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-award'"></i>
        <span>Pelatihan & Skill</span>
        <span x-show="trainings.length > 0" class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
            :class="activeTab === 'pelatihan-skill' ? 'bg-white text-blue-700' : 'bg-amber-100 text-amber-700'"
            x-text="trainings.length">
        </span>
    </button>

    {{-- TAB: DATA BPJS --}}
    <button type="button" @click="trySwitchTab('bpjs')"
        class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
        :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'bpjs' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
        <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-shield-heart'"></i>
        <span>Data BPJS</span>
    </button>

</div>

