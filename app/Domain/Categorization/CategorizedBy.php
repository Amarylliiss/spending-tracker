<?php

namespace App\Domain\Categorization;

enum CategorizedBy: string
{
    case Manual = 'manual';
    case Rule = 'rule';
    case None = 'none';
}
