<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Requests;

use HosmelQ\MessagePreview\Support\PreviewLocales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PreviewRequest extends FormRequest
{
    public function locale(): null|string
    {
        return $this->filled('locale') ? $this->string('locale')->value() : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(PreviewLocales $locales): array
    {
        return [
            'locale' => ['nullable', Rule::in($locales->all())],
        ];
    }
}
