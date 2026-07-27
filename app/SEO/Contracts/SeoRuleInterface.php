<?php

namespace App\SEO\Contracts;

use App\SEO\DTO\SeoResult;

interface SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void;
}