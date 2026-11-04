<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Resources\CatalogResource;
use HosmelQ\MessagePreview\MessageCatalog;

final readonly class MessageController
{
    public function index(MessageCatalog $catalog): CatalogResource
    {
        return new CatalogResource($catalog->all());
    }
}
