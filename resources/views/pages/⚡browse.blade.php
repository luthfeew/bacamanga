<?php

use App\Services\VoratoonService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.manga')] #[Title('Jelajah & Cari Komik')] class extends Component {
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'format')]
    public string $format = 'all';

    #[Url(as: 'status')]
    public string $status = 'all';

    #[Url(as: 'sort')]
    public string $sort = 'latest';

    #[Url(as: 'genre')]
    public string $genreId = '';

    #[Url(as: 'page')]
    public int $page = 1;

    public array $genres = [];
    public bool $filterOpen = false;

    public function mount(VoratoonService $voratoon): void
    {
        $this->genres = $voratoon->getGenres();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedFormat(): void
    {
        $this->page = 1;
    }

    public function updatedStatus(): void
    {
        $this->page = 1;
    }

    public function updatedSort(): void
    {
        $this->page = 1;
    }

    public function setGenre(string $id): void
    {
        $this->genreId = ($this->genreId === $id) ? '' : $id;
        $this->page = 1;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->format = 'all';
        $this->status = 'all';
        $this->sort = 'latest';
        $this->genreId = '';
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function with(VoratoonService $voratoon): array
    {
        $filters = [
            'sort' => $this->sort,
            'sortOrder' => ($this->sort === 'title') ? 'asc' : 'desc',
        ];

        if (!empty(trim($this->search))) {
            $filters['title'] = trim($this->search);
        }

        if ($this->format !== 'all') {
            $filters['format'] = $this->format;
        }

        if ($this->status !== 'all') {
            $filters['status'] = $this->status;
        }

        if (!empty($this->genreId)) {
            $filters['genreIds'] = $this->genreId;
        }

        $result = $voratoon->getSeries($filters, $this->page, 24);

        return [
            'seriesList' => $result['items'] ?? [],
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Jelajah Komik</h1>
                <p class="text-xs sm:text-sm text-zinc-400">Temukan ribuan komik Manga, Manhwa, dan Manhua dalam Bahasa Indonesia</p>
            </div>

            <!-- Mobile Filter Toggle Button -->
            <button
                @click="$wire.filterOpen = !$wire.filterOpen"
                type="button"
                class="lg:hidden inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-zinc-900 border border-zinc-700 text-sm font-medium text-zinc-200 hover:bg-zinc-800">
                <flux:icon.adjustments-horizontal class="size-4 text-orange-500" />
                <span>Filter & Kategori</span>
            </button>
        </div>

        <!-- Filter Controls Bar -->
        <div class="space-y-4">
            <!-- Search & Quick Selects Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Cari berdasarkan judul komik..."
                        class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 pl-10 text-sm text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                    />
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-500">
                        <flux:icon.magnifying-glass class="size-4" />
                    </div>
                    @if(!empty($search))
                        <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white">
                            <flux:icon.x-mark class="size-4" />
                        </button>
                    @endif
                </div>

                <!-- Format Filter (Manga / Manhwa / Manhua) -->
                <div>
                    <select
                        wire:model.live="format"
                        class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:outline-none focus:border-orange-500">
                        <option value="all">Semua Format (Semua Komik)</option>
                        <option value="manhwa">Manhwa (Korea)</option>
                        <option value="manga">Manga (Jepang)</option>
                        <option value="manhua">Manhua (China)</option>
                    </select>
                </div>

                <!-- Status Filter (Ongoing / Completed) -->
                <div>
                    <select
                        wire:model.live="status"
                        class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:outline-none focus:border-orange-500">
                        <option value="all">Semua Status</option>
                        <option value="ongoing">Berjalan (Ongoing)</option>
                        <option value="completed">Tamat (Completed)</option>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div>
                    <select
                        wire:model.live="sort"
                        class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:outline-none focus:border-orange-500">
                        <option value="latest">Rilis Terbaru</option>
                        <option value="totalViews">Paling Populer</option>
                        <option value="rating">Rating Tertinggi</option>
                        <option value="title">Judul (A - Z)</option>
                    </select>
                </div>
            </div>

            <!-- Collapsible Genre Filter Cloud -->
            <div class="{{ $filterOpen ? 'block' : 'hidden' }} lg:block p-4 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
                        <flux:icon.tag class="size-3.5 text-orange-500" />
                        Pilih Genre
                    </span>
                    @if(!empty($genreId) || !empty($search) || $format !== 'all' || $status !== 'all' || $sort !== 'latest')
                        <button
                            wire:click="resetFilters"
                            class="text-xs text-orange-400 hover:text-orange-300 font-medium">
                            Reset Filter
                        </button>
                    @endif
                </div>

                <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1">
                    @foreach($genres as $g)
                        <button
                            type="button"
                            wire:click="setGenre('{{ $g['id'] }}')"
                            class="px-3 py-1 rounded-lg text-xs font-medium transition-all {{ (string)$genreId === (string)$g['id'] ? 'bg-orange-500 text-white font-semibold shadow-md shadow-orange-500/20' : 'bg-zinc-800/70 text-zinc-300 hover:text-white hover:bg-zinc-800 border border-zinc-700/50' }}">
                            {{ $g['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Series Grid with Loading State -->
        <div class="relative min-h-[300px]">
            <!-- Loading Overlay -->
            <div wire:loading.flex class="absolute inset-0 z-20 bg-zinc-950/60 backdrop-blur-xs items-center justify-center">
                <div class="flex flex-col items-center gap-2 p-4 rounded-2xl bg-zinc-900 border border-zinc-800 text-orange-500">
                    <flux:icon.arrow-path class="size-6 animate-spin" />
                    <span class="text-xs font-medium text-zinc-300">Memuat komik...</span>
                </div>
            </div>

            @if(empty($seriesList))
                <!-- Empty State -->
                <div class="py-16 text-center space-y-3">
                    <div class="size-16 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center mx-auto text-zinc-500">
                        <flux:icon.magnifying-glass class="size-8" />
                    </div>
                    <h3 class="text-base font-semibold text-zinc-200">Tidak ada komik ditemukan</h3>
                    <p class="text-xs sm:text-sm text-zinc-400 max-w-sm mx-auto">
                        Coba ubah kata kunci pencarian atau bersihkan filter yang sedang aktif.
                    </p>
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold">
                        Reset Semua Filter
                    </button>
                </div>
            @else
                <!-- Manga Card Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    @foreach($seriesList as $manga)
                        <x-manga-card :manga="$manga" :show-latest-chapter="true" />
                    @endforeach
                </div>

                <!-- Pagination Navigation -->
                <div class="pt-8 flex items-center justify-center gap-3">
                    <button
                        wire:click="prevPage"
                        @disabled($page <= 1)
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed border border-zinc-800 text-sm font-medium text-zinc-200 transition-colors">
                        <flux:icon.chevron-left class="size-4" />
                        <span>Sebelumnya</span>
                    </button>

                    <div class="px-4 py-2 rounded-xl bg-zinc-900/80 border border-zinc-800 text-sm font-semibold text-orange-400">
                        Halaman {{ $page }}
                    </div>

                    <button
                        wire:click="nextPage"
                        @disabled(count($seriesList) < 24)
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed border border-zinc-800 text-sm font-medium text-zinc-200 transition-colors">
                        <span>Selanjutnya</span>
                        <flux:icon.chevron-right class="size-4" />
                    </button>
                </div>
            @endif
        </div>
    </div>
