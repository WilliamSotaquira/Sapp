<?php

declare(strict_types=1);

namespace App\Services\SmartParser\Extractors;

use App\Models\ServiceLevelAgreement;
use App\Models\SubService;
use App\Services\SmartParser\Contracts\FieldExtractorInterface;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubServiceClassifier implements FieldExtractorInterface
{
    /**
     * Umbral mínimo de similitud para asignación automática (55%).
     */
    private const SIMILARITY_THRESHOLD = 55.0;

    public function extract(ParsingContext $context): ExtractionResult
    {
        $normalizedText = $this->normalizeForComparison($context->normalizedText);

        if ($normalizedText === '') {
            return ExtractionResult::empty('sub_service');
        }

        $subServices = $this->getActiveSubServicesForContract($context->contractId);

        if ($subServices->isEmpty()) {
            return ExtractionResult::empty('sub_service');
        }

        $bestMatch = $this->findBestMatch($normalizedText, $subServices);

        if ($bestMatch === null || $bestMatch['score'] < self::SIMILARITY_THRESHOLD) {
            return ExtractionResult::empty('sub_service');
        }

        /** @var SubService $subService */
        $subService = $bestMatch['sub_service'];

        $serviceId = $subService->service_id;
        $familyId = $subService->service?->service_family_id;
        $slaId = $this->resolveActiveSlaId($subService->id, $familyId);

        $value = [
            'sub_service_id' => $subService->id,
            'service_id' => $serviceId,
            'family_id' => $familyId,
            'sla_id' => $slaId,
            'sub_service_name' => $subService->name,
        ];

        $confidence = $this->calculateConfidence($bestMatch['score']);

        return new ExtractionResult(
            fieldName: 'sub_service',
            value: $value,
            confidence: $confidence,
        );
    }

    /**
     * Obtiene los subservicios activos del contrato activo, con sus relaciones.
     *
     * @return Collection<int, SubService>
     */
    private function getActiveSubServicesForContract(int $contractId): Collection
    {
        return SubService::query()
            ->active()
            ->with(['service.family'])
            ->whereHas('service.family', function ($query) use ($contractId) {
                $query->where('contract_id', $contractId)
                    ->where('is_active', true);
            })
            ->whereHas('service', function ($query) {
                $query->where('is_active', true);
            })
            ->get();
    }

    /**
     * Encuentra el subservicio con la mayor puntuación de similitud.
     *
     * @param Collection<int, SubService> $subServices
     * @return array{sub_service: SubService, score: float}|null
     */
    private function findBestMatch(string $normalizedText, Collection $subServices): ?array
    {
        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($subServices as $subService) {
            $score = $this->calculateSimilarityScore($normalizedText, $subService);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = [
                    'sub_service' => $subService,
                    'score' => $score,
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * Calcula la puntuación de similitud textual entre el texto normalizado y un subservicio.
     *
     * Compara contra:
     * - Nombre del subservicio
     * - Código del subservicio
     * - Nombre del servicio padre
     * - Nombre de la familia
     *
     * Aplica bonificaciones por coincidencia exacta o contención.
     */
    private function calculateSimilarityScore(string $normalizedText, SubService $subService): float
    {
        $parts = array_filter([
            $subService->name,
            $subService->code,
            $subService->service?->name,
            $subService->service?->family?->name,
        ]);

        $searchSpace = $this->normalizeForComparison(implode(' ', $parts));
        $nameOnly = $this->normalizeForComparison($subService->name);

        // Comparación por nombre solamente
        similar_text($normalizedText, $nameOnly, $namePercent);

        // Comparación contra el espacio de búsqueda completo
        similar_text($normalizedText, $searchSpace, $fullPercent);

        $score = max($namePercent, $fullPercent);

        // Bonificaciones por coincidencia exacta o contención
        if ($nameOnly === $normalizedText) {
            $score = 100.0;
        } elseif (str_contains($nameOnly, $normalizedText) || str_contains($normalizedText, $nameOnly)) {
            $score = max($score, 92.0);
        } elseif (str_contains($searchSpace, $normalizedText)) {
            $score = max($score, 88.0);
        }

        return $score;
    }

    /**
     * Resuelve el SLA activo vinculado al subservicio.
     *
     * Busca primero por subservicio directo (a través de service_subservices),
     * luego por familia si no encuentra por subservicio.
     *
     * @return int|null ID del SLA activo, o null si no existe
     */
    private function resolveActiveSlaId(int $subServiceId, ?int $familyId): ?int
    {
        // Buscar SLA por subservicio (a través de la relación service_subservices)
        $slaBySubService = ServiceLevelAgreement::query()
            ->forSubService($subServiceId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('id');

        if ($slaBySubService) {
            return (int) $slaBySubService;
        }

        // Fallback: buscar SLA por familia
        if ($familyId) {
            $slaByFamily = ServiceLevelAgreement::query()
                ->where('service_family_id', $familyId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->value('id');

            if ($slaByFamily) {
                return (int) $slaByFamily;
            }
        }

        // Requisito 4.5: Si no hay SLA activo, retornar null
        return null;
    }

    /**
     * Calcula el nivel de confianza basado en la puntuación de similitud.
     */
    private function calculateConfidence(float $score): int
    {
        if ($score >= 95.0) {
            return 95;
        }

        if ($score >= 85.0) {
            return 85;
        }

        if ($score >= 70.0) {
            return 75;
        }

        // Score entre 55% y 70%
        return 60;
    }

    /**
     * Normaliza un texto para comparación: minúsculas, sin tildes, sin caracteres especiales.
     */
    private function normalizeForComparison(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->value();
    }
}
