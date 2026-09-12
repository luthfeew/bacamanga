<?php

use App\Services\VoratoonService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.manga')] class extends Component {
    public string $slug;
    public ?array $series = null;
    public array $chapters = [];
    public string $chapterSearch = '';
    public string $chapterSort = 'desc'; // 'desc' or 'asc'

    public function mount(string $slug, VoratoonService $voratoon): void
    {
        $this->slug = $slug;
        $this->series = $voratoon->getSeriesDetail($slug);

        if (!$this->series) {
            abort(404, 'Komik tidak ditemukan');
        }

        $this->chapters = $voratoon->getSeriesChapters($slug);
    }

    public function toggleChapterSort(): void
    {
        $this->chapterSort = ($this->chapterSort === 'desc') ? 'asc' : 'desc';
    }

    public function rendered(): void
    {
        $this->dispatch('set-page-title', title: ($this->series['title'] ?? 'Detail Komik') . ' - BacaManga');
    }
}; ?>

@php
        $title = $series['title'] ?? 'Manga Detail';
        $cover = $series['cover'] ?? '/placeholders/cover.jpg';
        $background = $series['background'] ?: $cover;
        $format = strtoupper($series['format'] ?? 'MANHWA');
        $status = ucfirst($series['status'] ?? 'Ongoing');
        $rating = $series['rating'] ?? 0;
        $author = $series['author'] ?? 'Unknown';
        $synopsis = $series['synopsis'] ?? 'Tidak ada sinopsis.';

        // Sort chapters
        $filteredChapters = $chapters;
        if (!empty(trim($chapterSearch))) {
            $query = trim($chapterSearch);
            $filteredChapters = array_filter($filteredChapters, function($c) use ($query) {
                return str_contains(strtolower($c['index']), strtolower($query)) ||
                       str_contains(strtolower($c['title']), strtolower($query));
            });
        }

        // Sort ascending or descending by numeric index if possible
        usort($filteredChapters, function($a, $b) use ($chapterSort) {
            $idxA = is_numeric($a['index']) ? (float)$a['index'] : $a['index'];
            $idxB = is_numeric($b['index']) ? (float)$b['index'] : $b['index'];
            if ($chapterSort === 'asc') {
                return $idxA <=> $idxB;
            }
            return $idxB <=> $idxA;
        });

        // Determine first and last chapter
        $sortedAsc = $chapters;
        usort($sortedAsc, fn($a, $b) => ((float)$a['index']) <=> ((float)$b['index']));
        $firstChapter = $sortedAsc[0] ?? null;
        $lastChapter = $sortedAsc[count($sortedAsc) - 1] ?? null;
    @endphp

    <div class="relative w-full"
         x-data="{
             slug: '{{ $slug }}',
             title: '{{ addslashes($title) }}',
             cover: '{{ addslashes($cover) }}',
             format: '{{ $format }}',
             isBookmarked: false,
             readChapters: [],
             init() {
                 let bookmarks = JSON.parse(localStorage.getItem('bacamanga_bookmarks') || '[]');
                 this.isBookmarked = bookmarks.some(b => b.slug === this.slug);

                 let history = JSON.parse(localStorage.getItem('bacamanga_history') || '[]');
                 let currentHistory = history.find(h => h.slug === this.slug);
                 if (currentHistory && currentHistory.readIndices) {
                     this.readChapters = currentHistory.readIndices;
                 }
             },
             toggleBookmark() {
                 let bookmarks = JSON.parse(localStorage.getItem('bacamanga_bookmarks') || '[]');
                 if (this.isBookmarked) {
                     bookmarks = bookmarks.filter(b => b.slug !== this.slug);
                     this.isBookmarked = false;
                 } else {
                     bookmarks.unshift({
                         slug: this.slug,
                         title: this.title,
                         cover: this.cover,
                         format: this.format,
                         savedAt: new Date().toISOString()
                     });
                     this.isBookmarked = true;
                 }
                 localStorage.setItem('bacamanga_bookmarks', JSON.stringify(bookmarks));
             }
         }">

        <!-- Blurred Hero Header Backdrop -->
        <div class="relative w-full h-[280px] sm:h-[350px] md:h-[400px] overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center filter blur-2xl scale-125 opacity-30"
                 style="background-image: url('{{ $background }}');"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/70 via-zinc-950/90 to-zinc-950"></div>
        </div>

        <!-- Main Content (Overlapping hero header) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-52 sm:-mt-64 relative z-10 space-y-10 pb-16">
            <!-- Top Series Info Card -->
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6 sm:gap-8">
                <!-- Cover Poster -->
                <div class="shrink-0 w-44 sm:w-56 md:w-64 aspect-[3/4] rounded-2xl overflow-hidden shadow-2xl border-2 border-zinc-700/80 bg-zinc-900 group">
                    <img src="{{ $cover }}" alt="{{ $title }}" referrerpolicy="no-referrer" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="if(!this.dataset.retried && '{{ $cover }}'.startsWith('http')){this.dataset.retried=1; this.src='{{ route('proxy.image') }}?url='+encodeURIComponent('{{ $cover }}');}" />
                </div>

                <!-- Info Details -->
                <div class="flex-1 space-y-4 text-center md:text-left">
                    <!-- Badges -->
                    <div class="flex items-center justify-center md:justify-start gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-orange-500/20 text-orange-400 border border-orange-500/30">
                            {{ $format }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-zinc-800 text-zinc-300 border border-zinc-700">
                            {{ $status }}
                        </span>
                        @if($rating > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                <flux:icon.star class="size-3.5 text-amber-400 fill-amber-400" />
                                {{ number_format((float)$rating, 1) }}
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight leading-tight">
                        {{ $title }}
                    </h1>

                    @if(!empty($series['nativeTitle']))
                        <p class="text-xs text-zinc-400 italic">
                            {{ $series['nativeTitle'] }}
                        </p>
                    @endif

                    <!-- Metadata Items (Author, Chapters, Views) -->
                    <div class="flex items-center justify-center md:justify-start gap-6 text-xs text-zinc-400 border-y border-zinc-800/80 py-2.5">
                        <div>
                            <span class="text-zinc-500 block">Penulis</span>
                            <span class="font-medium text-zinc-200">{{ $author }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 block">Total Bab</span>
                            <span class="font-medium text-zinc-200">{{ count($chapters) }} Chapter</span>
                        </div>
                        @if(!empty($series['releaseDate']))
                            <div>
                                <span class="text-zinc-500 block">Tahun</span>
                                <span class="font-medium text-zinc-200">{{ $series['releaseDate'] }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Genre Tags -->
                    @if(!empty($series['genres']))
                        <div class="flex items-center justify-center md:justify-start gap-1.5 flex-wrap">
                            @foreach($series['genres'] as $genre)
                                <a href="{{ route('browse', ['genre' => $genre['id']]) }}"
                                   class="px-2.5 py-1 rounded-lg text-xs font-medium bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 hover:border-orange-500/40 text-zinc-300 hover:text-white transition-colors"
                                   wire:navigate>
                                    {{ $genre['name'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- Action Buttons (Read First, Read Latest, Bookmark) -->
                    <div class="pt-2 flex items-center justify-center md:justify-start gap-3 flex-wrap">
                        @if($firstChapter)
                            <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $firstChapter['index']]) }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm shadow-lg shadow-orange-500/20 transition-all hover:scale-105 active:scale-95"
                               wire:navigate>
                                <flux:icon.book-open-text class="size-4" />
                                <span>Bab Pertama (Ch. {{ $firstChapter['index'] }})</span>
                            </a>
                        @endif

                        @if($lastChapter && $lastChapter['index'] !== ($firstChapter['index'] ?? null))
                            <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $lastChapter['index']]) }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-100 font-semibold text-sm border border-zinc-700 transition-colors"
                               wire:navigate>
                                <flux:icon.bolt class="size-4 text-orange-400" />
                                <span>Bab Terbaru (Ch. {{ $lastChapter['index'] }})</span>
                            </a>
                        @endif

                        <!-- Bookmark Button -->
                        <button
                            type="button"
                            @click="toggleBookmark()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border transition-all"
                            :class="isBookmarked ? 'bg-orange-500/10 border-orange-500 text-orange-400 font-semibold' : 'bg-zinc-900 border-zinc-700 text-zinc-300 hover:text-white hover:bg-zinc-800'">
                            <flux:icon.bookmark class="size-4" ::class="isBookmarked ? 'fill-orange-400' : ''" />
                            <span x-text="isBookmarked ? 'Tersimpan' : 'Bookmark'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Synopsis Section -->
            <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 space-y-3">
                <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <flux:icon.document-text class="size-5 text-orange-500" />
                    <span>Sinopsis Cerita</span>
                </h2>
                <div class="text-xs sm:text-sm text-zinc-300 leading-relaxed space-y-2 whitespace-pre-line">
                    {{ $synopsis }}
                </div>
            </div>

            <!-- Chapter List Section -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-orange-500/10 text-orange-500 border border-orange-500/20">
                            <flux:icon.bars-3-bottom-left class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">Daftar Chapter</h2>
                            <p class="text-xs text-zinc-400">Total {{ count($chapters) }} bab tersedia untuk dibaca gratis</p>
                        </div>
                    </div>

                    <!-- Filter / Search Chapter Bar -->
                    <div class="flex items-center gap-2">
                        <div class="relative w-40 sm:w-56">
                            <input
                                type="text"
                                wire:model.live="chapterSearch"
                                placeholder="Cari bab..."
                                class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-3 py-1.5 pl-8 text-xs text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-orange-500"
                            />
                            <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-500">
                                <flux:icon.magnifying-glass class="size-3.5" />
                            </div>
                        </div>

                        <!-- Sort Toggle Button -->
                        <button
                            type="button"
                            wire:click="toggleChapterSort"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-zinc-900 border border-zinc-800 hover:bg-zinc-800 text-xs text-zinc-300 transition-colors"
                            title="Urutkan Chapter">
                            <flux:icon.arrows-up-down class="size-3.5 text-orange-400" />
                            <span>{{ $chapterSort === 'desc' ? 'Terbaru' : 'Terlama' }}</span>
                        </button>
                    </div>
                </div>

                @if(empty($filteredChapters))
                    <div class="py-12 text-center text-zinc-500 text-sm">
                        Tidak ada chapter yang cocok dengan pencarian.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                        @foreach($filteredChapters as $ch)
                            <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $ch['index']]) }}"
                               class="group flex items-center justify-between p-3 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/90 border border-zinc-800/80 hover:border-orange-500/50 transition-all duration-200"
                               :class="readChapters.includes('{{ $ch['index'] }}') ? 'opacity-60 border-zinc-800/40' : ''"
                               wire:navigate>
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="size-7 rounded-lg bg-zinc-800 group-hover:bg-orange-500/20 text-zinc-400 group-hover:text-orange-400 flex items-center justify-center shrink-0 text-xs font-bold transition-colors">
                                        {{ $ch['index'] }}
                                    </div>
                                    <div class="truncate">
                                        <h4 class="text-xs sm:text-sm font-semibold text-zinc-200 group-hover:text-orange-400 truncate transition-colors">
                                            {{ $ch['title'] }}
                                        </h4>
                                        @if(!empty($ch['date']))
                                            <p class="text-[10px] text-zinc-500">
                                                {{ \Carbon\Carbon::parse($ch['date'])->diffForHumans(null, true) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="shrink-0 text-zinc-600 group-hover:text-orange-400 transition-colors">
                                    <flux:icon.chevron-right class="size-4" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
