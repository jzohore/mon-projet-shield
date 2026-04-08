<?php

namespace App\Infrastructure\Ocr\Parser;

use App\Domain\Kyc\Enum\DocumentType;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;
use Mindee\Parsing\V2\InferenceResponse;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @phpstan-type OcrItem object{fields: object}
 * @phpstan-type OcrFields object{
 * management?: object{items?: array<OcrItem>},
 * establishments?: object{items?: array<OcrItem>},
 * company_name?: object{value?: string},
 * registration_number?: object{value?: string},
 * legal_form?: object{value?: string},
 * share_capital?: object{value?: string},
 * date_of_registration?: object{value?: string},
 * registered_address?: object{value?: string},
 * duration_of_company?: object{value?: string},
 * activity_code_ape?: object{value?: string}
 * }
 * @phpstan-type OcrPrediction object{result?: object{fields: OcrFields}, fields?: OcrFields}
 */
#[AutoconfigureTag('app.mindee_parser')]
class KbisParser extends AbstractMindeeParser
{
    public function supports(DocumentType $type): bool
    {
        return $type === DocumentType::KBIS;
    }

    protected function callApi(string $absolutePath): object
    {
        $inputSource = new PathInput($absolutePath);

        $inferenceParams = new InferenceParameters('c151d6f2-9ca2-4b89-82d7-3d0291c7b7b8');

        $response = $this->client->enqueueAndGetResult(InferenceResponse::class, $inputSource, $inferenceParams);

        return $response->inference;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatData(object $prediction): array
    {
        // Dans une API Custom, les champs sont parfois directement à la racine de la prédiction
        // On utilise l'opérateur "null coalescing" pour sécuriser l'accès
        /** @var OcrFields|null $fields */
        $fields = $prediction->result->fields ?? $prediction->fields ?? null;

        // 1. Extraction des dirigeants (Boucle sur 'items')
        $management = [];
        if (isset($fields->management->items)) {
            foreach ($fields->management->items as $item) {
                $management[] = [
                    'name' => $item->fields->manager_name->value ?? null,
                    'role' => $item->fields->manager_role->value ?? null,
                ];
            }
        }

        // 2. Extraction des établissements (Boucle sur 'items')
        $establishments = [];
        if (isset($fields->establishments->items)) {
            foreach ($fields->establishments->items as $item) {
                $establishments[] = [
                    'address'  => $item->fields->establishment_address->value ?? null,
                    'activity' => $item->fields->establishment_activity->value ?? null,
                ];
            }
        }

        // 3. Retour du tableau structuré global
        return [
            'company_name'         => $fields->company_name->value ?? null,
            'registration_number'  => $fields->registration_number->value ?? null, // ex: 429 225 683 R.C.S. Bobigny
            'legal_form'           => $fields->legal_form->value ?? null,          // ex: SAS
            'share_capital'        => $fields->share_capital->value ?? null,       // ex: 762500
            'date_of_registration' => $fields->date_of_registration->value ?? null,// ex: 2000-01-27
            'registered_address'   => $fields->registered_address->value ?? null,  // ex: 47 Allée des Impressionnistes...
            'duration_of_company'  => $fields->duration_of_company->value ?? null, // ex: Jusqu'au 27/01/2099
            'activity_code_ape'    => $fields->activity_code_ape->value ?? null,

            // On injecte nos tableaux formatés
            'management'           => $management,
            'establishments'       => $establishments,
        ];
    }
}
