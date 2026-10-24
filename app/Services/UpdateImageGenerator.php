<?php

namespace App\Services;

use GdImage;

/**
 * Composes a grid of PNG images (part thumbnails) into one release image.
 * Returns PNG bytes; the caller decides where they go (disk, media library, response).
 */
class UpdateImageGenerator
{
    public function __construct(
        protected int $maxItems = 8,
        protected int $maxRows = 2,
        protected int $width = 1200,
        protected int $height = 800,
        protected int $padding = 6,
        protected string $background = '#ffffff',
        protected ?string $caption = null,
        protected string $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        protected int $captionSize = 28,
    ) {
        $this->maxItems = max(1, $this->maxItems);
        $this->maxRows = max(1, $this->maxRows);
    }

    /**
     * @param  array<int, string>  $paths  Absolute paths to PNG files
     * @return string|null PNG binary, or null if no image could be loaded
     */
    public function generate(array $paths): ?string
    {
        $images = $this->loadImages($paths);
        if ($images === []) {
            return null;
        }

        $canvas = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($canvas, true);
        [$r, $g, $b] = $this->hexToRgb($this->background);
        imagefilledrectangle($canvas, 0, 0, $this->width, $this->height, imagecolorallocate($canvas, $r, $g, $b));

        $top = $this->drawCaption($canvas);

        $perRow = (int) ceil(count($images) / $this->maxRows);
        $rows = array_chunk($images, $perRow);

        // Lay out: each row is as tall as the width allows, capped so all rows fit.
        $areaHeight = $this->height - $top - $this->padding;
        $maxRowHeight = ($areaHeight - $this->padding * (count($rows) - 1)) / count($rows);

        $rowHeights = array_map(function (array $row) use ($maxRowHeight): int {
            $arSum = array_sum(array_column($row, 'ar'));
            $fitWidth = ($this->width - $this->padding * (count($row) + 1)) / $arSum;

            return max(1, (int) floor(min($maxRowHeight, $fitWidth)));
        }, $rows);

        // Centre the block of rows vertically in the area below the caption.
        $blockHeight = array_sum($rowHeights) + $this->padding * (count($rows) - 1);
        $y = $top + max(0, intdiv($areaHeight - $blockHeight, 2));

        foreach ($rows as $i => $row) {
            $rowHeight = $rowHeights[$i];
            $widths = array_map(fn (array $img): int => max(1, (int) round($rowHeight * $img['ar'])), $row);

            // Centre each row horizontally.
            $rowWidth = array_sum($widths) + $this->padding * (count($row) - 1);
            $x = max(0, intdiv($this->width - $rowWidth, 2));

            foreach ($row as $j => $img) {
                imagecopyresampled($canvas, $img['res'], $x, $y, 0, 0, $widths[$j], $rowHeight, $img['w'], $img['h']);
                $x += $widths[$j] + $this->padding;
            }

            $y += $rowHeight + $this->padding;
        }

        ob_start();
        imagepng($canvas);

        return ob_get_clean() ?: null;
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, array{res: GdImage, w: int, h: int, ar: float}>
     */
    protected function loadImages(array $paths): array
    {
        $images = [];

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
                continue;
            }

            $res = @imagecreatefrompng($path);
            if ($res === false) {
                continue;
            }

            imagealphablending($res, true);
            $images[] = ['res' => $res, 'w' => imagesx($res), 'h' => imagesy($res), 'ar' => imagesx($res) / imagesy($res)];

            // Limit applies to images actually loaded, not requested paths.
            if (count($images) >= $this->maxItems) {
                break;
            }
        }

        return $images;
    }

    /**
     * Draws the caption centred at the top. Returns the y offset where the image grid begins.
     */
    protected function drawCaption(GdImage $canvas): int
    {
        if ($this->caption === null || $this->caption === '' || ! is_file($this->font)) {
            return $this->padding;
        }

        $bbox = imagettfbbox($this->captionSize, 0, $this->font, $this->caption);
        if ($bbox === false) {
            return $this->padding;
        }

        $textW = $bbox[2] - $bbox[0];
        $textH = $bbox[1] - $bbox[7]; // bottom-left y minus top-left y
        $x = (int) round(($this->width - $textW) / 2 - $bbox[0]);

        // bbox[7] is negative (glyph top above baseline), so this puts the glyph top at $padding.
        imagettftext($canvas, $this->captionSize, 0, $x, (int) ($this->padding - $bbox[7]), imagecolorallocate($canvas, 0, 0, 0), $this->font, $this->caption);

        return (int) ($this->padding * 2 + $textH);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
