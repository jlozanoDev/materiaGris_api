<?php

namespace App\DTOs;

readonly class PdfFileInfo
{
    public function __construct(
        public string $path,
        public string $filename,
    ) {}
}
