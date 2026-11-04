<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckMessageLinksRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'html' => ['required', 'string'],
        ];
    }
}
