<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased selection:bg-orange-500 selection:text-white flex flex-col font-sans pb-16 md:pb-0"
          x-data="{
              mobileSearchOpen: false,
              searchQuery: '',
              submitSearch() {
                  if (this.searchQuery.trim().length > 0) {
                      window.location.href = '{{ route('browse') }}?q=' + encodeURIComponent(this.searchQuery.trim());
                  }
              }
          }">

        <!-- Top Header Navigation -->
        <header class="sticky top-0 z-40 w-full border-b border-zinc-800/80 bg-zinc-950/90 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                <!-- Brand & Logo -->
                <div class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group" wire:navigate>
                        <div class="size-9 rounded-xl bg-gradient-to-tr from-orange-600 to-amber-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform duration-200">
                            <flux:icon.book-open-text class="size-5" />
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white flex items-center gap-1">
                            Baca<span class="text-orange-500">Manga</span>
                        </span>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                        <a href="{{ route('home') }}"
                           class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('home') ? 'bg-zinc-800 text-orange-400 font-semibold' : 'text-zinc-300 hover:text-white hover:bg-zinc-900' }}"
                           wire:navigate>
                            Beranda
                        </a>
                        <a href="{{ route('browse', ['sort' => 'latest']) }}"
                           class="px-3 py-1.5 rounded-lg transition-colors {{ request()->fullUrlIs('*sort=latest*') ? 'bg-zinc-800 text-orange-400 font-semibold' : 'text-zinc-300 hover:text-white hover:bg-zinc-900' }}"
                           wire:navigate>
                            Terbaru
                        </a>
                        <a href="{{ route('browse') }}"
                           class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('browse') && !request()->has('sort') && !request()->has('popular') ? 'bg-zinc-800 text-orange-400 font-semibold' : 'text-zinc-300 hover:text-white hover:bg-zinc-900' }}"
                           wire:navigate>
                            Jelajah Komik
                        </a>
                        <a href="{{ route('browse', ['sort' => 'totalViews']) }}"
                           class="px-3 py-1.5 rounded-lg transition-colors {{ request()->fullUrlIs('*sort=totalViews*') ? 'bg-zinc-800 text-orange-400 font-semibold' : 'text-zinc-300 hover:text-white hover:bg-zinc-900' }}"
                           wire:navigate>
                            Populer
                        </a>
                        <a href="{{ route('library') }}"
                           class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('library') ? 'bg-zinc-800 text-orange-400 font-semibold' : 'text-zinc-300 hover:text-white hover:bg-zinc-900' }}"
                           wire:navigate>
                            Koleksi Saya
                        </a>
                    </nav>
                </div>

                <!-- Desktop Search Bar & Right Actions -->
                <div class="flex items-center gap-3">
                    <form @submit.prevent="submitSearch" class="hidden sm:block relative w-48 md:w-64 lg:w-80">
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="Cari manga, manhwa, judul..."
                            class="w-full bg-zinc-900/90 border border-zinc-700/60 rounded-xl px-3.5 py-1.5 pl-9 text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition-colors"
                        />
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 pointer-events-none">
                            <flux:icon.magnifying-glass class="size-4" />
                        </div>
                    </form>

                    <!-- Mobile search toggle button -->
                    <button @click="mobileSearchOpen = !mobileSearchOpen"
                            type="button"
                            class="sm:hidden p-2 text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-900 transition-colors"
                            aria-label="Cari Komik">
                        <flux:icon.magnifying-glass class="size-5" />
                    </button>

                    <!-- Bookmark Shortcut Icon -->
                    <a href="{{ route('library') }}"
                       class="relative p-2 text-zinc-400 hover:text-orange-400 rounded-lg hover:bg-zinc-900 transition-colors"
                       title="Koleksi & Riwayat"
                       wire:navigate>
                        <flux:icon.bookmark class="size-5" />
                    </a>

                    <!-- Auth Button if logged in or not -->
                    @auth
                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="flex items-center gap-2 p-1 rounded-full ring-1 ring-zinc-700 hover:ring-orange-500 transition-all">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" class="size-7" />
                            </button>
                            <flux:menu class="w-48 bg-zinc-900 border-zinc-800 text-zinc-200">
                                <div class="px-3 py-2 border-b border-zinc-800">
                                    <p class="text-xs font-semibold text-zinc-100 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-[11px] text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                <flux:menu.item :href="route('dashboard')" icon="layout-grid" wire:navigate>{{ __('Dashboard') }}</flux:menu.item>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Pengaturan') }}</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-400">
                                        {{ __('Keluar') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    @else
                        <a href="{{ route('login') }}"
                           class="hidden md:inline-flex items-center justify-center px-3.5 py-1.5 text-xs font-medium text-white bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-lg transition-colors"
                           wire:navigate>
                            Masuk
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Mobile Search Bar Expandable -->
            <div x-show="mobileSearchOpen"
                 x-collapse
                 x-cloak
                 class="sm:hidden px-4 pb-3 border-t border-zinc-800/60 pt-2 bg-zinc-950">
                <form @submit.prevent="submitSearch" class="relative w-full">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Ketik judul manga, lalu tekan Enter..."
                        class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3.5 py-2 pl-9 text-sm text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-orange-500"
                    />
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 pointer-events-none">
                        <flux:icon.magnifying-glass class="size-4" />
                    </div>
                </form>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 w-full">
            {{ $slot }}
        </main>

        <!-- Site Footer -->
        <footer class="border-t border-zinc-800/80 bg-zinc-950 text-zinc-400 text-xs py-8 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="size-6 rounded-lg bg-orange-600 flex items-center justify-center text-white">
                        <flux:icon.book-open-text class="size-3.5" />
                    </div>
                    <span class="font-bold text-sm text-zinc-200">BacaManga</span>
                    <span class="text-zinc-600">|</span>
                    <span class="text-zinc-500">Baca komik online Bahasa Indonesia terlengkap dan gratis.</span>
                </div>
                <div class="flex items-center gap-4 text-zinc-500">
                    <a href="{{ route('home') }}" class="hover:text-zinc-300 transition-colors" wire:navigate>Beranda</a>
                    <a href="{{ route('browse') }}" class="hover:text-zinc-300 transition-colors" wire:navigate>Jelajah</a>
                    <a href="{{ route('library') }}" class="hover:text-zinc-300 transition-colors" wire:navigate>Koleksi</a>
                    <a href="https://v2.voratoon.com/" target="_blank" rel="noopener noreferrer" class="hover:text-orange-400 transition-colors">
                        Sumber: Voratoon
                    </a>
                </div>
            </div>
        </footer>

        <!-- Mobile Bottom Navigation Bar -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-zinc-950/95 backdrop-blur-md border-t border-zinc-800 flex items-center justify-around py-2 px-1 text-[11px] font-medium text-zinc-400">
            <a href="{{ route('home') }}"
               class="flex flex-col items-center gap-1 transition-colors {{ request()->routeIs('home') ? 'text-orange-500 font-semibold' : 'hover:text-zinc-200' }}"
               wire:navigate>
                <flux:icon.home class="size-5" />
                <span>Beranda</span>
            </a>
            <a href="{{ route('browse', ['sort' => 'latest']) }}"
               class="flex flex-col items-center gap-1 transition-colors {{ request()->fullUrlIs('*sort=latest*') ? 'text-orange-500 font-semibold' : 'hover:text-zinc-200' }}"
               wire:navigate>
                <flux:icon.clock class="size-5" />
                <span>Terbaru</span>
            </a>
            <a href="{{ route('browse') }}"
               class="flex flex-col items-center gap-1 transition-colors {{ request()->routeIs('browse') && !request()->has('sort') ? 'text-orange-500 font-semibold' : 'hover:text-zinc-200' }}"
               wire:navigate>
                <flux:icon.magnifying-glass class="size-5" />
                <span>Jelajah</span>
            </a>
            <a href="{{ route('browse', ['sort' => 'totalViews']) }}"
               class="flex flex-col items-center gap-1 transition-colors {{ request()->fullUrlIs('*sort=totalViews*') ? 'text-orange-500 font-semibold' : 'hover:text-zinc-200' }}"
               wire:navigate>
                <flux:icon.fire class="size-5" />
                <span>Populer</span>
            </a>
            <a href="{{ route('library') }}"
               class="flex flex-col items-center gap-1 transition-colors {{ request()->routeIs('library') ? 'text-orange-500 font-semibold' : 'hover:text-zinc-200' }}"
               wire:navigate>
                <flux:icon.bookmark class="size-5" />
                <span>Koleksi</span>
            </a>
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
