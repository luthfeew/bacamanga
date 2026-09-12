<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ImageProxyController extends Controller
{
    /**
     * Proxies Voratoon image requests to bypass Referer/CORS hotlink blocking.
     */
    public function __invoke(Request $request): Response
    {
        $url = $request->query('url');

        if (! $url || ! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid image URL');
        }

        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');

        // Whitelist allowed Voratoon domains
        $allowedDomains = [
            'cdn.voratoon.com',
            'cvr.voratoon.id',
            'v2.voratoon.com',
            'voratoon.com',
        ];

        $isAllowed = false;
        foreach ($allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            abort(403, 'Unauthorized image host');
        }

        $cacheKey = 'img_proxy:'.md5($url);

        $cached = Cache::get($cacheKey);
        if ($cached && isset($cached['content'], $cached['type'])) {
            return response($cached['content'], 200)
                ->header('Content-Type', $cached['type'])
                ->header('Cache-Control', 'public, max-age=604800, immutable');
        }

        try {
            $response = Http::withHeaders([
                'Referer' => 'https://v2.voratoon.com/',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            ])->timeout(20)->get($url);

            if (! $response->successful()) {
                abort($response->status(), 'Failed to retrieve image from source');
            }

            $content = $response->body();
            $contentType = $response->header('Content-Type') ?: 'image/jpeg';

            // Cache images up to 2.5MB in size for 7 days
            if (strlen($content) <= 2.5 * 1024 * 1024) {
                Cache::put($cacheKey, [
                    'content' => $content,
                    'type' => $contentType,
                ], now()->addDays(7));
            }

            return response($content, 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=604800, immutable');
        } catch (\Throwable $e) {
            abort(502, 'Image proxy error: '.$e->getMessage());
        }
    }
}
