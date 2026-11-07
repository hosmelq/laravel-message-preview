<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

final class CompatibilityIssueCollection extends ResourceCollection
{
    public static $wrap = 'data';
}
