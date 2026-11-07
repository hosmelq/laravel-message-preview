<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Requests;

use HosmelQ\CanIEmail\Enums\ClientFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckMessageCompatibilityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clients' => ['present', 'list'],
            'clients.*' => [Rule::enum(ClientFamily::class)],
            'html' => ['required', 'string'],
        ];
    }
}
