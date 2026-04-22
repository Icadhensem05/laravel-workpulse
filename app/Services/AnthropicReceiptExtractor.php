<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AnthropicReceiptExtractor
{
    public function extract(UploadedFile $file, array $categories): array
    {
        $apiKey = (string) config('services.anthropic.api_key');
        $model = (string) config('services.anthropic.model');
        $baseUrl = rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com/v1'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
        $fileBlock = $this->buildFileContentBlock($file, $mimeType);
        $categoryCodes = array_values(array_unique(array_filter(array_map(
            fn (array $category): string => (string) ($category['code'] ?? ''),
            $categories
        ))));

        $response = Http::baseUrl($baseUrl)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(90)
            ->acceptJson()
            ->post('/messages', [
                'model' => $model,
                'max_tokens' => 900,
                'tool_choice' => [
                    'type' => 'tool',
                    'name' => 'extract_claim_receipt',
                ],
                'tools' => [[
                    'name' => 'extract_claim_receipt',
                    'description' => 'Extract a structured employee-claim draft from a receipt or invoice image/PDF.',
                    'input_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'merchant_name' => ['type' => 'string'],
                            'claim_date' => ['type' => 'string', 'description' => 'Receipt date in YYYY-MM-DD when available.'],
                            'claim_month' => ['type' => 'string', 'description' => 'Claim month in YYYY-MM when claim_date is known.'],
                            'item_date' => ['type' => 'string', 'description' => 'Line-item date in YYYY-MM-DD when available.'],
                            'suggested_category_code' => [
                                'type' => 'string',
                                'enum' => $categoryCodes !== [] ? $categoryCodes : ['miscellaneous'],
                            ],
                            'description' => ['type' => 'string'],
                            'purpose' => ['type' => 'string'],
                            'amount' => ['type' => 'number'],
                            'receipt_no' => ['type' => 'string'],
                            'invoice_no' => ['type' => 'string'],
                            'hotel_name' => ['type' => 'string'],
                            'remarks' => ['type' => 'string'],
                            'confidence' => ['type' => 'number'],
                            'warnings' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['suggested_category_code', 'description', 'amount'],
                        'additionalProperties' => false,
                    ],
                ]],
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        $fileBlock,
                        [
                            'type' => 'text',
                            'text' => $this->buildPrompt($categoryCodes),
                        ],
                    ],
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('error.message')
                ?: $response->json('message')
                ?: 'Anthropic receipt extraction failed.'
            );
        }

        $toolUse = collect($response->json('content', []))
            ->first(fn (array $block): bool => ($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === 'extract_claim_receipt');

        if (! is_array($toolUse) || ! is_array($toolUse['input'] ?? null)) {
            throw new RuntimeException('Anthropic did not return a structured receipt extraction result.');
        }

        return $this->normalizeExtractedInput($toolUse['input'], $categoryCodes);
    }

    private function buildFileContentBlock(UploadedFile $file, string $mimeType): array
    {
        $data = base64_encode($file->get());

        if ($mimeType === 'application/pdf') {
            return [
                'type' => 'document',
                'source' => [
                    'type' => 'base64',
                    'media_type' => 'application/pdf',
                    'data' => $data,
                ],
            ];
        }

        if (! Str::startsWith($mimeType, 'image/')) {
            throw new RuntimeException('Only image and PDF receipts are supported.');
        }

        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $mimeType,
                'data' => $data,
            ],
        ];
    }

    private function buildPrompt(array $categoryCodes): string
    {
        $codes = $categoryCodes !== [] ? implode(', ', $categoryCodes) : 'miscellaneous';

        return implode("\n", [
            'Read this receipt or invoice and prepare a claim draft for a staff expense form.',
            'Use only the categories listed here: '.$codes.'.',
            'Choose the closest category code from the list.',
            'Return the merchant, date, amount, document numbers, a concise description, and practical remarks.',
            'If a field is missing, return an empty string for text, 0 for amount, and [] for warnings.',
            'Do not invent mileage, toll, parking, or quantity values from a normal receipt.',
            'Set claim_month from claim_date when possible.',
        ]);
    }

    private function normalizeExtractedInput(array $input, array $categoryCodes): array
    {
        $claimDate = $this->normalizeDate($input['claim_date'] ?? null);
        $itemDate = $this->normalizeDate($input['item_date'] ?? null) ?: $claimDate;
        $claimMonth = $this->normalizeMonth($input['claim_month'] ?? null)
            ?: ($claimDate ? substr($claimDate, 0, 7) : null);

        $categoryCode = (string) ($input['suggested_category_code'] ?? 'miscellaneous');
        if (! in_array($categoryCode, $categoryCodes, true)) {
            $categoryCode = in_array('miscellaneous', $categoryCodes, true) ? 'miscellaneous' : ($categoryCodes[0] ?? 'miscellaneous');
        }

        $warnings = collect($input['warnings'] ?? [])
            ->filter(fn ($warning) => is_string($warning) && trim($warning) !== '')
            ->values()
            ->all();

        return [
            'merchant_name' => trim((string) ($input['merchant_name'] ?? '')),
            'claim_date' => $claimDate,
            'claim_month' => $claimMonth,
            'item_date' => $itemDate,
            'suggested_category_code' => $categoryCode,
            'description' => trim((string) ($input['description'] ?? '')),
            'purpose' => trim((string) ($input['purpose'] ?? '')),
            'amount' => round(max(0, (float) ($input['amount'] ?? 0)), 2),
            'receipt_no' => trim((string) ($input['receipt_no'] ?? '')),
            'invoice_no' => trim((string) ($input['invoice_no'] ?? '')),
            'hotel_name' => trim((string) ($input['hotel_name'] ?? '')),
            'remarks' => trim((string) ($input['remarks'] ?? '')),
            'confidence' => max(0, min(1, (float) ($input['confidence'] ?? 0))),
            'warnings' => $warnings,
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizeMonth(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
