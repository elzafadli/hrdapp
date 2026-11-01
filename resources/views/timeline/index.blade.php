<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-extrabold text-xl text-gray-900 tracking-tight flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-100 text-blue-600">
                        <i class="fa-solid fa-newspaper text-sm"></i>
                    </span>
                    <span>Timeline Perusahaan</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Informasi kehadiran, cuti, izin, dan hari bahagia rekan kerja.</p>
            </div>

            {{-- Quick Filter Chips --}}
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-xl border border-gray-200 self-start sm:self-auto text-xs font-semibold">
                <a href="{{ route('timeline.index', ['tab' => 'all']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $filterTab === 'all' ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fa-solid fa-list-ul mr-1"></i> Semua
                </a>
                <a href="{{ route('timeline.index', ['tab' => 'leave']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $filterTab === 'leave' ? 'bg-white text-amber-600 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fa-regular fa-calendar-days mr-1"></i> Izin & Cuti
                </a>
                <a href="{{ route('timeline.index', ['tab' => 'birthday']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $filterTab === 'birthday' ? 'bg-white text-rose-600 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fa-solid fa-cake-candles mr-1"></i> Ulang Tahun
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6 bg-slate-50 min-h-screen">
        <div class="max-w-2xl mx-auto px-3 sm:px-4 space-y-4">

            {{-- Notice Ringkas Hari Ini (Jika ada aktivitas hari ini) --}}
            @if($todayLeaveCount > 0 || $todayBdayCount > 0)
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-4 text-white shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-lg">
                            <i class="fa-solid fa-bullhorn text-white"></i>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-blue-100">Hari ini • {{ \Carbon\Carbon::today()->translatedFormat('d F Y') }}</div>
                            <div class="text-sm font-bold mt-0.5">
                                @if($todayLeaveCount > 0 && $todayBdayCount > 0)
                                    {{ $todayLeaveCount }} Rekan Izin/Cuti & {{ $todayBdayCount }} Rekan Ulang Tahun
                                @elseif($todayLeaveCount > 0)
                                    {{ $todayLeaveCount }} Rekan Kerja Sedang Izin / Cuti
                                @else
                                    {{ $todayBdayCount }} Rekan Kerja Berulang Tahun Hari Ini! 🎉
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Feed List --}}
            @forelse($feed as $post)
                <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-gray-200/70 hover:shadow-sm transition">
                    {{-- Header Kartu (Perusahaan & Waktu) --}}
                    <div class="flex items-center gap-3">
                        {{-- Avatar / Inisial Perusahaan --}}
                        <div class="w-10 h-10 rounded-full border border-gray-200 bg-blue-50 text-blue-600 flex items-center justify-center font-extrabold text-xs shrink-0 tracking-wider">
                            {{ $post['company']?->code ?? 'TJTM' }}
                        </div>

                        {{-- Nama Perusahaan & Waktu Relatif --}}
                        <div class="flex flex-col min-w-0">
                            <span class="text-sm font-extrabold text-gray-900 tracking-tight truncate leading-tight uppercase">
                                {{ $post['company']?->name ?? 'PT TIARA JAYA TUNGGAL MANDIRI' }}
                            </span>
                            <span class="text-[11px] italic text-gray-400 font-normal mt-0.5">
                                {{ $post['relative_time'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Notice Box (Kuning untuk Cuti/Izin, Pink untuk Ulang Tahun) --}}
                    @if($post['type'] === 'leave')
                        <div class="bg-amber-50/80 border border-amber-200/70 rounded-xl p-3 my-3.5 flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-100/90 text-amber-700 flex items-center justify-center shrink-0 text-xs mt-0.5">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div class="text-xs text-amber-950 font-medium leading-relaxed pt-0.5">
                                @if($post['is_today'])
                                    Pada hari ini tanggal <span class="font-bold font-mono">{{ $post['date']->format('d/m/Y') }}</span>, terdapat {{ $post['count'] > 1 ? $post['count'] . ' karyawan yang melakukan izin / cuti antara lain :' : 'karyawan yang melakukan izin / cuti :' }}
                                @else
                                    Pada tanggal <span class="font-bold font-mono">{{ $post['date']->format('d/m/Y') }}</span>, terdapat {{ $post['count'] > 1 ? $post['count'] . ' karyawan yang melakukan izin / cuti antara lain :' : 'karyawan yang melakukan izin / cuti :' }}
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Birthday Notice --}}
                        <div class="bg-rose-50/80 border border-rose-200/70 rounded-xl p-3 my-3.5 flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-rose-100/90 text-rose-600 flex items-center justify-center shrink-0 text-xs mt-0.5">
                                <i class="fa-solid fa-cake-candles"></i>
                            </div>
                            <div class="text-xs text-rose-950 font-medium leading-relaxed pt-0.5">
                                @if($post['is_today'])
                                    Pada hari ini tanggal <span class="font-bold font-mono">{{ $post['date']->format('d/m/Y') }}</span>, terdapat {{ $post['count'] > 1 ? $post['count'] . ' karyawan yang merayakan ulang tahun antara lain :' : 'karyawan yang merayakan ulang tahun :' }}
                                @else
                                    Pada tanggal <span class="font-bold font-mono">{{ $post['date']->format('d/m/Y') }}</span>, terdapat {{ $post['count'] > 1 ? $post['count'] . ' karyawan yang merayakan ulang tahun antara lain :' : 'karyawan yang merayakan ulang tahun :' }}
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Daftar Karyawan --}}
                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden bg-gray-50/40">
                        @foreach($post['employees'] as $empItem)
                            <div class="p-3 sm:p-3.5 flex items-start sm:items-center gap-3 hover:bg-white transition">
                                {{-- Avatar Inisial / Foto Profil --}}
                                @if(!empty($empItem['photo']))
                                    <img src="{{ $empItem['photo'] }}" alt="{{ $empItem['name'] }}"
                                        class="w-10 h-10 rounded-xl object-cover shrink-0 border border-gray-200">
                                @else
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0 border {{ $empItem['avatar_color'] }}">
                                        {{ $empItem['initials'] }}
                                    </div>
                                @endif

                                {{-- Detail Karyawan & Badges --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 leading-tight truncate">
                                        {{ $empItem['name'] }}
                                    </h4>

                                    <div class="flex items-center text-xs text-gray-500 gap-1.5 mt-0.5">
                                        <i class="fa-regular fa-building text-gray-400 text-[11px]"></i>
                                        <span class="truncate">{{ $empItem['jabatan'] }}</span>
                                    </div>

                                    {{-- Badges --}}
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        @foreach($empItem['badges'] as $badge)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badge['badge_class'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot_class'] }}"></span>
                                                <span>{{ $badge['label'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                {{-- State Kosong --}}
                <div class="bg-white rounded-2xl p-8 text-center border border-gray-200 shadow-xs">
                    <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-2xl mx-auto mb-3">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Belum Ada Aktivitas Timeline</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 leading-relaxed">
                        Saat ini belum ada pengajuan cuti atau izin yang disetujui (approved) maupun karyawan yang berulang tahun dalam rentang waktu terbaru.
                    </p>
                    <div class="mt-4 flex justify-center gap-2">
                        <a href="{{ route('leave.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition">
                            <i class="fa-solid fa-plus mr-1.5"></i> Buat Pengajuan Cuti
                        </a>
                        <a href="{{ route('calendar.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 transition">
                            <i class="fa-regular fa-calendar-days mr-1.5"></i> Buka Kalender ESS
                        </a>
                    </div>
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
