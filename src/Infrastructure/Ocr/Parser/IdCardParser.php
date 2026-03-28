<?php

namespace App\Infrastructure\Ocr\Parser;

use App\Domain\Kyc\Enum\DocumentType;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;
use Mindee\Parsing\V2\InferenceResponse;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.mindee_parser')]
class IdCardParser extends AbstractMindeeParser
{
    public function supports(DocumentType $type): bool
    {
        return $type === DocumentType::ID_CARD;
    }

    protected function callApi(string $absolutePath): object
    {
        $inputSource = new PathInput($absolutePath);

        $inferenceParams = new InferenceParameters('220951a2-6a24-4240-93e8-252e5d358bc5');

        $response = $this->client->enqueueAndGetResult(InferenceResponse::class, $inputSource, $inferenceParams);

        return $response->inference;
    }

    /**
     * @param object{
     *     result: object{
     *         fields: array<string, object{
     *             value?: string|null,
     *             fields?: array<string, object{
     *                 value?: string|null
     *             }>
     *         }>
     *     }
     * } $prediction
     *
     * @return array<string, mixed>
     */
    protected function formatData(object $prediction): array
    {
        $fields = $prediction->result->fields;

        return [
            'first_name' => $fields['given_names']->value ?? null,
            'last_name' => $fields['surnames']->value ?? null,
            'birth_date' => $fields['date_of_birth']->value ?? null,
            'birth_place' => $fields['place_of_birth']->value ?? null,
            'id_number' => $fields['document_number']->value ?? null,
            'nationality' => $fields['nationality']->value ?? null,
            'gender' => $fields['sex']->value ?? null,
            'document_type' => $fields['document_type']->value ?? null,
            'address' => [
                'city' => $fields['address']->fields['city']->value ?? null,
                'department' => $fields['address']->fields['state']->value ?? null,
                'full_address' => $fields['address']->value ?? null,
            ],
            'mrz' => [
                'line_1' => $fields['mrz']->fields['line_1']->value ?? null,
                'line_2' => $fields['mrz']->fields['line_2']->value ?? null,
            ],
        ];
    }
}
