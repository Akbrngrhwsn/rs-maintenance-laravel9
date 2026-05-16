<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Portal Maintenance - RS PKU Jatinom</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-slate-200 relative overflow-hidden">

    <!-- Background Blur -->
    <div class="absolute top-0 left-0 w-72 h-72 bg-blue-300/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-72 h-72 bg-green-300/30 rounded-full blur-3xl"></div>

    <div class="relative z-10 min-h-screen flex items-center justify-center px-6 py-10">

        <div class="max-w-5xl w-full">

            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/70 glass border border-white shadow-sm mb-5">
                    
                    <span class="text-sm font-medium text-gray-700">
                        RS PKU Jatinom
                    </span>
                </div>

                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800 tracking-tight">
                    Sistem Informasi Maintenance
                </h1>

                <p class="mt-4 text-gray-600 text-lg max-w-2xl mx-auto">
                    Pilih layanan maintenance yang ingin Anda akses
                </p>
            </div>

            <!-- Cards -->
            <div class="grid md:grid-cols-2 gap-8">

                <!-- IT -->
                <a href="{{ route('public.home') }}"
                    class="group relative overflow-hidden rounded-3xl bg-white/80 glass border border-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">

                    <!-- Glow -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/0 to-blue-500/5 opacity-0 group-hover:opacity-100 transition"></div>

                    <div class="relative p-8 md:p-10">

                        <div class="flex items-center justify-center w-20 h-20 rounded-2xl 
                            bg-blue-100 text-blue-600 mb-8
                            group-hover:bg-blue-600 
                            group-hover:text-white
                            transition-all duration-300 shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-10 h-10"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-800 mb-3">
                            RS Maintenance IT
                        </h2>

                        <p class="text-gray-600 leading-relaxed">
                            Layanan maintenance komputer, jaringan, printer,
                            dan sistem aplikasi rumah sakit.
                        </p>

                        <div class="mt-8 flex items-center gap-2 text-blue-600 font-semibold">
                            <span>Akses Sistem</span>

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- IPSRS -->
                <a href="https://rs-maintenance-ipsrs.pku-jatinom.com"
                    class="group relative overflow-hidden rounded-3xl bg-white/80 glass border border-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">

                    <!-- Glow -->
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500/0 to-green-500/5 opacity-0 group-hover:opacity-100 transition"></div>

                    <div class="relative p-8 md:p-10">

                        <div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-green-100 text-green-600 mb-8 group-hover:bg-green-600 group-hover:text-white transition-all duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-10 h-10"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-800 mb-3">
                            RS Maintenance IPSRS
                        </h2>

                        <p class="text-gray-600 leading-relaxed">
                            Layanan pemeliharaan sarana, prasarana,
                            fasilitas, dan gedung rumah sakit.
                        </p>

                        <div class="mt-8 flex items-center gap-2 text-green-600 font-semibold">
                            <span>Akses Sistem</span>

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Footer -->
            <div class="mt-12 text-center">
                <p class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} RS PKU Jatinom —
                    Instalasi Teknologi Informasi
                </p>
            </div>

        </div>
    </div>
</body>
</html>