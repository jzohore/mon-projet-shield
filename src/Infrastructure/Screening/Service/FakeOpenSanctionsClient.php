<?php

namespace App\Infrastructure\Screening\Service;

use App\Domain\Port\OpenSanctionsClientInterface;

class FakeOpenSanctionsClient implements OpenSanctionsClientInterface
{
    public function search(string $name, string $schema = 'Person'): array
    {
        $nameLower = strtolower($name);

        // --- SCÉNARIO 1 : PERSONNE SOUS SANCTIONS (ex: si on tape "putin" ou "sanction") ---
        if (str_contains($nameLower, 'putin') || str_contains($nameLower, 'sanction')) {
            return [
                'total_matches' => 1,
                'alerts' => [
                    [
                        'id' => 'Q7747',
                        'name' => 'Vladimir Vladimirovich Putin',
                        'schema' => 'Person',
                        'topics' => ['sanction', 'role.pep'],
                        'positions' => ['President of the Russian Federation'],
                        'birth_dates' => ['1952-10-07'],
                        'incorporation_dates' => [],
                        'notes' => ['Sanctioned due to the invasion of Ukraine.'],
                        'countries' => ['ru'],
                        'registration_numbers' => [],
                        'aliases' => ['Vladimir Poutine', 'Wladimir Putin'],
                        'datasets' => ['us_ofac_sdn', 'eu_fsf', 'wd_peps'],
                        'raw_data' => ['mocked' => true],
                    ],
                ],
            ];
        }

        // --- SCÉNARIO 2 : ENTREPRISE SOUS SANCTIONS (ex: si on tape "gazprom") ---
        if ($schema === 'Company' || str_contains($nameLower, 'gazprom')) {
            return [
                'total_matches' => 1,
                'alerts' => [
                    [
                        'id' => 'company-1234',
                        'name' => 'Gazprom PAO',
                        'schema' => 'Company',
                        'topics' => ['sanction'],
                        'positions' => [],
                        'birth_dates' => [],
                        'incorporation_dates' => ['1989-08-08'],
                        'notes' => ['State-owned multinational energy corporation.'],
                        'countries' => ['ru'],
                        'registration_numbers' => ['OGRN: 1027700070518', 'LEI: 549300H4UUKWNRUB0000'],
                        'aliases' => ['Gazprom', 'OAO Gazprom'],
                        'datasets' => ['us_ofac_sdn', 'gb_hmt_sanctions'],
                        'raw_data' => ['mocked' => true],
                    ],
                ],
            ];
        }

        // --- SCÉNARIO 3 : PEP (Personne Politiquement Exposée) (ex: "macron") ---
        if (str_contains($nameLower, 'macron')) {
            return [
                'total_matches' => 1,
                'alerts' => [
                    [
                        'id' => 'Q8612',
                        'name' => 'Emmanuel Macron',
                        'schema' => 'Person',
                        'topics' => ['role.pep'],
                        'positions' => ['President of the French Republic'],
                        'birth_dates' => ['1977-12-21'],
                        'incorporation_dates' => [],
                        'notes' => [],
                        'countries' => ['fr'],
                        'registration_numbers' => [],
                        'aliases' => ['Emmanuel Jean-Michel Frédéric Macron'],
                        'datasets' => ['wd_peps', 'fr_hatvp'],
                        'raw_data' => ['mocked' => true],
                    ],
                ],
            ];
        }

        // --- SCÉNARIO 4 : AUCUN RÉSULTAT (Profil sain) ---
        return [
            'total_matches' => 0,
            'alerts' => [],
        ];
    }
}
