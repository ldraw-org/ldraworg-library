<?php

namespace App\Services\LDraw;

use App\Enums\LdrawFileType;
use App\Models\Part\Part;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LDrawFile
{
    private function __construct(
        public LdrawFileType $filetype,
        public string $filename,
        public string $contents,
    ) {
    }

    public static function fromArray(array $file): self
    {
        return new self(
            $file['filetype'],
            $file['filename'],
            $file['contents']
        );
    }

    public static function fromUploadedFile(TemporaryUploadedFile $file): self
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file->getPath() . '/' . $file->getFilename());

        $filetype = LdrawFileType::tryFrom($mimetype);
        if ($filetype == LdrawFileType::Image) {
            // Check if the image is valid
            imagecreatefrompng($file->getPath() . '/' . $file->getFilename());
        }
        return new self(
            $filetype,
            $file->getClientOriginalName(),
            $file->get()
        );
    }

    public static function fromPart(Part $part): self
    {
        return new self(
            $part->isTexmap() ? LdrawFileType::Image : LdrawFileType::TextFile,
            basename($part->filename),
            $part->get()
        );
    }
}
