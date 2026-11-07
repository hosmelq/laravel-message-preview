<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Compatibility;

use HosmelQ\CanIEmail\CanIEmail;
use HosmelQ\CanIEmail\Enums\ClientFamily;
use HosmelQ\CanIEmail\Enums\Support;
use HosmelQ\CanIEmail\Feature;
use HosmelQ\CanIEmail\Issue;

final readonly class MessageCompatibilityChecker
{
    public function __construct(
        private CanIEmail $canIEmail,
        private ClientFamilies $clientFamilies,
    ) {}

    /**
     * @param array<ClientFamily> $families
     *
     * @return list<array{clients: list<ClientSupport>, feature: Feature}>
     */
    public function check(string $html, array $families): array
    {
        $chosen = array_intersect_key(
            $this->clientFamilies->all(),
            array_flip(
                array_map(static fn (ClientFamily $family): string => $family->value, $families),
            ),
        );
        $issues = [];
        $results = [];

        foreach ($this->canIEmail->check(
            $html,
            array_map(ClientFamily::from(...), array_keys($chosen)),
        ) as $issue) {
            $issues[$issue->feature->slug][] = $issue;
        }

        foreach ($issues as $featureIssues) {
            $clients = [];

            foreach ($chosen as $family => $name) {
                $clients[] = $this->support(
                    $family,
                    $name,
                    array_filter(
                        $featureIssues,
                        static fn (Issue $issue): bool => $issue->client->family === $family,
                    ),
                );
            }

            if (array_any($clients, static fn (ClientSupport $client): bool => in_array(
                $client->level,
                [Support::None, Support::Partial],
                true,
            ))) {
                $results[] = ['clients' => $clients, 'feature' => $featureIssues[0]->feature];
            }
        }

        usort(
            $results,
            static fn (array $a, array $b): int => self::lacking($b) <=> self::lacking($a),
        );

        return $results;
    }

    /**
     * @param array{clients: list<ClientSupport>, feature: Feature} $result
     *
     * @return array{int, int}
     */
    private static function lacking(array $result): array
    {
        $levels = array_map(
            static fn (ClientSupport $client): Support => $client->level,
            $result['clients'],
        );

        return [
            count(array_keys($levels, Support::None, true)),
            count(array_keys($levels, Support::Partial, true)),
        ];
    }

    /**
     * @param array<int, Issue> $issues
     */
    private function support(string $family, string $name, array $issues): ClientSupport
    {
        $platforms = static fn (Support $support): array => array_values(array_map(
            static fn (Issue $issue): string => $issue->client->platformName,
            array_filter($issues, static fn (Issue $issue): bool => $issue->support === $support),
        ));
        $limited = $platforms(Support::Partial);
        $missing = $platforms(Support::None);
        $unknown = $platforms(Support::Unknown);

        return new ClientSupport(
            $family,
            match (true) {
                $missing !== [] => Support::None,
                $limited !== [] => Support::Partial,
                $unknown !== [] => Support::Unknown,
                default => Support::Full,
            },
            $limited,
            $missing,
            $name,
            array_values(array_unique(array_merge(...array_map(
                static fn (Issue $issue): array => $issue->notes,
                $issues,
            )))),
            $unknown,
        );
    }
}
