<nav x-data="{ open: false }" class="bg-white/90 backdrop-blur-md border-b border-gray-100 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="transition-transform hover:scale-105 duration-200">
                        <x-application-logo class="block h-10 w-auto fill-current text-blue-900" />
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    
                    {{-- === 1. MENU ADMIN === --}}
                    @if(Auth::check() && Auth::user()->role === 'admin')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-sm font-bold tracking-tight">
                            {{ __('Daftar Pemeliharaan') }}
                            <span id="badge-admin-reports" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-600 text-white hidden">0</span>
                        </x-nav-link>
                        <x-nav-link :href="route('apps.index')" :active="request()->routeIs('apps.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Projek Aplikasi') }}
                            <span id="badge-admin-apps" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-blue-600 text-white hidden">0</span>
                        </x-nav-link>
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Kelola User') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.rooms.index')" :active="request()->routeIs('admin.rooms.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Kelola Ruangan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.procurements.index')" :active="request()->routeIs('admin.procurements.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Daftar Pengadaan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('it-notes.index')" :active="request()->routeIs('it-notes.index')">
                            {{ __('Catatan Tim IT') }}
                        </x-nav-link>
                    @endif

                    {{-- === RAPAT: Visible for any logged in user === --}}
                    @if(Auth::check())
                        <x-nav-link :href="route('meetings.index')" :active="request()->routeIs('meetings.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Rapat') }}
                        </x-nav-link>
                    @endif

                    {{-- === 2. MENU DIREKTUR (UPDATE) === --}}
                    @if(Auth::check() && Auth::user()->role === 'direktur')
                        <x-nav-link :href="route('director.reports')" :active="request()->routeIs('director.reports')" class="text-sm font-bold tracking-tight">
                            {{ __('Monitoring Laporan') }}
                        </x-nav-link>

                        {{-- TAMBAHAN: MENU PENGADAAN DIREKTUR --}}
                        <x-nav-link :href="route('director.procurements.index')" :active="request()->routeIs('director.procurements.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Daftar Pengadaan') }}
                            {{-- Badge Notifikasi Pengadaan --}}
                            <span id="badge-director-procurements" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-600 text-white hidden">0</span>
                        </x-nav-link>

                        <x-nav-link :href="route('apps.index')" :active="request()->routeIs('apps.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Projek Aplikasi') }}
                            {{-- Badge Notifikasi Aplikasi --}}
                            <span id="badge-director-apps" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-blue-600 text-white hidden">0</span>
                        </x-nav-link>

                        <x-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="text-sm font-bold tracking-tight">
                            {{ __('Tracking Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="text-sm font-bold tracking-tight">
                            {{ __('Form Laporan Baru') }}
                        </x-nav-link>
                    @endif

                    {{-- === MENU MANAGEMENT === --}}
                    @if(Auth::check() && Auth::user()->role === 'management')
                        <x-nav-link :href="route('management.reports')" :active="request()->routeIs('management.reports')" class="text-sm font-bold tracking-tight">
                            {{ __('Monitoring Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('management.procurements')" :active="request()->routeIs('management.procurements')" class="text-sm font-bold tracking-tight">
                            {{ __('Persetujuan Pengadaan') }}
                            <span id="badge-management-procurements" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-emerald-600 text-white hidden">0</span>
                        </x-nav-link>
                        <x-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="text-sm font-bold tracking-tight">
                            {{ __('Tracking Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="text-sm font-bold tracking-tight">
                            {{ __('Form Laporan Baru') }}
                        </x-nav-link>
                    @endif

                    {{-- === 3. MENU BENDAHARA === --}}
                    @if(Auth::check() && Auth::user()->role === 'bendahara')
                        <x-nav-link :href="route('bendahara.reports')" :active="request()->routeIs('bendahara.reports')" class="text-sm font-bold tracking-tight">
                            {{ __('Monitoring Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('bendahara.procurements.index')" :active="request()->routeIs('bendahara.procurements.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Validasi Keuangan') }}
                            <span id="badge-bendahara-procurements" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-600 text-white hidden">0</span>
                        </x-nav-link>
                        <x-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="text-sm font-bold tracking-tight">
                            {{ __('Tracking Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="text-sm font-bold tracking-tight">
                            {{ __('Form Laporan Baru') }}
                        </x-nav-link>
                    @endif

                    {{-- === 4. MENU kepala_ruang === --}}
                    @if(Auth::check() && Auth::user()->role === 'kepala_ruang')
                        <x-nav-link :href="route('kepala-ruang.procurements.index')" :active="request()->routeIs('kepala-ruang.procurements.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Validasi Pengadaan') }}
                            <span id="badge-kepala-ruang-procurements" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-600 text-white hidden">0</span>
                        </x-nav-link>
                        <x-nav-link :href="route('kepala-ruang.apps.index')" :active="request()->routeIs('kepala-ruang.apps.index')" class="text-sm font-bold tracking-tight">
                            {{ __('Form Request Aplikasi') }}
                        </x-nav-link>
                        <x-nav-link :href="route('apps.index')" :active="request()->routeIs('apps.*')" class="text-sm font-bold tracking-tight">
                            {{ __('Projek Aplikasi') }}
                        </x-nav-link>
                        <x-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="text-sm font-bold tracking-tight">
                            {{ __('Tracking Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="text-sm font-bold tracking-tight">
                            {{ __('Form Laporan Baru') }}
                        </x-nav-link>
                    @endif

                    {{-- === 5. MENU GUEST === --}}
                    @if(!Auth::check())
                        <x-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="text-sm font-bold tracking-tight">
                            {{ __('Tracking Laporan') }}
                        </x-nav-link>
                        <x-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="text-sm font-bold tracking-tight">
                            {{ __('Form Laporan Baru') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- Right Section: User Dropdown --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @if(Auth::check())
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-bold rounded-xl text-blue-900 bg-blue-50 hover:bg-blue-100 transition ease-in-out duration-200 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-full bg-blue-900 flex items-center justify-center text-[11px] text-white shadow-sm font-bold">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    <span class="text-blue-900">{{ Auth::user()->name }}</span>
                                </div>

                                <div class="ms-2">
                                    <svg class="fill-current h-4 w-4 text-blue-900 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-gray-100 bg-white rounded-t-xl">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Signed in as</p>
                                <p class="text-sm font-bold text-blue-900 truncate">{{ Auth::user()->email }}</p>
                                @php
                                    $roleColors = [
                                        'admin' => 'bg-purple-100 text-purple-800',
                                        'direktur' => 'bg-blue-100 text-blue-800',
                                        'kepala_ruang' => 'bg-yellow-100 text-yellow-800',
                                        'staff' => 'bg-gray-100 text-gray-800',
                                        'bendahara' => 'bg-green-100 text-green-800',
                                        'management' => 'bg-emerald-100 text-emerald-800',
                                    ];
                                @endphp
                                <div class="mt-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $roleColors[Auth::user()->role] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst(Auth::user()->role) }}</span>
                                </div>
                                @if(Auth::user()->role === 'kepala_ruang' && Auth::user()->room)
                                    <div class="mt-2 text-sm text-gray-600">{{ Auth::user()->room->name }}</div>
                                @endif
                            </div>
                            
                            <div class="p-1 bg-white">
                                <x-dropdown-link :href="route('profile.edit')" class="rounded-lg font-bold text-gray-700 hover:text-blue-900 hover:bg-gray-50 transition">
                                    {{ __('My Profile') }}
                                </x-dropdown-link>
                            </div>

                            <div class="p-1 bg-white rounded-b-xl border-t border-gray-50">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm font-bold text-red-600 hover:bg-red-50 rounded-lg transition">
                                        {{ __('Sign Out') }}
                                    </button>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-900 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-blue-800 transition shadow-md">
                        {{ __('Login') }}
                    </a>
                @endif
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="p-2.5 rounded-xl text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Quick Rapat Modal removed — single Rapat link in navbar covers all functions. --}}

    {{-- MOBILE MENU --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white border-t border-gray-100 shadow-lg">
        <div class="pt-2 pb-3 space-y-1 px-2">
            @if(Auth::check())
                <x-responsive-nav-link :href="route('meetings.index')" :active="request()->routeIs('meetings.*')" class="rounded-lg font-bold">
                    {{ __('Rapat') }}
                </x-responsive-nav-link>
                {{-- ADMIN MOBILE --}}
                @if(Auth::user()->role === 'admin')
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="rounded-lg font-bold">
                        {{ __('Daftar Pemeliharaan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('apps.index')" :active="request()->routeIs('apps.*')" class="rounded-lg font-bold">
                        {{ __('Projek Aplikasi') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="rounded-lg font-bold">
                        {{ __('Kelola User') }}
                    </x-responsive-nav-link>
                            <x-responsive-nav-link :href="route('admin.rooms.index')" :active="request()->routeIs('admin.rooms.*')" class="rounded-lg font-bold">
                                {{ __('Kelola Ruangan') }}
                            </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.procurements.index')" :active="request()->routeIs('admin.procurements.*')" class="rounded-lg font-bold">
                        {{ __('Daftar Pengadaan') }}
                    </x-responsive-nav-link>
                @endif

                {{-- DIREKTUR MOBILE (UPDATE) --}}
                @if(Auth::user()->role === 'direktur')
                    <x-responsive-nav-link :href="route('director.reports')" :active="request()->routeIs('director.reports')" class="rounded-lg font-bold">
                        {{ __('Monitoring Laporan') }}
                    </x-responsive-nav-link>
                    {{-- Tambahan Mobile --}}
                    <x-responsive-nav-link :href="route('director.procurements.index')" :active="request()->routeIs('director.procurements.*')" class="rounded-lg font-bold">
                        {{ __('Persetujuan Pengadaan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('apps.index')" :active="request()->routeIs('apps.*')" class="rounded-lg font-bold">
                        {{ __('Projek Aplikasi') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="rounded-lg font-bold">
                        {{ __('Tracking Laporan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="rounded-lg font-bold">
                        {{ __('Form Laporan Baru') }}
                    </x-responsive-nav-link>
                @endif

                {{-- MANAGEMENT MOBILE --}}
                @if(Auth::user()->role === 'management')
                    <x-responsive-nav-link :href="route('management.reports')" :active="request()->routeIs('management.reports')" class="rounded-lg font-bold">
                        {{ __('Monitoring Laporan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('management.procurements')" :active="request()->routeIs('management.procurements')" class="rounded-lg font-bold">
                        {{ __('Persetujuan Pengadaan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="rounded-lg font-bold">
                        {{ __('Tracking Laporan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="rounded-lg font-bold">
                        {{ __('Form Laporan Baru') }}
                    </x-responsive-nav-link>
                @endif

                {{-- BENDAHARA MOBILE --}}
                @if(Auth::user()->role === 'bendahara')
                    <x-responsive-nav-link :href="route('bendahara.reports')" :active="request()->routeIs('bendahara.reports')" class="rounded-lg font-bold">
                        {{ __('Monitoring Laporan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('bendahara.procurements.index')" :active="request()->routeIs('bendahara.procurements.*')" class="rounded-lg font-bold">
                        {{ __('Validasi Keuangan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="rounded-lg font-bold">
                        {{ __('Tracking Laporan') }}
                    </x-responsive-nav-link>
                @endif

                {{-- KEPALA RUANG MOBILE --}}
                @if(Auth::user()->role === 'kepala_ruang')
                    <x-responsive-nav-link :href="route('kepala-ruang.procurements.index')" :active="request()->routeIs('kepala-ruang.procurements.*')" class="rounded-lg font-bold">
                        {{ __('Validasi Pengadaan') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('kepala-ruang.apps.index')" :active="request()->routeIs('kepala-ruang.apps.index')" class="rounded-lg font-bold">
                        {{ __('Form Request Aplikasi') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('apps.index')" :active="request()->routeIs('apps.*')" class="rounded-lg font-bold">
                        {{ __('Projek Aplikasi') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="rounded-lg font-bold">
                        {{ __('Tracking Laporan') }}
                    </x-responsive-nav-link>
                @endif

            @else
                <x-responsive-nav-link :href="route('public.tracking')" :active="request()->routeIs('public.tracking')" class="rounded-lg font-bold">
                    {{ __('Tracking Laporan') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('public.home')" :active="request()->routeIs('public.home')" class="rounded-lg font-bold">
                    {{ __('Form Laporan Baru') }}
                </x-responsive-nav-link>
                <div class="border-t border-gray-100 my-2"></div>
                <x-responsive-nav-link :href="route('login')" class="rounded-lg font-bold text-blue-900 bg-blue-50">
                    Login Staff
                </x-responsive-nav-link>
            @endif
        </div>

        {{-- USER PROFILE MOBILE --}}
        @if(Auth::check())
            <div class="pt-4 pb-4 border-t border-gray-200 bg-gray-50">
                <div class="px-4 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-blue-900 flex items-center justify-center text-sm text-white font-bold shadow-sm">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-base text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                        @php
                            $roleColors = [
                                'admin' => 'bg-purple-100 text-purple-800',
                                'direktur' => 'bg-blue-100 text-blue-800',
                                'kepala_ruang' => 'bg-yellow-100 text-yellow-800',
                                'staff' => 'bg-gray-100 text-gray-800',
                                'bendahara' => 'bg-green-100 text-green-800',
                            ];
                        @endphp
                        <div class="mt-1">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $roleColors[Auth::user()->role] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst(Auth::user()->role) }}</span>
                        </div>
                        @if(Auth::user()->role === 'kepala_ruang' && Auth::user()->room)
                            <div class="text-sm text-gray-500">Kepala Ruangan: {{ Auth::user()->room->name }}</div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 space-y-1 px-2">
                    <x-responsive-nav-link :href="route('profile.edit')" class="rounded-lg">
                        {{ __('My Profile') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();" class="text-red-600 hover:bg-red-50 rounded-lg">
                            {{ __('Sign Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endif
    </div>
</nav>