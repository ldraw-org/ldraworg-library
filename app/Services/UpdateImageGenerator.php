<?php

namespace App\Services;

class UpdateImageGenerator
{
    protected int $maxRowHeight = 0;
    protected string $defaultFont;

    public function __construct(
        protected int $maxItems = 8,
        protected int $maxRows = 2,
        protected int $outW = 1200,
        protected int $outH = 800,
        protected int $padding = 6,
        protected string $bgHex = '#ffffff',
        protected ?string $caption = null,
        protected ?string $captionFont = null,
        protected int $captionSize = 28,
        protected int $captionMargin = 18
    ) {
        $this->maxItems = max(1, $this->maxItems);
        $this->maxRows = max(1, $this->maxRows);
        $this->captionFont = $this->captionFont ?? '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $this->defaultFont = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    }

    public function maxItems(int $n): self
    {
        $this->maxItems = max(1, $n);
        return $this;
    }

    public function maxRows(int $n): self
    {
        $this->maxRows = max(1, $n);
        return $this;
    }

    public function outputSize(int $w, int $h): self
    {
        $this->outW = $w;
        $this->outH = $h;
        return $this;
    }

    public function padding(int $p): self
    {
        $this->padding = $p;
        return $this;
    }

    public function bg(string $hex): self
    {
        $this->bgHex = $hex;
        return $this;
    }

    public function caption(?string $txt, ?string $font = null, int $size = 28): self
    {
        if ($txt !== null && $txt !== '') {
            $this->caption = $txt;
            $this->captionFont = $font ?? $this->defaultFont;
            $this->captionSize = $size;
        }
        return $this;
    }

    public function captionMargin(int $m): self
    {
        $this->captionMargin = $m;
        return $this;
    }

    public function generate(array $paths, ?string $saveTo = null): bool
    {
        $paths = array_values(array_filter($paths, fn ($p) => is_string($p) && $p !== ''));
        if (count($paths) === 0) {
            return false;
        }

        $paths = array_slice($paths, 0, $this->maxItems);

        $images = [];
        foreach ($paths as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $res = @imagecreatefrompng($path);
            if ($res === false) {
                continue;
            }

            imagesavealpha($res, true);
            $images[] = [
                'res' => $res,
                'w' => imagesx($res),
                'h' => imagesy($res),
                'ar' => imagesx($res) / imagesy($res)
            ];
        }

        if (count($images) === 0) {
            return false;
        }

        $captionHeight = 0;
        if ($this->captionFont !== null && function_exists('imagettfbbox') && $this->caption !== null) {
            $bbox = @imagettfbbox($this->captionSize, 0, $this->captionFont, $this->caption);
            if ($bbox !== false) {
                $textH = abs($bbox[5] - $bbox[1]);
                $captionHeight = $textH + $this->captionMargin + $this->padding;
            }
        }

        $availableHeight = $this->outH - $captionHeight - ($this->padding * ($this->maxRows + 1));
        $this->maxRowHeight = (int) floor($availableHeight / $this->maxRows);

        $canvas = imagecreatetruecolor($this->outW, $this->outH);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);

        $bg = $this->hexToRgb($this->bgHex);
        $bgColor = imagecolorallocatealpha($canvas, $bg['r'], $bg['g'], $bg['b'], 0);
        imagefilledrectangle($canvas, 0, 0, $this->outW, $this->outH, $bgColor);

        if ($this->caption !== null && $this->caption !== '') {
            if ($this->captionFont !== null && function_exists('imagettftext')) {
                $bbox = @imagettfbbox($this->captionSize, 0, $this->captionFont, $this->caption);
                if ($bbox !== false) {
                    $textW = abs($bbox[2] - $bbox[0]);
                    $textH = abs($bbox[5] - $bbox[1]);
                    $cx = (int) (($this->outW - $textW) / 2);
                    $cy = $this->padding + $textH;
                    $black = imagecolorallocate($canvas, 0, 0, 0);
                    imagettftext($canvas, $this->captionSize, 0, $cx, $cy, $black, $this->captionFont, $this->caption);
                }
            } else {
                $black = imagecolorallocate($canvas, 0, 0, 0);
                imagestring($canvas, 5, (int) (($this->outW - strlen($this->caption) * 7) / 2), $this->padding, $this->caption, $black);
            }
        }

        $y = $this->padding + $captionHeight;
        $spacing = $this->padding;
        $row = [];
        $imagesPerRow = (int) ceil(count($images) / $this->maxRows);

        foreach ($images as $idx => $img) {
            $row[] = $img;
            $isRowFull = count($row) >= $imagesPerRow || $idx === array_key_last($images);

            if ($isRowFull) {
                $rowARSum = array_sum(array_map(fn ($i) => $i['ar'], $row));
                $rowHeight = min($this->maxRowHeight, ($this->outW - $spacing * (count($row) + 1)) / $rowARSum);

                $x = $spacing;
                foreach ($row as $i) {
                    $w = (int) ($rowHeight * $i['ar']);
                    $h = (int) $rowHeight;

                    $tmp = imagecreatetruecolor($w, $h);
                    imagesavealpha($tmp, true);
                    imagealphablending($tmp, false);
                    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
                    imagefilledrectangle($tmp, 0, 0, $w, $h, $transparent);

                    imagecopyresampled($tmp, $i['res'], 0, 0, 0, 0, $w, $h, $i['w'], $i['h']);

                    imagealphablending($canvas, true);
                    imagesavealpha($canvas, true);
                    imagecopy($canvas, $tmp, $x, $y, 0, 0, $w, $h);

                    imagedestroy($tmp);
                    $x += $w + $spacing;
                }

                $y += $rowHeight + $spacing;
                $row = [];
            }
        }

        foreach ($images as $i) {
            imagedestroy($i['res']);
        }

        if ($saveTo !== null && $saveTo !== '') {
            $dir = dirname($saveTo);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $ok = imagepng($canvas, $saveTo);
            imagedestroy($canvas);
            return (bool) $ok;
        }

        header('Content-Type: image/png');
        imagepng($canvas);
        imagedestroy($canvas);

        return true;
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }
}
