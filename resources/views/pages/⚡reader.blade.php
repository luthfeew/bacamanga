<?php

use App\Services\VoratoonService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.manga')] class extends Component {
    public string $slug;
    public string $chapterIndex;
    public ?array $series = null;
    public ?array $chapter = null;
    public array $chapters = [];
    public ?string $prevChapterIndex = null;
    public ?string $nextChapterIndex = null;

    public function mount(string $slug, string $chapterIndex, VoratoonService $voratoon): void
    {
        $this->slug = $slug;
        $this->chapterIndex = $chapterIndex;

        $this->series = $voratoon->getSeriesDetail($slug);
        if (!$this->series) {
            abort(404, 'Komik tidak ditemukan');
        }

        $this->chapter = $voratoon->getChapter($slug, $chapterIndex);
        if (!$this->chapter) {
            abort(404, 'Chapter tidak ditemukan');
        }

        $this->chapters = $voratoon->getSeriesChapters($slug);

        // Sort chapters numerically to find prev and next
        $sortedAsc = $this->chapters;
        usort($sortedAsc, function($a, $b) {
            $ia = is_numeric($a['index']) ? (float)$a['index'] : $a['index'];
            $ib = is_numeric($b['index']) ? (float)$b['index'] : $b['index'];
            return $ia <=> $ib;
        });

        $currentIndexInList = null;
        foreach ($sortedAsc as $i => $ch) {
            if ((string)$ch['index'] === (string)$chapterIndex) {
                $currentIndexInList = $i;
                break;
            }
        }

        if ($currentIndexInList !== null) {
            $this->prevChapterIndex = isset($sortedAsc[$currentIndexInList - 1])
                ? (string)$sortedAsc[$currentIndexInList - 1]['index']
                : null;

            $this->nextChapterIndex = isset($sortedAsc[$currentIndexInList + 1])
                ? (string)$sortedAsc[$currentIndexInList + 1]['index']
                : null;
        }
    }
}; ?>

@php
        $title = $series['title'] ?? 'Manga';
        $cover = $series['cover'] ?? '';
        $chTitle = $chapter['title'] ?? "Chapter {$chapterIndex}";
        $images = $chapter['images'] ?? [];
    @endphp

    <div class="relative min-h-screen bg-black text-zinc-100 flex flex-col"
         x-data="{
             slug: '{{ $slug }}',
             title: '{{ addslashes($title) }}',
             cover: '{{ addslashes($cover) }}',
             chapterIndex: '{{ $chapterIndex }}',
             chapterTitle: '{{ addslashes($chTitle) }}',
             prevChapterUrl: '{{ $prevChapterIndex ? route('series.chapter', ['slug' => $slug, 'chapterIndex' => $prevChapterIndex]) : '' }}',
             nextChapterUrl: '{{ $nextChapterIndex ? route('series.chapter', ['slug' => $slug, 'chapterIndex' => $nextChapterIndex]) : '' }}',
             controlsVisible: true,
             useProxy: localStorage.getItem('bacamanga_use_proxy') === 'true',
             toggleProxy() {
                 this.useProxy = !this.useProxy;
                 localStorage.setItem('bacamanga_use_proxy', this.useProxy);
             },
             init() {
                 this.saveHistory();
                 window.addEventListener('keydown', (e) => {
                     if (e.key === 'ArrowLeft' && this.prevChapterUrl) {
                         window.location.href = this.prevChapterUrl;
                     } else if (e.key === 'ArrowRight' && this.nextChapterUrl) {
                         window.location.href = this.nextChapterUrl;
                     }
                 });
             },
             saveHistory() {
                 let history = JSON.parse(localStorage.getItem('bacamanga_history') || '[]');
                 let existingIndex = history.findIndex(h => h.slug === this.slug);
                 let readIndices = [];

                 if (existingIndex !== -1) {
                     readIndices = history[existingIndex].readIndices || [];
                     history.splice(existingIndex, 1);
                 }

                 if (!readIndices.includes(this.chapterIndex)) {
                     readIndices.push(this.chapterIndex);
                 }

                 history.unshift({
                     slug: this.slug,
                     title: this.title,
                     cover: this.cover,
                     lastChapterIndex: this.chapterIndex,
                     lastChapterTitle: this.chapterTitle,
                     readIndices: readIndices,
                     readAt: new Date().toISOString()
                 });

                 // Keep maximum 50 history entries
                 if (history.length > 50) {
                     history = history.slice(0, 50);
                 }

                 localStorage.setItem('bacamanga_history', JSON.stringify(history));
             }
         }">

        <!-- Reader Top Navigation Bar -->
        <div class="sticky top-0 z-30 w-full bg-zinc-950/95 border-b border-zinc-800 backdrop-blur-md transition-transform duration-300">
            <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-2 sm:gap-4">
                <!-- Back to Series Button & Title -->
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <a href="{{ route('series.show', $slug) }}"
                       class="p-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 hover:text-white border border-zinc-800 shrink-0 transition-colors"
                       title="Kembali ke Detail Komik"
                       wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                    </a>

                    <div class="truncate">
                        <a href="{{ route('series.show', $slug) }}" class="text-xs sm:text-sm font-bold text-zinc-200 hover:text-orange-400 truncate block transition-colors" wire:navigate>
                            {{ $title }}
                        </a>
                        <p class="text-[11px] text-orange-400 font-medium truncate">
                            {{ $chTitle }}
                        </p>
                    </div>
                </div>

                <!-- Chapter Select Dropdown & Controls -->
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <!-- Previous Chapter Button -->
                    @if($prevChapterIndex)
                        <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $prevChapterIndex]) }}"
                           class="p-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 hover:text-white border border-zinc-800 transition-colors"
                           title="Chapter Sebelumnya (Panah Kiri)"
                           wire:navigate>
                            <flux:icon.chevron-left class="size-4" />
                        </a>
                    @else
                        <button disabled class="p-2 rounded-xl bg-zinc-900/40 text-zinc-600 border border-zinc-900 cursor-not-allowed">
                            <flux:icon.chevron-left class="size-4" />
                        </button>
                    @endif

                    <!-- Chapter Selector Jump Dropdown -->
                    <select
                        onchange="window.location.href = this.value"
                        class="bg-zinc-900 border border-zinc-800 rounded-xl px-2.5 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-orange-500 max-w-[120px] sm:max-w-[160px]">
                        @foreach($chapters as $c)
                            <option value="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $c['index']]) }}"
                                {{ (string)$c['index'] === (string)$chapterIndex ? 'selected' : '' }}>
                                {{ $c['title'] }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Next Chapter Button -->
                    @if($nextChapterIndex)
                        <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $nextChapterIndex]) }}"
                           class="p-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-semibold transition-colors"
                           title="Chapter Selanjutnya (Panah Kanan)"
                           wire:navigate>
                            <flux:icon.chevron-right class="size-4" />
                        </a>
                    @else
                        <button disabled class="p-2 rounded-xl bg-zinc-900/40 text-zinc-600 border border-zinc-900 cursor-not-allowed">
                            <flux:icon.chevron-right class="size-4" />
                        </button>
                    @endif

                    <!-- Proxy Mode Toggle Button -->
                    <button
                        @click="toggleProxy()"
                        type="button"
                        :class="useProxy ? 'bg-orange-500/20 text-orange-400 border-orange-500/50' : 'bg-zinc-900 text-zinc-400 border-zinc-800 hover:text-zinc-200'"
                        class="px-2.5 py-1.5 rounded-xl border text-[11px] font-medium flex items-center gap-1.5 transition-colors"
                        :title="useProxy ? 'Server Proxy Aktif (Klik untuk matikan)' : 'Klik untuk aktifkan server proxy jika gambar terblokir'">
                        <flux:icon.server class="size-3.5" />
                        <span class="hidden sm:inline" x-text="useProxy ? 'Proxy: Aktif' : 'Proxy: Auto'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Vertical Images Reader Canvas -->
        <div class="flex-1 w-full flex flex-col items-center py-4 sm:py-6">
            @if(empty($images))
                <div class="py-24 text-center space-y-4 px-4">
                    <div class="size-16 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center mx-auto text-zinc-500">
                        <flux:icon.photo class="size-8" />
                    </div>
                    <h3 class="text-base font-semibold text-zinc-200">Lembaran gambar belum tersedia</h3>
                    <p class="text-xs sm:text-sm text-zinc-400 max-w-sm mx-auto">
                        Gambar bab ini sedang diproses oleh server atau terjadi kendala sementara.
                    </p>
                    <a href="{{ route('series.show', $slug) }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-orange-500 text-white text-xs font-semibold" wire:navigate>
                        Kembali ke Detail Komik
                    </a>
                </div>
            @else
                <!-- Continuous Image Strip (Zero gap for seamless webtoon reading) -->
                <div class="w-full max-w-3xl flex flex-col items-center bg-zinc-950 shadow-2xl">
                    @foreach($images as $imgIndex => $imageUrl)
                        @php
                            $proxyUrl = route('proxy.image', ['url' => $imageUrl]);
                        @endphp
                        <div class="w-full relative bg-zinc-900 overflow-hidden min-h-[300px] flex items-center justify-center">
                            <img
                                :src="useProxy ? '{{ $proxyUrl }}' : '{{ $imageUrl }}'"
                                data-proxy-src="{{ $proxyUrl }}"
                                alt="{{ $title }} - Bab {{ $chapterIndex }} (Hal {{ $imgIndex + 1 }})"
                                loading="{{ $imgIndex < 3 ? 'eager' : 'lazy' }}"
                                decoding="async"
                                referrerpolicy="no-referrer"
                                class="w-full h-auto block select-none"
                                onerror="if (!this.dataset.fallbackApplied) {
                                    this.dataset.fallbackApplied = 'true';
                                    this.src = this.dataset.proxySrc;
                                } else {
                                    this.classList.add('p-8', 'text-zinc-500', 'text-xs');
                                    this.alt = 'Gagal memuat gambar lembar {{ $imgIndex + 1 }}. Silakan klik tombol Proxy Server di navigasi atas.';
                                }"
                            />
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Bottom Chapter Navigation & Controls -->
            <div class="w-full max-w-2xl px-4 py-12 text-center space-y-6">
                <div class="p-6 rounded-2xl bg-zinc-900/80 border border-zinc-800 space-y-4">
                    <h4 class="text-sm font-semibold text-zinc-200">
                        Kamu telah menyelesaikan <span class="text-orange-400">{{ $chTitle }}</span>
                    </h4>

                    <div class="flex items-center justify-center gap-3 flex-wrap">
                        @if($prevChapterIndex)
                            <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $prevChapterIndex]) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold border border-zinc-700 transition-colors"
                               wire:navigate>
                                <flux:icon.chevron-left class="size-4" />
                                <span>Bab Sebelumnya</span>
                            </a>
                        @endif

                        <a href="{{ route('series.show', $slug) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium border border-zinc-700 transition-colors"
                           wire:navigate>
                            <flux:icon.list-bullet class="size-4" />
                            <span>Daftar Bab</span>
                        </a>

                        @if($nextChapterIndex)
                            <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $nextChapterIndex]) }}"
                               class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold shadow-lg shadow-orange-500/25 transition-all hover:scale-105 active:scale-95"
                               wire:navigate>
                                <span>Lanjut Bab Selanjutnya</span>
                                <flux:icon.chevron-right class="size-4" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
