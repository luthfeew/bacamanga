<?php

use App\Services\VoratoonService;
use Illuminate\Support\Facades\Http;

test('home page returns successful response and displays branding', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('BacaManga');
    $response->assertSee('Populer Minggu Ini');
    $response->assertSee('Rilis Bab Terbaru');
});

test('browse page returns successful response and shows filters', function () {
    $response = $this->get(route('browse'));

    $response->assertOk();
    $response->assertSee('Jelajah Komik');
    $response->assertSee('Semua Format');
    $response->assertSee('Semua Status');
});

test('library page returns successful response and shows tabs', function () {
    $response = $this->get(route('library'));

    $response->assertOk();
    $response->assertSee('Koleksi & Riwayat Baca');
    $response->assertSee('Bookmark');
    $response->assertSee('Riwayat Baca');
});

test('series detail page returns 404 for invalid series slug', function () {
    Http::fake([
        '*/series/nonexistent-manga-slug' => Http::response(null, 404),
    ]);

    $response = $this->get(route('series.show', ['slug' => 'nonexistent-manga-slug']));

    $response->assertNotFound();
});

test('reader page returns 404 for invalid series or chapter', function () {
    Http::fake([
        '*/series/nonexistent-series' => Http::response(null, 404),
    ]);

    $response = $this->get(route('series.chapter', [
        'slug' => 'nonexistent-series',
        'chapterIndex' => '1',
    ]));

    $response->assertNotFound();
});

test('voratoon service correctly formats series data', function () {
    $service = new VoratoonService;

    $mockRaw = [
        'id' => 'abc-123',
        'data' => [
            'title' => 'Solo Leveling',
            'slug' => 'solo-leveling',
            'coverImage' => 'https://cvr.voratoon.id/cover.webp',
            'synopsis' => 'Hunter terlemah menjadi terkuat.',
            'author' => 'Chugong',
            'rating' => 9.8,
            'status' => 'completed',
            'format' => 'manhwa',
            'genres' => [
                ['id' => '1', 'data' => ['name' => 'Action']],
                ['id' => '2', 'data' => ['name' => 'Fantasy']],
            ],
        ],
        'chapters' => [
            ['chapterIndex' => 1, 'data' => ['title' => 'Chapter 1', 'slug' => 'ch-1']],
        ],
    ];

    $formatted = $service->formatSingleSeries($mockRaw);

    expect($formatted['title'])->toBe('Solo Leveling');
    expect($formatted['slug'])->toBe('solo-leveling');
    expect($formatted['author'])->toBe('Chugong');
    expect($formatted['status'])->toBe('completed');
    expect($formatted['format'])->toBe('manhwa');
    expect($formatted['genres'])->toHaveCount(2);
    expect($formatted['genres'][0]['name'])->toBe('Action');
    expect($formatted['latestChapter']['index'])->toBe('1');
});

test('image proxy successfully streams voratoon images', function () {
    Http::fake([
        'https://cdn.voratoon.com/*' => Http::response('fake-image-binary-data', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $response = $this->get(route('proxy.image', [
        'url' => 'https://cdn.voratoon.com/wp-content/img/A/test.jpg',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
    expect($response->getContent())->toBe('fake-image-binary-data');
});

test('image proxy rejects unauthorized domains', function () {
    $response = $this->get(route('proxy.image', [
        'url' => 'https://evil.com/malicious.jpg',
    ]));

    $response->assertForbidden();
});
