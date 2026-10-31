<x-app-layout>
    <div class="py-6" x-data="checkInApp()">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-5">

            <!-- Top Bar Navigasi -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Presensi Masuk</h2>
                        <p class="text-xs text-gray-400">Catat waktu kehadiran masuk kerja harian Anda</p>
                    </div>
                </div>

                <a href="{{ route('attendance.history') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                    Riwayat
                </a>
            </div>

            <!-- Notifikasi Pesan Sukses / Error -->
            <div x-show="message" 
                 x-transition
                 :class="isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'"
                 class="p-4 rounded-xl border text-sm flex items-start gap-3 shadow-sm"
                 style="display: none;">
                <i :class="isSuccess ? 'fa-solid fa-circle-check text-emerald-500' : 'fa-solid fa-circle-exclamation text-rose-500'" class="text-lg mt-0.5 shrink-0"></i>
                <div class="flex-1 font-medium text-xs leading-relaxed" x-text="message"></div>
                <button type="button" @click="message = ''" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Kartu Utama Presensi (White ESS Golden Style) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">
                
                <!-- 1. Real-Time Jam & Tanggal -->
                <div class="rounded-2xl p-5 bg-gradient-to-br from-blue-50/80 via-white to-blue-50/30 border border-blue-100/80 text-center space-y-1.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-100/70 text-blue-800 text-xs font-semibold">
                        <i class="fa-regular fa-calendar-days text-[11px]"></i>
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                    </span>
                    <div class="text-4xl font-extrabold text-gray-900 tracking-wider font-mono pt-1" x-text="currentTime">
                        --:--:--
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ $employee->name ?? auth()->user()->name }} 
                        @if($employee && $employee->jabatan)
                            · <span class="font-medium text-gray-700">{{ $employee->jabatan->jabatan }}</span>
                        @endif
                    </p>
                </div>

                <!-- 2. Shift Info -->
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div>
                            <span class="text-[11px] text-gray-400 block font-medium">Shift Kerja</span>
                            <span class="text-xs font-bold text-gray-800">{{ $shift['label'] }}</span>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 bg-white px-2.5 py-1 rounded-lg border border-gray-200">
                        {{ $shift['start_time'] }} - {{ $shift['end_time'] }}
                    </span>
                </div>

                <!-- 3. Info Lokasi Sekarang -->
                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100/70 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shadow-sm shrink-0">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="truncate">
                            <span class="text-[11px] text-gray-400 block font-medium">Lokasi Sekarang</span>
                            <span class="text-xs font-bold text-gray-900 truncate block" x-text="locationText">Mendeteksi lokasi...</span>
                            <span class="text-[10px] text-gray-500" x-show="coordsText" x-text="coordsText"></span>
                        </div>
                    </div>
                    <button type="button" @click="detectLocation()" 
                        class="px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold shrink-0 transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-arrows-rotate text-[11px] text-blue-600" :class="{ 'fa-spin': isDetecting }"></i>
                        Perbarui
                    </button>
                </div>

                <!-- Status Sudah Masuk -->
                @if($todayAttendance && $todayAttendance->check_in_time)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3 text-xs">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg shrink-0"></i>
                        <div>
                            <span class="font-bold block">Sudah Presensi Masuk</span>
                            <span>Tercatat pada pukul <strong>{{ substr($todayAttendance->check_in_time, 0, 5) }} WITA</strong> ({{ $todayAttendance->check_in_status === 'late' ? 'Terlambat' : 'Tepat Waktu' }}).</span>
                        </div>
                    </div>
                @endif

                <!-- 4. Catatan Opsional -->
                <div class="space-y-1.5 pt-1">
                    <x-input-label for="notes" :value="__('Catatan Presensi (Opsional)')" />
                    <textarea id="notes" x-model="notes" rows="2"
                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs placeholder-gray-400"
                        placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>

                <!-- 5. Tombol Aksi Presensi Masuk -->
                <div class="pt-3 border-t border-gray-100">
                    @if($todayAttendance && $todayAttendance->check_in_time)
                        <button type="button" disabled
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-emerald-100 text-emerald-700 font-bold text-sm rounded-xl cursor-not-allowed">
                            <i class="fa-solid fa-check text-sm"></i>
                            Presensi Masuk Hari Ini Selesai
                        </button>
                    @else
                        <button type="button" @click="submitCheckIn()" :disabled="isSubmitting"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-md shadow-blue-500/20 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="isSubmitting" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                                Menyimpan Presensi...
                            </span>
                            <span x-show="!isSubmitting">
                                <i class="fa-solid fa-arrow-right-to-bracket text-sm mr-1"></i>
                                Presensi Masuk Sekarang
                            </span>
                        </button>
                    @endif
                </div>

            </div>

        </div>
    </div>

    <script>
        function checkInApp() {
            return {
                currentTime: '--:--:--',
                latitude: null,
                longitude: null,
                locationText: 'Mendeteksi lokasi...',
                coordsText: '',
                isDetecting: false,
                isSubmitting: false,
                notes: '',
                message: '',
                isSuccess: false,

                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    this.detectLocation();
                },

                updateTime() {
                    const now = new Date();
                    const timeStr = new Intl.DateTimeFormat('en-GB', {
                        timeZone: 'Asia/Makassar',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    }).format(now);
                    this.currentTime = `${timeStr} WITA`;
                },

                detectLocation() {
                    this.isDetecting = true;
                    this.locationText = 'Mencari sinyal GPS...';

                    if (!navigator.geolocation) {
                        this.locationText = 'GPS tidak didukung perangkat ini';
                        this.isDetecting = false;
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.latitude = position.coords.latitude;
                            this.longitude = position.coords.longitude;
                            this.coordsText = `Koordinat: ${this.latitude.toFixed(5)}, ${this.longitude.toFixed(5)}`;
                            this.reverseGeocode(this.latitude, this.longitude);
                            this.isDetecting = false;
                        },
                        (error) => {
                            console.warn('Geolocation error:', error);
                            this.locationText = 'Lokasi aktif (koordinat default)';
                            this.isDetecting = false;
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                async reverseGeocode(lat, lon) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`, {
                            headers: { 'Accept-Language': 'id' }
                        });
                        const data = await res.json();
                        if (data && data.display_name) {
                            // Ambil nama tempat atau jalan ringkas
                            const parts = data.display_name.split(',');
                            this.locationText = parts.slice(0, 3).join(',').trim();
                        } else {
                            this.locationText = 'Lokasi GPS Terdeteksi';
                        }
                    } catch (e) {
                        this.locationText = 'Lokasi GPS Terdeteksi';
                    }
                },

                async submitCheckIn() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    this.message = '';

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch("{{ route('attendance.check-in.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                latitude: this.latitude,
                                longitude: this.longitude,
                                notes: this.notes
                            })
                        });

                        const res = await response.json();

                        if (response.ok && res.success) {
                            this.isSuccess = true;
                            this.message = res.message;
                            setTimeout(() => {
                                window.location.reload();
                            }, 1200);
                        } else {
                            this.isSuccess = false;
                            this.message = res.message || 'Gagal menyimpan presensi masuk.';
                        }
                    } catch (e) {
                        this.isSuccess = false;
                        this.message = 'Terjadi kesalahan jaringan atau server saat menyimpan presensi.';
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
