<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoratoonService
{
    protected string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('services.voratoon.base_url', 'https://v2.voratoon.com/backend'), '/');
    }

    /**
     * Fetch trending series.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrending(): array
    {
        return Cache::remember('voratoon:trending', 900, function (): array {
            $response = $this->request('/series/trending');

            return $this->formatSeriesList($response['data'] ?? []);
        });
    }

    /**
     * Fetch recommendations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecommendations(): array
    {
        return Cache::remember('voratoon:recommendations', 900, function (): array {
            $response = $this->request('/series/recommendations');

            return $this->formatSeriesList($response['data'] ?? []);
        });
    }

    /**
     * Fetch popular series.
     *
     * @param  string  $period  'all', 'today', or 'week'
     * @return array<int, array<string, mixed>>
     */
    public function getPopular(string $period = 'all'): array
    {
        $endpoint = match ($period) {
            'today' => '/popular/today',
            'week' => '/popular/week',
            default => '/popular',
        };

        return Cache::remember("voratoon:popular:{$period}", 900, function () use ($endpoint): array {
            $response = $this->request($endpoint);

            return $this->formatSeriesList($response['data'] ?? []);
        });
    }

    /**
     * Fetch best manga, manhwa, or manhua.
     *
     * @param  string  $type  'manga', 'manhwa', or 'manhua'
     * @return array<int, array<string, mixed>>
     */
    public function getBest(string $type = 'manhwa'): array
    {
        $endpoint = match (strtolower($type)) {
            'manga' => '/series/best-manga',
            'manhua' => '/series/best-manhua',
            default => '/series/best-manhwa',
        };

        return Cache::remember("voratoon:best:{$type}", 900, function () use ($endpoint): array {
            $response = $this->request($endpoint);

            return $this->formatSeriesList($response['data'] ?? []);
        });
    }

    /**
     * Fetch latest series updates.
     *
     * @return array{items: array<int, array<string, mixed>>, page: int, take: int}
     */
    public function getLatestUpdates(int $page = 1, int $take = 24): array
    {
        return $this->getSeries([
            'sort' => 'latest',
            'sortOrder' => 'desc',
        ], $page, $take);
    }

    /**
     * Fetch series with filters and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: array<int, array<string, mixed>>, page: int, take: int}
     */
    public function getSeries(array $filters = [], int $page = 1, int $take = 24): array
    {
        $cacheKey = 'voratoon:series:'.md5(serialize($filters)."-p{$page}-t{$take}");

        return Cache::remember($cacheKey, 300, function () use ($filters, $page, $take): array {
            $queryParams = [
                'page' => $page,
                'take' => $take,
                'includeMeta' => 1,
                'takeChapter' => 1,
            ];

            if (! empty($filters['title'])) {
                $queryParams['title'] = trim($filters['title']);
            }

            if (! empty($filters['sort'])) {
                $queryParams['sort'] = $filters['sort'];
                $queryParams['sortOrder'] = $filters['sortOrder'] ?? 'desc';
            }

            $filterParts = [];
            if (! empty($filters['status']) && $filters['status'] !== 'all') {
                $status = strtolower($filters['status']);
                if ($status === 'canceled') {
                    $status = 'cancelled';
                }
                $filterParts[] = "status=={$status}";
            }

            if (! empty($filters['format']) && $filters['format'] !== 'all') {
                $filterParts[] = 'format=='.strtolower($filters['format']);
            }

            if (! empty($filters['type']) && $filters['type'] !== 'all') {
                $filterParts[] = 'type=='.strtolower($filters['type']);
            }

            if (! empty($filterParts)) {
                $queryParams['filter'] = implode(';', $filterParts);
            }

            if (! empty($filters['genreIds'])) {
                $queryParams['genreIds'] = is_array($filters['genreIds'])
                    ? implode(',', $filters['genreIds'])
                    : $filters['genreIds'];
            }

            if (! empty($filters['minimumChapters'])) {
                $queryParams['minimumChapters'] = (int) $filters['minimumChapters'];
            }

            $response = $this->request('/series', $queryParams);
            $items = $this->formatSeriesList($response['data'] ?? []);

            return [
                'items' => $items,
                'page' => $page,
                'take' => $take,
            ];
        });
    }

    /**
     * Fetch all genres.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getGenres(): array
    {
        return Cache::remember('voratoon:genres', 86400, function (): array {
            $response = $this->request('/genres');
            $data = $response['data'] ?? [];

            $genres = [];
            foreach ($data as $item) {
                $name = $item['data']['name'] ?? null;
                if ($name) {
                    $genres[] = [
                        'id' => (int) $item['id'],
                        'name' => $name,
                    ];
                }
            }

            usort($genres, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            return $genres;
        });
    }

    /**
     * Fetch series detail by slug.
     *
     * @return array<string, mixed>|null
     */
    public function getSeriesDetail(string $slug): ?array
    {
        $cacheKey = "voratoon:series:detail:{$slug}";

        return Cache::remember($cacheKey, 1800, function () use ($slug): ?array {
            $response = $this->request('/series/'.urlencode($slug));
            $data = $response['data'] ?? null;

            if (! $data) {
                return null;
            }

            return $this->formatSingleSeries($data);
        });
    }

    /**
     * Fetch all chapters for a series.
     *
     * @return array<int, array{id: int, index: string, title: string, date: ?string}>
     */
    public function getSeriesChapters(string $slug): array
    {
        $cacheKey = "voratoon:series:chapters:{$slug}";

        return Cache::remember($cacheKey, 600, function () use ($slug): array {
            $response = $this->request('/series/'.urlencode($slug).'/chapters');
            $data = $response['data'] ?? [];

            $chapters = [];
            foreach ($data as $item) {
                $index = (string) ($item['data']['index'] ?? '');
                $chapters[] = [
                    'id' => (int) $item['id'],
                    'index' => $index,
                    'title' => $item['data']['title'] ?: ($index !== '' ? "Chapter {$index}" : 'Chapter'),
                    'date' => $item['createdAt'] ?? null,
                ];
            }

            return $chapters;
        });
    }

    /**
     * Fetch single chapter data with images.
     *
     * @return array{id: ?int, index: string, title: string, images: array<int, string>, date: ?string}|null
     */
    public function getChapter(string $slug, string|int $chapterIndex): ?array
    {
        $chapterIndex = (string) $chapterIndex;
        $cacheKey = "voratoon:chapter:{$slug}:{$chapterIndex}";

        return Cache::remember($cacheKey, 3600, function () use ($slug, $chapterIndex): ?array {
            $response = $this->request('/series/'.urlencode($slug).'/chapters/'.urlencode($chapterIndex));
            $chapter = $response['data'] ?? null;

            if (! $chapter) {
                return null;
            }

            $images = [];
            if (! empty($chapter['data']['images'])) {
                $images = array_values($chapter['data']['images']);
            } elseif (! empty($chapter['dataImages'])) {
                $images = array_values($chapter['dataImages']);
            }

            $chIndex = (string) ($chapter['chapterIndex'] ?? $chapter['data']['index'] ?? $chapterIndex);

            return [
                'id' => $chapter['id'] ?? null,
                'index' => $chIndex,
                'title' => $chapter['data']['title'] ?: "Chapter {$chIndex}",
                'images' => $images,
                'date' => $chapter['createdAt'] ?? null,
            ];
        });
    }

    /**
     * Helper to perform HTTP GET request.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function request(string $endpoint, array $query = []): array
    {
        $url = $this->baseUrl.'/'.ltrim($endpoint, '/');

        try {
            $response = Http::timeout(15)
                ->retry(2, 150)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) BacaMangaApp/1.0',
                    'Referer' => 'https://v2.voratoon.com/',
                ])
                ->get($url, $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning("Voratoon API error [{$response->status()}] on {$url}: {$response->body()}");
        } catch (\Throwable $e) {
            Log::error("Voratoon API exception on {$url}: {$e->getMessage()}");
        }

        return [];
    }

    /**
     * Format a list of series items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function formatSeriesList(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $formatted = $this->formatSingleSeries($item);
            if ($formatted) {
                $result[] = $formatted;
            }
        }

        return $result;
    }

    /**
     * Format a single series item into a clean array shape.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    public function formatSingleSeries(array $item): ?array
    {
        $data = $item['data'] ?? $item;

        $title = $data['title'] ?? null;
        $slug = $data['slug'] ?? null;

        if (! $title || ! $slug) {
            return null;
        }

        $cover = $data['coverImage'] ?? null;
        if ($cover) {
            // Fix unescaped entities if any
            $cover = str_replace(['&amp;', 'u0026'], '&', $cover);
        }

        $background = $data['backgroundImage'] ?? null;
        if ($background) {
            $background = str_replace(['&amp;', 'u0026'], '&', $background);
        }

        $genres = [];
        if (! empty($data['genres'])) {
            foreach ($data['genres'] as $g) {
                $name = $g['data']['name'] ?? $g['name'] ?? null;
                if ($name) {
                    $genres[] = [
                        'id' => (int) ($g['id'] ?? 0),
                        'name' => $name,
                    ];
                }
            }
        }

        $latestChapter = null;
        if (! empty($item['chapters'][0])) {
            $ch = $item['chapters'][0];
            $latestChapter = [
                'index' => (string) ($ch['chapterIndex'] ?? $ch['data']['index'] ?? ''),
                'title' => $ch['data']['title'] ?? '',
                'date' => $ch['createdAt'] ?? null,
            ];
        }

        return [
            'id' => $item['id'] ?? null,
            'title' => $title,
            'nativeTitle' => $data['nativeTitle'] ?? null,
            'slug' => $slug,
            'cover' => $cover,
            'background' => $background,
            'synopsis' => $data['synopsis'] ?? '',
            'author' => $data['author'] ?? 'Unknown',
            'rating' => isset($data['rating']) ? (float) $data['rating'] : 0,
            'totalChapters' => (string) ($data['totalChapters'] ?? '0'),
            'releaseDate' => (string) ($data['releaseDate'] ?? ''),
            'status' => strtolower($data['status'] ?? 'ongoing'),
            'format' => strtolower($data['format'] ?? 'manhwa'),
            'type' => strtolower($data['type'] ?? 'mirror'),
            'isHot' => (bool) ($data['isHot'] ?? false),
            'genres' => $genres,
            'latestChapter' => $latestChapter,
            'views' => $item['metadata']['views']['total'] ?? $item['dataMetadata']['totalViews'] ?? 0,
        ];
    }
}
