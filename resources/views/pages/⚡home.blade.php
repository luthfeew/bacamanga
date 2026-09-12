<?php

use App\Services\VoratoonService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.manga')] #[Title('Baca Komik Online Bahasa Indonesia Terlengkap')] class extends Component {
    public array $trending = [];
    public array $popular = [];
    public array $latestUpdates = [];
    public array $genres = [];
    public array $recommendations = [];

    public function mount(VoratoonService $voratoon): void
    {
        $this->trending = array_slice($voratoon->getTrending(), 0, 5);
        $this->popular = array_slice($voratoon->getPopular('week'), 0, 12);
        $this->recommendations = array_slice($voratoon->getRecommendations(), 0, 6);
        $this->latestUpdates = $voratoon->getLatestUpdates(1, 18)['items'] ?? [];
        $this->genres = array_slice($voratoon->getGenres(), 0, 20);
    }
}; ?>

<div class="w-full">
    <!-- Hero / Featured Banner Carousel -->
    @if(!empty($trending))
        <section class="relative w-full overflow-hidden bg-zinc-950 border-b border-zinc-800/80"
                 x-data="{
                     activeSlide: 0,
                     totalSlides: {{ count($trending) }},
                     autoplayTimer: null,
                     init() {
                         this.startAutoplay();
                     },
                     startAutoplay() {
                         this.autoplayTimer = setInterval(() => {
                             this.next();
                         }, 6000);
                     },
                     stopAutoplay() {
                         clearInterval(this.autoplayTimer);
                     },
                     next() {
                         this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                     },
                     prev() {
                         this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides;
                     }
                 }"
                 @mouseenter="stopAutoplay()"
                 @mouseleave="startAutoplay()">

            <div class="relative h-[340px] sm:h-[400px] md:h-[440px] w-full">
                @foreach($trending as $index => $item)
                    <div x-show="activeSlide === {{ $index }}"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-105"
                         class="absolute inset-0 w-full h-full"
                         style="{{ $index === 0 ? '' : 'display: none;' }}">

                        <!-- Background Backdrop Image with heavy blur and dark overlay -->
                        <div class="absolute inset-0 bg-cover bg-center filter blur-xl scale-110 opacity-35"
                             style="background-image: url('{{ $item['background'] ?: $item['cover'] }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/85 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-zinc-950/60"></div>

                        <!-- Content Container -->
                        <div class="relative max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-center">
                            <div class="flex items-center gap-6 sm:gap-8 max-w-3xl">
                                <!-- Manga Cover Poster -->
                                <a href="{{ route('series.show', $item['slug']) }}" class="shrink-0 hidden sm:block w-36 md:w-44 aspect-[3/4] rounded-2xl overflow-hidden shadow-2xl border border-zinc-700/60 group" wire:navigate>
                                    <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" referrerpolicy="no-referrer" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="if(!this.dataset.retried && '{{ $item['cover'] }}'.startsWith('http')){this.dataset.retried=1; this.src='{{ route('proxy.image') }}?url='+encodeURIComponent('{{ $item['cover'] }}');}" />
                                </a>

                                <!-- Manga Details -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-orange-500/20 text-orange-400 border border-orange-500/30">
                                            Trending #{{ $index + 1 }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase bg-zinc-800 text-zinc-300">
                                            {{ $item['format'] }}
                                        </span>
                                        @if(!empty($item['rating']))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                                <flux:icon.star class="size-3 text-amber-400 fill-amber-400" />
                                                {{ number_format((float)$item['rating'], 1) }}
                                            </span>
                                        @endif
                                    </div>

                                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight leading-tight line-clamp-2">
                                        <a href="{{ route('series.show', $item['slug']) }}" class="hover:text-orange-400 transition-colors" wire:navigate>
                                            {{ $item['title'] }}
                                        </a>
                                    </h1>

                                    <p class="text-xs sm:text-sm text-zinc-300 line-clamp-3 leading-relaxed max-w-xl">
                                        {{ $item['synopsis'] ?: 'Belum ada sinopsis untuk komik ini. Klik tombol di bawah untuk mulai membaca chapter terbaru secara gratis.' }}
                                    </p>

                                    <div class="pt-2 flex items-center gap-3">
                                        <a href="{{ route('series.show', $item['slug']) }}"
                                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm shadow-lg shadow-orange-500/25 transition-all hover:scale-105 active:scale-95"
                                           wire:navigate>
                                            <flux:icon.book-open-text class="size-4" />
                                            <span>Baca Sekarang</span>
                                        </a>

                                        @if(!empty($item['latestChapter']['index']))
                                            <a href="{{ route('series.chapter', ['slug' => $item['slug'], 'chapterIndex' => $item['latestChapter']['index']]) }}"
                                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-zinc-800/80 hover:bg-zinc-700 text-zinc-200 font-medium text-sm border border-zinc-700 transition-colors"
                                               wire:navigate>
                                                <span>Ch. {{ $item['latestChapter']['index'] }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Carousel Controls (Next/Prev Arrows) -->
                <button @click="prev()"
                        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full bg-zinc-950/60 hover:bg-zinc-900 border border-zinc-700/60 text-white transition-colors">
                    <flux:icon.chevron-left class="size-5" />
                </button>
                <button @click="next()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-2 rounded-full bg-zinc-950/60 hover:bg-zinc-900 border border-zinc-700/60 text-white transition-colors">
                    <flux:icon.chevron-right class="size-5" />
                </button>

                <!-- Carousel Indicator Dots -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
                    @foreach($trending as $idx => $t)
                        <button @click="activeSlide = {{ $idx }}"
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="activeSlide === {{ $idx }} ? 'w-6 bg-orange-500' : 'w-2 bg-zinc-600 hover:bg-zinc-400'">
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">
        <!-- Quick Genre Pills Filter Cloud -->
        @if(!empty($genres))
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider">Genre Pilihan</h2>
                    <a href="{{ route('browse') }}" class="text-xs text-orange-400 hover:text-orange-300 font-medium" wire:navigate>
                        Lihat Semua Genre →
                    </a>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                    @foreach($genres as $genre)
                        <a href="{{ route('browse', ['genreIds' => $genre['id']]) }}"
                           class="shrink-0 px-3.5 py-1.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-medium text-zinc-300 hover:text-white hover:border-orange-500/50 hover:bg-zinc-800 transition-colors"
                           wire:navigate>
                            {{ $genre['name'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Popular Manga Carousel / Grid -->
        @if(!empty($popular))
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-orange-500/10 text-orange-500 border border-orange-500/20">
                            <flux:icon.fire class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">Populer Minggu Ini</h2>
                            <p class="text-xs text-zinc-400">Komik paling banyak dibaca dan dicari penggemar</p>
                        </div>
                    </div>
                    <a href="{{ route('browse', ['sort' => 'totalViews']) }}"
                       class="text-xs font-semibold text-orange-400 hover:text-orange-300 transition-colors flex items-center gap-1"
                       wire:navigate>
                        <span>Selengkapnya</span>
                        <flux:icon.chevron-right class="size-4" />
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    @foreach($popular as $manga)
                        <x-manga-card :manga="$manga" :show-latest-chapter="false" />
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Latest Updates Section -->
        @if(!empty($latestUpdates))
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <flux:icon.clock class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">Rilis Bab Terbaru</h2>
                            <p class="text-xs text-zinc-400">Update chapter komik terkini dari hari ke hari</p>
                        </div>
                    </div>
                    <a href="{{ route('browse', ['sort' => 'latest']) }}"
                       class="text-xs font-semibold text-orange-400 hover:text-orange-300 transition-colors flex items-center gap-1"
                       wire:navigate>
                        <span>Lihat Semua Update</span>
                        <flux:icon.chevron-right class="size-4" />
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    @foreach($latestUpdates as $manga)
                        <x-manga-card :manga="$manga" :show-latest-chapter="true" />
                    @endforeach
                </div>

                <!-- Call to action button -->
                <div class="pt-6 flex justify-center">
                    <a href="{{ route('browse', ['sort' => 'latest']) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-200 font-semibold text-sm border border-zinc-700/80 hover:border-orange-500/50 transition-colors"
                       wire:navigate>
                        <span>Jelajahi Lebih Banyak Komik</span>
                        <flux:icon.arrow-right class="size-4 text-orange-500" />
                    </a>
                </div>
            </section>
        @endif

        <!-- Recommendations Section -->
        @if(!empty($recommendations))
            <section class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <flux:icon.sparkles class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">Rekomendasi Editor</h2>
                            <p class="text-xs text-zinc-400">Pilihan judul terbaik dengan alur cerita menarik</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    @foreach($recommendations as $manga)
                        <x-manga-card :manga="$manga" :show-latest-chapter="false" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
