<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.manga')] #[Title('Koleksi & Riwayat Baca')] class extends Component {
    //
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 space-y-8"
         x-data="{
             activeTab: 'bookmarks',
             bookmarks: [],
             history: [],
             init() {
                 this.loadData();
                 if (window.location.hash === '#history') {
                     this.activeTab = 'history';
                 }
             },
             loadData() {
                 try {
                     this.bookmarks = JSON.parse(localStorage.getItem('bacamanga_bookmarks') || '[]');
                 } catch (e) {
                     this.bookmarks = [];
                 }
                 try {
                     this.history = JSON.parse(localStorage.getItem('bacamanga_history') || '[]');
                 } catch (e) {
                     this.history = [];
                 }
             },
             removeBookmark(slug) {
                 this.bookmarks = this.bookmarks.filter(b => b.slug !== slug);
                 localStorage.setItem('bacamanga_bookmarks', JSON.stringify(this.bookmarks));
             },
             clearAllBookmarks() {
                 if (confirm('Yakin ingin menghapus semua bookmark?')) {
                     this.bookmarks = [];
                     localStorage.removeItem('bacamanga_bookmarks');
                 }
             },
             removeHistory(slug) {
                 this.history = this.history.filter(h => h.slug !== slug);
                 localStorage.setItem('bacamanga_history', JSON.stringify(this.history));
             },
             clearAllHistory() {
                 if (confirm('Yakin ingin menghapus seluruh riwayat baca?')) {
                     this.history = [];
                     localStorage.removeItem('bacamanga_history');
                 }
             },
             formatDate(dateStr) {
                 if (!dateStr) return '';
                 try {
                     const date = new Date(dateStr);
                     const now = new Date();
                     const diffSec = Math.floor((now - date) / 1000);
                     if (diffSec < 60) return 'Baru saja';
                     if (diffSec < 3600) return Math.floor(diffSec / 60) + ' menit lalu';
                     if (diffSec < 86400) return Math.floor(diffSec / 3600) + ' jam lalu';
                     if (diffSec < 2592000) return Math.floor(diffSec / 86400) + ' hari lalu';
                     return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                 } catch (e) {
                     return '';
                 }
             }
         }">

        <!-- Page Header & Title -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-800 pb-6">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                    <span class="p-2 bg-gradient-to-tr from-orange-600 to-amber-500 rounded-xl text-white shadow-lg shadow-orange-500/20">
                        <flux:icon.bookmark class="size-6" />
                    </span>
                    Koleksi & Riwayat Baca
                </h1>
                <p class="text-sm text-zinc-400">
                    Manga favorit yang disimpan dan riwayat membaca Anda (tersimpan di perangkat Anda).
                </p>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center bg-zinc-900 p-1.5 rounded-xl border border-zinc-800 self-start md:self-auto">
                <button
                    @click="activeTab = 'bookmarks'; window.location.hash = 'bookmarks'"
                    type="button"
                    :class="activeTab === 'bookmarks' ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'text-zinc-400 hover:text-white'"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all flex items-center gap-2">
                    <flux:icon.bookmark class="size-4" />
                    <span>Bookmark</span>
                    <span class="px-1.5 py-0.2 text-[11px] rounded-full"
                          :class="activeTab === 'bookmarks' ? 'bg-orange-600 text-white' : 'bg-zinc-800 text-zinc-300'"
                          x-text="bookmarks.length">0</span>
                </button>
                <button
                    @click="activeTab = 'history'; window.location.hash = 'history'"
                    type="button"
                    :class="activeTab === 'history' ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'text-zinc-400 hover:text-white'"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all flex items-center gap-2">
                    <flux:icon.clock class="size-4" />
                    <span>Riwayat Baca</span>
                    <span class="px-1.5 py-0.2 text-[11px] rounded-full"
                          :class="activeTab === 'history' ? 'bg-orange-600 text-white' : 'bg-zinc-800 text-zinc-300'"
                          x-text="history.length">0</span>
                </button>
            </div>
        </div>

        <!-- ================= BOOKMARKS TAB ================= -->
        <div x-show="activeTab === 'bookmarks'" x-cloak class="space-y-6">
            <!-- Header bar with count and clear button -->
            <div class="flex items-center justify-between" x-show="bookmarks.length > 0">
                <span class="text-xs sm:text-sm text-zinc-400">
                    Menampilkan <strong class="text-zinc-200" x-text="bookmarks.length"></strong> komik tersimpan
                </span>
                <button
                    @click="clearAllBookmarks"
                    type="button"
                    class="text-xs text-rose-400 hover:text-rose-300 hover:underline flex items-center gap-1.5">
                    <flux:icon.trash class="size-3.5" />
                    Hapus Semua Bookmark
                </button>
            </div>

            <!-- Empty State for Bookmarks -->
            <div x-show="bookmarks.length === 0" class="py-16 text-center space-y-4 max-w-md mx-auto">
                <div class="size-16 mx-auto rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-500 shadow-inner">
                    <flux:icon.bookmark class="size-8" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-zinc-200">Belum ada komik yang ditandai</h3>
                    <p class="text-xs sm:text-sm text-zinc-500">
                        Klik tombol "Bookmark" pada halaman komik untuk menyimpannya ke koleksi pribadi Anda.
                    </p>
                </div>
                <div>
                    <a href="{{ route('browse') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-xs sm:text-sm shadow-lg shadow-orange-500/25 transition-colors"
                       wire:navigate>
                        <flux:icon.magnifying-glass class="size-4" />
                        Jelajahi Manga Sekarang
                    </a>
                </div>
            </div>

            <!-- Bookmarks Grid -->
            <div x-show="bookmarks.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4 md:gap-5">
                <template x-for="item in bookmarks" :key="item.slug">
                    <div class="group relative flex flex-col bg-zinc-900/60 hover:bg-zinc-800/80 border border-zinc-800/70 hover:border-zinc-700/80 rounded-xl overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-black/60">
                        <!-- Poster Image -->
                        <div class="relative aspect-[3/4] w-full overflow-hidden bg-zinc-950">
                            <img :src="item.cover"
                                 :alt="item.title"
                                 loading="lazy"
                                 referrerpolicy="no-referrer"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />

                            <!-- Format Badge -->
                            <div class="absolute top-2 left-2 z-10">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider backdrop-blur-md bg-orange-600/90 text-white shadow-sm"
                                      x-text="item.format || 'MANGA'"></span>
                            </div>

                            <!-- Remove Button -->
                            <button
                                @click.prevent.stop="removeBookmark(item.slug)"
                                type="button"
                                title="Hapus dari bookmark"
                                class="absolute top-2 right-2 z-10 size-7 rounded-full bg-zinc-900/80 hover:bg-rose-600 text-zinc-300 hover:text-white flex items-center justify-center backdrop-blur-sm transition-colors shadow">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </div>

                        <!-- Info Content -->
                        <div class="p-3 flex-1 flex flex-col justify-between space-y-2">
                            <a :href="'/series/' + item.slug"
                               class="font-semibold text-xs sm:text-sm text-zinc-200 group-hover:text-orange-400 line-clamp-2 leading-snug transition-colors"
                               :title="item.title"
                               x-text="item.title"
                               wire:navigate>
                            </a>

                            <div class="pt-2 border-t border-zinc-800/60 flex items-center justify-between text-[11px] text-zinc-400">
                                <span x-text="formatDate(item.savedAt)"></span>
                                <a :href="'/series/' + item.slug"
                                   class="text-orange-400 hover:text-orange-300 font-medium flex items-center gap-0.5"
                                   wire:navigate>
                                    Baca &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ================= READING HISTORY TAB ================= -->
        <div x-show="activeTab === 'history'" x-cloak class="space-y-6">
            <!-- Header bar with count and clear button -->
            <div class="flex items-center justify-between" x-show="history.length > 0">
                <span class="text-xs sm:text-sm text-zinc-400">
                    Menampilkan <strong class="text-zinc-200" x-text="history.length"></strong> riwayat baca terakhir
                </span>
                <button
                    @click="clearAllHistory"
                    type="button"
                    class="text-xs text-rose-400 hover:text-rose-300 hover:underline flex items-center gap-1.5">
                    <flux:icon.trash class="size-3.5" />
                    Hapus Seluruh Riwayat
                </button>
            </div>

            <!-- Empty State for History -->
            <div x-show="history.length === 0" class="py-16 text-center space-y-4 max-w-md mx-auto">
                <div class="size-16 mx-auto rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-500 shadow-inner">
                    <flux:icon.clock class="size-8" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-zinc-200">Belum ada riwayat baca</h3>
                    <p class="text-xs sm:text-sm text-zinc-500">
                        Setiap chapter yang Anda baca di BacaManga akan otomatis dicatat di sini sehingga Anda dapat melanjutkan baca kapan saja.
                    </p>
                </div>
                <div>
                    <a href="{{ route('browse') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-xs sm:text-sm shadow-lg shadow-orange-500/25 transition-colors"
                       wire:navigate>
                        <flux:icon.book-open class="size-4" />
                        Mulai Baca Komik
                    </a>
                </div>
            </div>

            <!-- History List -->
            <div x-show="history.length > 0" class="space-y-3">
                <template x-for="item in history" :key="item.slug">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-3.5 sm:p-4 rounded-xl bg-zinc-900/60 hover:bg-zinc-900 border border-zinc-800/80 hover:border-zinc-700/80 transition-all">
                        <!-- Left: Thumbnail & Details -->
                        <div class="flex items-center gap-4 min-w-0">
                            <a :href="'/series/' + item.slug" class="shrink-0 size-16 sm:size-20 rounded-lg overflow-hidden bg-zinc-950 border border-zinc-800" wire:navigate>
                                <img :src="item.cover" :alt="item.title" referrerpolicy="no-referrer" class="w-full h-full object-cover hover:scale-105 transition-transform" />
                            </a>
                            <div class="min-w-0 space-y-1">
                                <a :href="'/series/' + item.slug"
                                   class="font-semibold text-sm sm:text-base text-zinc-100 hover:text-orange-400 line-clamp-1 transition-colors"
                                   :title="item.title"
                                   x-text="item.title"
                                   wire:navigate>
                                </a>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-400">
                                    <span class="inline-flex items-center gap-1 text-orange-400 font-medium">
                                        <flux:icon.book-open class="size-3.5" />
                                        Terakhir dibaca: Chapter <span x-text="item.lastChapterIndex"></span>
                                    </span>
                                    <span>&bull;</span>
                                    <span class="text-zinc-400" x-text="formatDate(item.readAt)"></span>
                                    <template x-if="item.readIndices && item.readIndices.length > 1">
                                        <span class="hidden md:inline text-zinc-400">
                                            &bull; <span x-text="item.readIndices.length"></span> chapter selesai
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center justify-end gap-2 shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-zinc-800/60">
                            <!-- Continue Reading Button -->
                            <a :href="'/series/' + item.slug + '/chapter/' + item.lastChapterIndex"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold shadow-md shadow-orange-500/20 transition-colors"
                               wire:navigate>
                                <flux:icon.play class="size-3.5" />
                                <span>Lanjut Baca</span>
                            </a>

                            <!-- Detail Series Button -->
                            <a :href="'/series/' + item.slug"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-medium transition-colors"
                               title="Lihat Detail Komik"
                               wire:navigate>
                                <flux:icon.list-bullet class="size-3.5" />
                                <span class="hidden md:inline">Detail</span>
                            </a>

                            <!-- Remove History Item Button -->
                            <button
                                @click="removeHistory(item.slug)"
                                type="button"
                                title="Hapus dari riwayat"
                                class="p-1.5 text-zinc-400 hover:text-rose-400 hover:bg-zinc-800 rounded-lg transition-colors">
                                <flux:icon.trash class="size-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
