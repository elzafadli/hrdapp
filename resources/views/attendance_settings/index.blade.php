<x-app-layout>
    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <!-- Header -->
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Setting Lokasi & Radius Presensi Geolocation</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Atur titik GPS kantor pusat/cabang dan toleransi jarak presensi mobile karyawan.</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <form method="POST" action="{{ route('attendance-settings.update') }}">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left: Form Inputs -->
                        <div class="lg:col-span-5 space-y-4">
                            <div>
                                <x-input-label for="office_name" :value="__('Nama Kantor / Titik Lokasi')" :required="true" />
                                <x-text-input id="office_name" name="office_name" type="text"
                                    :value="old('office_name', $setting->office_name)"
                                    placeholder="Contoh: Kantor Pusat Jakarta" class="mt-1 block w-full" required />
                                <x-input-error class="mt-1" :messages="$errors->get('office_name')" />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="latitude" :value="__('Latitude')" :required="true" />
                                    <x-text-input id="latitude" name="latitude" type="text"
                                        :value="old('latitude', $setting->latitude)"
                                        class="mt-1 block w-full font-mono text-xs" required />
                                    <x-input-error class="mt-1" :messages="$errors->get('latitude')" />
                                </div>
                                <div>
                                    <x-input-label for="longitude" :value="__('Longitude')" :required="true" />
                                    <x-text-input id="longitude" name="longitude" type="text"
                                        :value="old('longitude', $setting->longitude)"
                                        class="mt-1 block w-full font-mono text-xs" required />
                                    <x-input-error class="mt-1" :messages="$errors->get('longitude')" />
                                </div>
                            </div>

                            <div>
                                <button type="button" onclick="getCurrentDeviceLocation()"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                                    <i class="fa-solid fa-crosshairs text-blue-600"></i>
                                    Ambil Titik Koordinat Saya Saat Ini
                                </button>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <x-input-label for="radius_meters" :value="__('Batas Radius Presensi (Meter)')" :required="true" />
                                    <span class="text-xs font-bold text-blue-600" id="radiusBadge">
                                        {{ old('radius_meters', $setting->radius_meters) }} m
                                    </span>
                                </div>
                                <x-text-input id="radius_meters" name="radius_meters" type="number" min="10" max="10000"
                                    :value="old('radius_meters', $setting->radius_meters)"
                                    class="mt-1 block w-full" required />
                                <x-input-error class="mt-1" :messages="$errors->get('radius_meters')" />
                                <p class="text-[11px] text-gray-400 mt-1">Jarak toleransi maksimal karyawan dari titik kantor saat check-in.</p>
                            </div>

                            <div class="pt-3 border-t border-gray-100">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" id="allow_outside_radius" name="allow_outside_radius" value="1"
                                        {{ old('allow_outside_radius', $setting->allow_outside_radius) ? 'checked' : '' }}
                                        class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    <div>
                                        <span class="text-xs font-semibold text-gray-800">Izinkan Check-in di Luar Radius</span>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Jika dicentang, karyawan tetap dapat presensi dari luar kantor namun tercatat status <i>Luar Radius</i>.</p>
                                    </div>
                                </label>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <button type="submit"
                                    class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                                    <i class="fa-solid fa-floppy-disk mr-1.5"></i>
                                    Simpan Pengaturan Lokasi
                                </button>
                            </div>
                        </div>

                        <!-- Right: Interactive Leaflet Map Preview -->
                        <div class="lg:col-span-7">
                            <div class="flex items-center justify-between pb-2 mb-2">
                                <span class="text-xs font-bold text-gray-700">Preview Peta & Jangkauan Radius (Klik peta untuk memindahkan titik)</span>
                            </div>
                            <div id="officeMap" class="w-full h-[400px] rounded-xl border border-gray-200 z-10 shadow-xs"></div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            let map, marker, circle;

            document.addEventListener('DOMContentLoaded', function () {
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const radiusInput = document.getElementById('radius_meters');
                const radiusBadge = document.getElementById('radiusBadge');

                let lat = parseFloat(latInput.value) || -6.2000000;
                let lng = parseFloat(lngInput.value) || 106.8166660;
                let radius = parseInt(radiusInput.value) || 150;

                // Init map
                map = L.map('officeMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                circle = L.circle([lat, lng], {
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.2,
                    radius: radius
                }).addTo(map);

                function updateMapPosition(newLat, newLng) {
                    marker.setLatLng([newLat, newLng]);
                    circle.setLatLng([newLat, newLng]);
                    latInput.value = newLat.toFixed(7);
                    lngInput.value = newLng.toFixed(7);
                }

                // Drag marker
                marker.on('dragend', function (e) {
                    const pos = marker.getLatLng();
                    updateMapPosition(pos.lat, pos.lng);
                });

                // Click on map
                map.on('click', function (e) {
                    updateMapPosition(e.latlng.lat, e.latlng.lng);
                });

                // Radius input change
                radiusInput.addEventListener('input', function () {
                    const r = parseInt(this.value) || 0;
                    circle.setRadius(r);
                    radiusBadge.textContent = r + ' m';
                });

                // Lat/Lng input change
                latInput.addEventListener('change', function () {
                    const newLat = parseFloat(this.value);
                    const newLng = parseFloat(lngInput.value);
                    if (!isNaN(newLat) && !isNaN(newLng)) {
                        updateMapPosition(newLat, newLng);
                        map.setView([newLat, newLng]);
                    }
                });
                lngInput.addEventListener('change', function () {
                    const newLat = parseFloat(latInput.value);
                    const newLng = parseFloat(this.value);
                    if (!isNaN(newLat) && !isNaN(newLng)) {
                        updateMapPosition(newLat, newLng);
                        map.setView([newLat, newLng]);
                    }
                });
            });

            function getCurrentDeviceLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        document.getElementById('latitude').value = lat.toFixed(7);
                        document.getElementById('longitude').value = lng.toFixed(7);
                        if (marker && circle && map) {
                            marker.setLatLng([lat, lng]);
                            circle.setLatLng([lat, lng]);
                            map.setView([lat, lng], 17);
                        }
                    }, function (error) {
                        alert('Gagal mendeteksi lokasi perangkat: ' + error.message);
                    });
                } else {
                    alert('Geolocation tidak didukung oleh browser Anda.');
                }
            }
        </script>
    @endpush
</x-app-layout>

