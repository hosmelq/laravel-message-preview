<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\CanIEmail\Enums\ClientFamily;
use HosmelQ\MessagePreview\Compatibility\MessageCompatibilityChecker;
use HosmelQ\MessagePreview\Http\Requests\CheckMessageCompatibilityRequest;
use HosmelQ\MessagePreview\Http\Resources\CompatibilityIssueCollection;

final readonly class CheckMessageCompatibilityController
{
    public function __invoke(
        CheckMessageCompatibilityRequest $request,
        MessageCompatibilityChecker $checker,
    ): CompatibilityIssueCollection {
        $issues = $checker->check(
            $request->string('html')->value(),
            $request->enums('clients', ClientFamily::class),
        );

        return new CompatibilityIssueCollection($issues);
    }
}
