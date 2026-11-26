<?php

use App\Events\PartSubmitted;
use App\Models\User;
use App\Services\LDraw\LDrawFile;
use App\Services\Part\Submit\Registrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @return list<array{red: int, green: int, blue: int, alpha: int}>
 */
function texmapPixels(string $png): array
{
    $image = imagecreatefromstring($png);
    $pixels = [];
    for ($y = 0; $y < imagesy($image); $y++) {
        for ($x = 0; $x < imagesx($image); $x++) {
            $pixels[] = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        }
    }

    return $pixels;
}

it('losslessly optimizes a submitted texmap body', function () {
    Queue::fake();
    Event::fake([PartSubmitted::class]);

    $image = imagecreatetruecolor(32, 32);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    for ($y = 0; $y < 32; $y++) {
        for ($x = 0; $x < 32; $x++) {
            imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $x * 8, $y * 8, 128, ($x + $y) % 128));
        }
    }
    ob_start();
    imagepng($image, null, 0);
    $uncompressed = ob_get_clean();

    $parts = app(Registrar::class)->submit(
        collect([LDrawFile::fromArray(['mimetype' => 'image/png', 'filename' => 'texture.png', 'contents' => $uncompressed])]),
        User::factory()->create(),
    );

    $body = $parts->first()->get();
    expect(strlen($body))->toBeLessThan(strlen($uncompressed))
        ->and(texmapPixels($body))->toBe(texmapPixels($uncompressed));
});
