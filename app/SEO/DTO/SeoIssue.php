<?php

namespace App\SEO\DTO;

class SeoIssue
{
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public string $suggestion,
        public bool $passed = false
    ) {}
}
