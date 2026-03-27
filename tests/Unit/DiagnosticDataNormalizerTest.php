<?php

namespace Tests\Unit;

use App\Support\DiagnosticDataNormalizer;
use PHPUnit\Framework\TestCase;

class DiagnosticDataNormalizerTest extends TestCase
{
    public function test_it_normalizes_diagnostic_metadata_for_similarity_search(): void
    {
        $metadata = DiagnosticDataNormalizer::normalizeDiagnosticMetadata(
            'Tiene fuga de agua y gotea por la base.',
            ['Posible falla en tarjeta electrónica', 'Bomba con fuga'],
            'Lavadora'
        );

        $this->assertSame('tiene fuga de agua y gotea por la base', $metadata['normalized_symptoms']);
        $this->assertSame('water_leak', $metadata['failure_type']);
        $this->assertContains('lavadora', $metadata['symptom_keywords']);
        $this->assertContains('fuga', $metadata['symptom_keywords']);
        $this->assertNotNull($metadata['diagnostic_signature']);
    }
}
