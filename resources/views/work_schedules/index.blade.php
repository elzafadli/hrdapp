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
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-business-time text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Jadwal & Jam Kerja Kantor</h1>
                            <p class="text-xs text-gray-500 mt-0.5">Definisikan pola hari kerja, jam masuk, jam pulang, dan shift operasional karyawan.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('emp-schedules.index') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        Penugasan Karyawan
                    </a>
                    <a href="{{ route('work-schedules.create') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Tambah Jadwal Kerja
                    </a>
                </div>
            </div>

            <!-- Grid Kartu Jadwal Kerja -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($schedules as $item)
                    <div class="bg-white rounded-2xl shadow-xs border {{ $item->is_default ? 'border-indigo-300 ring-2 ring-indigo-50' : 'border-gray-100' }} p-6 flex flex-col justify-between space-y-5 transition hover:shadow-md">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-700 uppercase">
                                    {{ $item->code }}
                                </span>
                                @if($item->is_default)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-star text-[10px]"></i> Default Sistem
                                    </span>
                                @else
                                    <form action="{{ route('work-schedules.set-default', $item->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-medium text-gray-400 hover:text-indigo-600 transition">
                                            Jadikan Default
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <h3 class="font-bold text-gray-900 text-base mt-2.5">{{ $item->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $item->description ?: 'Tidak ada keterangan tambahan.' }}</p>

                            <!-- Weekly Day Matrix Pill -->
                            <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5">
                                <span class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Hari & Jam Operasional:</span>
                                @php
                                    $sortedDays = $item->days->sortBy('day_of_week');
                                @endphp
                                <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                    @foreach($sortedDays as $day)
                                        <div class="p-1.5 rounded-lg {{ $day->is_work_day ? 'bg-blue-50 border border-blue-200 text-blue-900 font-bold' : 'bg-gray-50 text-gray-400 border border-gray-100' }}">
                                            <div class="text-[10px] uppercase font-bold">{{ substr($day->day_name, 0, 3) }}</div>
                                            <div class="text-[9px] mt-0.5 {{ $day->is_work_day ? 'text-blue-700 font-mono font-medium' : 'text-gray-400' }}">
                                                {{ $day->is_work_day ? substr($day->start_time, 0, 5) : 'OFF' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Footer & Actions -->
                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-gray-500">
                                <i class="fa-solid fa-user-clock text-gray-400 mr-1"></i>
                                <b>{{ $item->employeeSchedules->count() }}</b> Karyawan Aktif
                            </span>

                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('work-schedules.edit', $item->id) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 border border-yellow-200 rounded-lg text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-[10px]"></i> Edit
                                </a>

                                @if(!$item->is_default && $item->employeeSchedules->count() === 0)
                                    <form action="{{ route('work-schedules.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal kerja ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center p-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs transition" title="Hapus">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-2xl p-8 text-center text-gray-400 border border-dashed border-gray-300">
                        <i class="fa-solid fa-calendar-xmark text-3xl mb-2"></i>
                        <p class="text-sm">Belum ada jadwal kerja yang dibuat.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>

