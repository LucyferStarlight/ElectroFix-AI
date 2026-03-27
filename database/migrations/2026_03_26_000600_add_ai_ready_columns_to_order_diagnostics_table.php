<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_diagnostics', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->after('order_id')->constrained('equipments')->nullOnDelete();
            $table->string('equipment_type', 120)->nullable()->after('equipment_snapshot');
            $table->string('failure_type', 80)->nullable()->after('diagnostic_summary');
            $table->text('normalized_symptoms')->nullable()->after('symptoms_snapshot');
            $table->json('symptom_keywords')->nullable()->after('normalized_symptoms');
            $table->string('diagnostic_signature', 191)->nullable()->after('symptom_keywords');

            $table->index(['company_id', 'customer_id', 'created_at'], 'order_diag_company_customer_created_idx');
            $table->index(['company_id', 'equipment_id', 'created_at'], 'order_diag_company_equipment_created_idx');
            $table->index(['company_id', 'equipment_type'], 'order_diag_company_equipment_type_idx');
            $table->index(['company_id', 'failure_type'], 'order_diag_company_failure_type_idx');
            $table->index(['company_id', 'diagnostic_signature'], 'order_diag_company_signature_idx');
        });

        DB::table('order_diagnostics')
            ->orderBy('id')
            ->chunkById(100, function (Collection $diagnostics): void {
                $orderIds = $diagnostics->pluck('order_id')->filter()->unique()->values();

                $orders = DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->get()
                    ->keyBy('id');

                $equipmentIds = $orders->pluck('equipment_id')->filter()->unique()->values();

                $equipments = DB::table('equipments')
                    ->whereIn('id', $equipmentIds)
                    ->get()
                    ->keyBy('id');

                foreach ($diagnostics as $diagnostic) {
                    $order = $orders->get($diagnostic->order_id);
                    $equipment = $order ? $equipments->get($order->equipment_id) : null;
                    $possibleCauses = json_decode((string) ($diagnostic->possible_causes ?? '[]'), true);
                    $metadata = $this->normalizeMetadata(
                        $diagnostic->symptoms_snapshot,
                        is_array($possibleCauses) ? $possibleCauses : [],
                        $equipment->type ?? data_get(
                            json_decode((string) ($diagnostic->equipment_snapshot ?? 'null'), true),
                            'type'
                        )
                    );

                    DB::table('order_diagnostics')
                        ->where('id', $diagnostic->id)
                        ->update([
                            'customer_id' => $order->customer_id ?? null,
                            'equipment_id' => $order->equipment_id ?? null,
                            'equipment_type' => $this->normalizeText($equipment->type ?? null),
                            'failure_type' => $metadata['failure_type'],
                            'normalized_symptoms' => $metadata['normalized_symptoms'],
                            'symptom_keywords' => $metadata['symptom_keywords'] === []
                                ? null
                                : json_encode($metadata['symptom_keywords'], JSON_UNESCAPED_UNICODE),
                            'diagnostic_signature' => $metadata['diagnostic_signature'],
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('order_diagnostics', function (Blueprint $table): void {
            $table->dropIndex('order_diag_company_customer_created_idx');
            $table->dropIndex('order_diag_company_equipment_created_idx');
            $table->dropIndex('order_diag_company_equipment_type_idx');
            $table->dropIndex('order_diag_company_failure_type_idx');
            $table->dropIndex('order_diag_company_signature_idx');

            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('equipment_id');
            $table->dropColumn([
                'equipment_type',
                'failure_type',
                'normalized_symptoms',
                'symptom_keywords',
                'diagnostic_signature',
            ]);
        });
    }

    /**
     * @param  array<int, string>  $possibleCauses
     * @return array<string, mixed>
     */
    private function normalizeMetadata(?string $symptoms, array $possibleCauses, ?string $equipmentType): array
    {
        $normalizedSymptoms = $this->normalizeText($symptoms);
        $keywords = $this->extractKeywords($symptoms, $possibleCauses, $equipmentType);
        $failureType = $this->inferFailureType($normalizedSymptoms, $possibleCauses);

        return [
            'normalized_symptoms' => $normalizedSymptoms,
            'symptom_keywords' => $keywords,
            'failure_type' => $failureType,
            'diagnostic_signature' => $this->buildSignature($equipmentType, $failureType, $keywords),
        ];
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $value
        );

        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  array<int, string>  $possibleCauses
     * @return array<int, string>
     */
    private function extractKeywords(?string $symptoms, array $possibleCauses, ?string $equipmentType): array
    {
        $stopWords = [
            'a', 'al', 'con', 'de', 'del', 'el', 'en', 'es', 'esta', 'este', 'hay', 'la', 'las', 'lo', 'los',
            'no', 'por', 'que', 'se', 'sin', 'su', 'sus', 'un', 'una', 'unos', 'unas', 'y',
        ];

        $source = collect([
            $this->normalizeText($symptoms),
            $this->normalizeText($equipmentType),
            ...array_map(fn ($cause) => $this->normalizeText((string) $cause), $possibleCauses),
        ])
            ->filter()
            ->implode(' ');

        $tokens = preg_split('/\s+/u', $source) ?: [];

        return collect($tokens)
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => mb_strlen($token) >= 3)
            ->reject(fn ($token) => in_array($token, $stopWords, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $possibleCauses
     */
    private function inferFailureType(?string $normalizedSymptoms, array $possibleCauses = []): ?string
    {
        $catalog = [
            'power_failure' => ['no enciende', 'no prende', 'sin energia', 'sin corriente', 'no arranca'],
            'cooling_failure' => ['no enfria', 'no congela', 'temperatura alta', 'no enfria bien'],
            'water_leak' => ['fuga', 'gotea', 'pierde agua', 'derrame', 'filtracion'],
            'noise_vibration' => ['ruido', 'vibracion', 'golpeteo', 'zumbido'],
            'heating_failure' => ['no calienta', 'no calienta bien', 'temperatura baja'],
            'drain_failure' => ['no drena', 'no desagua', 'drenaje', 'atasco'],
            'control_board_failure' => ['tarjeta', 'placa', 'control', 'panel', 'electronica'],
        ];

        $haystack = collect([$normalizedSymptoms, ...array_map(
            fn ($cause) => $this->normalizeText((string) $cause),
            $possibleCauses
        )])
            ->filter()
            ->implode(' ');

        if ($haystack === '') {
            return null;
        }

        foreach ($catalog as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($haystack, $pattern)) {
                    return $type;
                }
            }
        }

        return 'general_failure';
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function buildSignature(?string $equipmentType, ?string $failureType, array $keywords): ?string
    {
        $parts = array_filter([
            $this->normalizeText($equipmentType),
            $failureType,
            implode('-', array_slice($keywords, 0, 6)),
        ]);

        if ($parts === []) {
            return null;
        }

        return implode('|', $parts);
    }
};
