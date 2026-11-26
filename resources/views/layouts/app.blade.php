<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="notranslate" translate="no">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google" content="notranslate">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'HrdApp') }}">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script>
        if (typeof flatpickr !== 'undefined') {
            flatpickr.setDefaults({
                disableMobile: true
            });
        }
    </script>
    <style>
        /* Minimal overrides to blend with Tailwind if needed */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem;
            background-color: #fff;
            border-color: #d1d5db;
            border-radius: 0.375rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-color: #d1d5db;
            border-radius: 0.375rem;
        }

        /* Flatpickr overrides for Tailwind forms collision */
        .flatpickr-calendar {
            background: #fff !important;
            border-radius: 1rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #e5e7eb !important;
            font-family: inherit !important;
            box-sizing: content-box !important;
            width: 308px !important;
            padding: 0.75rem !important;
        }
        .flatpickr-months {
            padding: 0 0.25rem !important;
        }
        .flatpickr-days {
            width: 308px !important;
        }
        .dayContainer {
            width: 308px !important;
            min-width: 308px !important;
            max-width: 308px !important;
        }
        .flatpickr-calendar input.cur-year,
        .flatpickr-calendar .numInputWrapper input {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            font-weight: 700 !important;
        }
        .flatpickr-calendar select {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 1.5rem 0 0.5rem !important;
            font-weight: 700 !important;
        }
        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #fff !important;
            border-radius: 0.5rem !important;
        }
        /* Tanggal Disable: Tidak dicoret, tampil abu-abu lembut / disabled */
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #9ca3af !important;
            background: #f9fafb !important;
            text-decoration: none !important;
            cursor: not-allowed !important;
            opacity: 0.45 !important;
            border-color: transparent !important;
        }
        .flatpickr-day {
            border-radius: 0.5rem !important;
            line-height: 38px !important;
            height: 38px !important;
        }

        /* Slim & responsive scrollbar untuk sidebar */
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #475569 transparent;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 9999px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-100 notranslate" translate="no">
    <div class="flex h-screen overflow-hidden bg-gray-100">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
            <!-- Top Navigation -->
            @include('layouts.navigation')

            <!-- Main Scrollable Area -->
            <div class="flex-1 overflow-y-auto pb-20 sm:pb-0">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Bar (Sesuai Tampilan Mobile App) -->
    <nav class="sm:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200/90 z-30 px-2 py-1 shadow-lg pb-[max(0.35rem,env(safe-area-inset-bottom))]">
        <div class="flex items-center justify-around max-w-md mx-auto">
            <!-- Kalender -->
            <a href="{{ route('calendar.index') }}" class="flex flex-col items-center py-1 px-2.5 text-xs transition {{ request()->routeIs('calendar.*') ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fa-solid fa-calendar-days text-lg mb-0.5"></i>
                <span class="text-[11px] leading-tight">Kalender</span>
            </a>

            <!-- Timeline -->
            <a href="{{ route('timeline.index') }}" class="flex flex-col items-center py-1 px-2 text-xs transition {{ request()->routeIs('timeline.*') ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fa-solid fa-newspaper text-lg mb-0.5"></i>
                <span class="text-[11px] leading-tight">Timeline</span>
            </a>

            <!-- Kehadiran (Tombol Lingkaran Melayang di Tengah) -->
            <a href="{{ route('attendance.check-in') }}" class="flex flex-col items-center -mt-6 group">
                <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg group-hover:bg-blue-700 transition border-4 border-slate-100">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2m0 10v2a2 2 0 01-2 2h-2m-10 0H5a2 2 0 01-2-2v-2" />
                        <circle cx="12" cy="11" r="3" stroke-width="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17a4 4 0 018 0" />
                    </svg>
                </div>
                <span class="text-[11px] font-medium mt-0.5 {{ request()->routeIs('attendance.*') ? 'text-blue-600 font-bold' : 'text-gray-600' }}">Kehadiran</span>
            </a>

            <!-- Ajukan Cuti Tahunan -->
            <a href="{{ route('leave.index') }}" class="flex flex-col items-center py-1 px-2 text-xs transition {{ request()->routeIs('leave.*') ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fa-solid fa-umbrella-beach text-lg mb-0.5"></i>
                <span class="text-[11px] leading-tight">Ajukan Cuti</span>
            </a>

            <!-- Profil (Employee Profile) -->
            <a href="{{ route('ess.profile') }}" class="flex flex-col items-center py-1 px-2.5 text-xs transition {{ request()->routeIs('ess.profile*') ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fa-solid fa-id-badge text-lg mb-0.5"></i>
                <span class="text-[11px] leading-tight">Profil</span>
            </a>
        </div>
    </nav>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>
    @stack('scripts')

    <!-- PWA Install Prompt Banner (Khusus HP / Mobile Browser) -->
    <div id="pwa-install-banner" class="hidden md:hidden fixed top-3 left-3 right-3 z-50 bg-white/95 backdrop-blur-md border border-blue-200 p-3.5 rounded-2xl shadow-xl transition-all duration-300">
        <div class="flex items-start gap-3">
            <img src="/icons/icon-96x96.png" alt="App Icon" class="w-11 h-11 rounded-xl shadow-xs shrink-0 mt-0.5">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-900 leading-tight">Pasang Aplikasi di HP</h4>
                    <button type="button" onclick="dismissPwaBanner()" class="text-gray-400 hover:text-gray-600 p-0.5 -mr-1">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
                <p class="text-[11px] text-gray-600 mt-1 leading-snug">
                    Akses presensi selfie, GPS & fitur ESS langsung dari layar utama HP Anda tanpa buka browser.
                </p>
                <div class="mt-2.5 flex items-center gap-2">
                    <button type="button" id="btn-pwa-install" onclick="triggerPwaInstall()"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-download text-[11px]"></i>
                        <span>Install Sekarang</span>
                    </button>
                    <button type="button" onclick="dismissPwaBanner()" class="text-[11px] text-gray-500 hover:text-gray-700 px-2 py-1">
                        Nanti Saja
                    </button>
                </div>
                <!-- Petunjuk untuk iOS Safari -->
                <div id="pwa-ios-instructions" class="hidden mt-2 p-2 rounded-xl bg-blue-50 border border-blue-100 text-[10px] text-blue-900 leading-relaxed">
                    Untuk pengguna <strong>iPhone (Safari)</strong>: Tekan tombol <strong>Bagikan (Share)</strong> <i class="fa-solid fa-arrow-up-from-bracket mx-0.5"></i> di bagian bawah, lalu pilih <strong>"Tambahkan ke Layar Utama" (Add to Home Screen)</strong> ➕.
                </div>
            </div>
        </div>
    </div>

    <!-- PWA Service Worker & Install Logic -->
    <script>
        let deferredPrompt;
        const pwaBanner = document.getElementById('pwa-install-banner');
        const iosInstructions = document.getElementById('pwa-ios-instructions');
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        // Register Service Worker with Auto Update Check
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => {
                        // Cek pembaruan berkas dari server secara berkala
                        reg.update();
                    })
                    .catch((err) => {
                        console.warn('PWA ServiceWorker registration failed:', err);
                    });
            });

            // Refresh halaman otomatis jika service worker baru telah aktif
            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        }

        // Handle PWA Install Prompt for Android/Chrome
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const dismissed = sessionStorage.getItem('pwa_banner_dismissed');
            if (!isStandalone && !dismissed && pwaBanner) {
                pwaBanner.classList.remove('hidden');
            }
        });

        // Detect iOS Safari if not installed
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        if (isIos && !isStandalone) {
            const dismissed = sessionStorage.getItem('pwa_banner_dismissed');
            if (!dismissed && pwaBanner) {
                pwaBanner.classList.remove('hidden');
                const btnInstall = document.getElementById('btn-pwa-install');
                if (btnInstall) btnInstall.style.display = 'none';
                if (iosInstructions) iosInstructions.classList.remove('hidden');
            }
        }

        window.triggerPwaInstall = async function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                if (pwaBanner) pwaBanner.classList.add('hidden');
            }
        };

        window.dismissPwaBanner = function() {
            if (pwaBanner) pwaBanner.classList.add('hidden');
            sessionStorage.setItem('pwa_banner_dismissed', 'true');
        };

        window.addEventListener('appinstalled', () => {
            if (pwaBanner) pwaBanner.classList.add('hidden');
            deferredPrompt = null;
        });
    </script>
</body>

</html>
