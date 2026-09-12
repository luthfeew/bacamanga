@props([
    'manga',
    'showLatestChapter' => true,
])

@php
    $slug = $manga['slug'] ?? '';
    $title = $manga['title'] ?? 'Manga Title';
    $cover = $manga['cover'] ?? '/placeholders/cover.jpg';
    $format = strtoupper($manga['format'] ?? 'MANHWA');
    $rating = $manga['rating'] ?? 0;
    $status = $manga['status'] ?? 'ongoing';
    $isHot = !empty($manga['isHot']);
    $latestChapter = $manga['latestChapter'] ?? null;

    $formatBadgeColor = match ($format) {
        'MANGA' => 'bg-blue-600/90 text-blue-100 border-blue-500/50',
        'MANHUA' => 'bg-emerald-600/90 text-emerald-100 border-emerald-500/50',
        default => 'bg-orange-600/90 text-orange-100 border-orange-500/50',
    };
@endphp

<div class="group relative flex flex-col rounded-2xl bg-zinc-900/60 border border-zinc-800/80 overflow-hidden hover:border-orange-500/60 hover:shadow-xl hover:shadow-orange-500/10 transition-all duration-300">
    <!-- Poster Image with Link -->
    <a href="{{ route('series.show', $slug) }}" class="relative block aspect-[3/4] w-full overflow-hidden bg-zinc-800" wire:navigate>
        <img
            src="{{ $cover }}"
            alt="{{ $title }}"
            loading="lazy"
            referrerpolicy="no-referrer"
            class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-300 ease-out"
            onerror="if(!this.dataset.retried && '{{ $cover }}'.startsWith('http')){this.dataset.retried=1; this.src='{{ route('proxy.image') }}?url='+encodeURIComponent('{{ $cover }}');} else {this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22400%22 viewBox=%220 0 300 400%22><rect width=%22300%22 height=%22400%22 fill=%22%2327272a%22/><text x=%2250%25%22 y=%2250%25%22 fill=%22%2371717a%22 font-family=%22sans-serif%22 font-size=%2216%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22>No Cover</text></svg>';}"
        />

        <!-- Top Badges Overlay -->
        <div class="absolute top-2 left-2 right-2 flex items-center justify-between gap-1 pointer-events-none">
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider backdrop-blur-md border {{ $formatBadgeColor }}">
                {{ $format }}
            </span>

            @if($isHot)
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-red-600/90 text-red-100 border border-red-500/50 backdrop-blur-md flex items-center gap-0.5">
                    <flux:icon.fire class="size-3 inline" /> HOT
                </span>
            @elseif($rating > 0)
                <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded-md bg-zinc-950/80 text-amber-300 border border-amber-500/30 backdrop-blur-md flex items-center gap-1">
                    <flux:icon.star class="size-3 text-amber-400 fill-amber-400" />
                    {{ number_format((float)$rating, 1) }}
                </span>
            @endif
        </div>

        <!-- Gradient Bottom Shadow -->
        <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-zinc-950/90 via-zinc-950/40 to-transparent pointer-events-none"></div>
    </a>

    <!-- Info Block -->
    <div class="p-3 flex-1 flex flex-col justify-between gap-2">
        <a href="{{ route('series.show', $slug) }}" class="group-hover:text-orange-400 transition-colors" wire:navigate>
            <h3 class="text-xs sm:text-sm font-semibold text-zinc-100 line-clamp-2 leading-snug" title="{{ $title }}">
                {{ $title }}
            </h3>
        </a>

        @if($showLatestChapter && $latestChapter && !empty($latestChapter['index']))
            <div class="pt-1 border-t border-zinc-800/80 flex items-center justify-between text-[11px] text-zinc-400">
                <a href="{{ route('series.chapter', ['slug' => $slug, 'chapterIndex' => $latestChapter['index']]) }}"
                   class="inline-flex items-center gap-1 font-medium text-orange-400/90 hover:text-orange-300 bg-orange-500/10 hover:bg-orange-500/20 px-2 py-0.5 rounded-md transition-colors"
                   wire:navigate>
                    <span>Ch. {{ $latestChapter['index'] }}</span>
                </a>

                @if(!empty($latestChapter['date']))
                    <span class="text-[10px] text-zinc-500">
                        {{ \Carbon\Carbon::parse($latestChapter['date'])->diffForHumans(null, true) }}
                    </span>
                @endif
            </div>
        @elseif(!empty($manga['totalChapters']) && $manga['totalChapters'] !== '0')
            <div class="pt-1 border-t border-zinc-800/80 flex items-center justify-between text-[11px] text-zinc-400">
                <span class="text-[11px] text-zinc-400">
                    {{ $manga['totalChapters'] }} Bab
                </span>
                <span class="text-[10px] capitalize px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300">
                    {{ $status }}
                </span>
            </div>
        @endif
    </div>
</div>
