<?php

namespace App\LDraw;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LDrawFile
{
    private function __construct(
        public ?string $mimetype,
        public string $filename,
        public string $contents,
    ) {
    }

    public static function fromArray(array $file): self
    {
        return new self(
            $file['mimetype'],
            $file['filename'],
            $file['contents']
        );
    }

    public static function fromUploadedFile(TemporaryUploadedFile $file): self
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file->getPath() . '/' . $file->getFilename());
        switch ($mimetype) {
            case 'text/plain':
                break;
            case 'image/png':
                if (!$img = @imagecreatefrompng($file->getPath() . '/' . $file->getFilename())) {
                    $mimetype = null;
                }
                break;
            default:
                $mimetype = null;
        }
        return new self(
            $mimetype,
            $file->getClientOriginalName(),
            $file->get()
        );
    }
}
