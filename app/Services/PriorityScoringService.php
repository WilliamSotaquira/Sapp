<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Support\Carbon;
use Carbon\Carbon as BaseCarbon;

/**
 * Servicio de cálculo de puntaje de prioridad para solicitudes de servicio.
 *
 * Fórmula: Puntaje = Criticidad + Antigüedad + Complejidad + Desconfianza + Hilos
 *
 * Clasificación resultante:
 * - P0: Atender hoy (puntaje >= 80)
 * - P1: Atender 24-48h (puntaje >= 60)
 * - P2: Programar esta semana (puntaje >= 40)
 * - P3: Cola operativa (puntaje >= 20)
 * - P4: Archivar o validar si aplica (puntaje < 20)
 *
 * Antigüedad (calculada desde created_at hasta fecha de corte):
 * - Muy reciente: 0-6 días
 * - Reciente: 7-13 días
 * - Media: 14-29 días
 * - Antigua: 30-59 días
 * - Muy antigua: 60+ días
 *
 * Criticidad Crítica: Urgente/importante, errores, credenciales, VPN, permisos/accesos,
 *   producción, servidores, canales PQRSDF, ChatBot o enlaces rotos.
 *
 * Criticidad Alta: Solicitudes de publicación/actualización, portales, micrositios,
 *   landing pages, SINEFAC/PAPP/DEDE, datos abiertos, BIC, informes/caracterización
 *   o sistemas de información.
 *
 * Complejidad Alta: Requiere desarrollo, integración, micrositio/landing/portal,
 *   arquitectura, SEO, Figma/pagelayouts, permisos/roles o coordinación técnica.
 */
class PriorityScoringService
{
    // ==================== PESOS DE CRITICIDAD ====================
    const CRITICALITY_SCORES = [
        'CRITICA' => 40,
        'ALTA' => 30,
        'MEDIA' => 15,
        'BAJA' => 5,
    ];

    // ==================== PESOS DE COMPLEJIDAD ====================
    const COMPLEXITY_SCORES = [
        'ALTA' => 25,
        'MEDIA' => 12,
        'BAJA' => 5,
    ];

    // ==================== PESOS DE ANTIGÜEDAD ====================
    const ANTIQUITY_SCORES = [
        'MUY_ANTIGUA' => 25,  // 60+ días
        'ANTIGUA' => 20,      // 30-59 días
        'MEDIA' => 12,        // 14-29 días
        'RECIENTE' => 6,      // 7-13 días
        'MUY_RECIENTE' => 2,  // 0-6 días
    ];

    // ==================== RANGOS DE ANTIGÜEDAD (días) ====================
    const ANTIQUITY_RANGES = [
        'MUY_RECIENTE' => [0, 6],
        'RECIENTE' => [7, 13],
        'MEDIA' => [14, 29],
        'ANTIGUA' => [30, 59],
        'MUY_ANTIGUA' => [60, PHP_INT_MAX],
    ];

    // ==================== PESOS DE DESCONFIANZA ====================
    // Factor 1-5: qué tan incierto es el origen/estado de la solicitud
    const DISTRUST_MULTIPLIER = 3; // Cada punto de desconfianza suma 3 al puntaje

    // ==================== PESOS DE HILOS ====================
    // Cada correo adicional en el hilo suma puntos (indica urgencia/seguimiento)
    const THREAD_SCORE_PER_MESSAGE = 2;
    const THREAD_SCORE_CAP = 10; // Máximo 10 puntos por hilos

    // ==================== UMBRALES DE PRIORIDAD (P0-P4) ====================
    const PRIORITY_THRESHOLDS = [
        'P0' => 80,  // Atender hoy
        'P1' => 60,  // Atender 24-48h
        'P2' => 40,  // Programar esta semana
        'P3' => 20,  // Cola operativa
        'P4' => 0,   // Archivar o validar si aplica
    ];

    // ==================== LABELS ====================
    const PRIORITY_LABELS = [
        'P0' => 'Atender hoy',
        'P1' => 'Atender 24-48h',
        'P2' => 'Programar esta semana',
        'P3' => 'Cola operativa',
        'P4' => 'Archivar o validar si aplica',
    ];

    const ANTIQUITY_LABELS = [
        'MUY_RECIENTE' => 'Muy reciente (0-6 días)',
        'RECIENTE' => 'Reciente (7-13 días)',
        'MEDIA' => 'Media (14-29 días)',
        'ANTIGUA' => 'Antigua (30-59 días)',
        'MUY_ANTIGUA' => 'Muy antigua (60+ días)',
    ];

    const COMPLEXITY_LABELS = [
        'BAJA' => 'Baja',
        'MEDIA' => 'Media',
        'ALTA' => 'Alta',
    ];

    const CRITICALITY_LABELS = [
        'BAJA' => 'Baja',
        'MEDIA' => 'Media',
        'ALTA' => 'Alta',
        'CRITICA' => 'Crítica',
    ];

    /**
     * Calcular el puntaje de prioridad completo para una solicitud.
     *
     * @param ServiceRequest $request
     * @param Carbon|BaseCarbon|null $cutDate Fecha de corte para calcular antigüedad (default: hoy)
     * @return array Desglose completo del puntaje
     */
    public function calculateScore(ServiceRequest $request, $cutDate = null): array
    {
        $cutDate = $cutDate ?? Carbon::now();

        $criticalityScore = $this->getCriticalityScore($request->criticality_level);
        $complexityScore = $this->getComplexityScore($request->complexity_level ?? 'MEDIA');
        $antiquityClass = $this->classifyAntiquity($request->created_at, $cutDate);
        $antiquityScore = $this->getAntiquityScore($antiquityClass);
        $distrustScore = $this->getDistrustScore($request->distrust_factor ?? 1);
        $threadScore = $this->getThreadScore($request->thread_count ?? 1);

        $totalScore = $criticalityScore + $complexityScore + $antiquityScore + $distrustScore + $threadScore;
        $priorityLevel = $this->classifyPriority($totalScore);

        return [
            'criticality_score' => $criticalityScore,
            'complexity_score' => $complexityScore,
            'antiquity_score' => $antiquityScore,
            'antiquity_class' => $antiquityClass,
            'distrust_score' => $distrustScore,
            'thread_score' => $threadScore,
            'total_score' => $totalScore,
            'priority_level' => $priorityLevel,
            'priority_label' => self::PRIORITY_LABELS[$priorityLevel] ?? $priorityLevel,
        ];
    }

    /**
     * Calcular y persistir el puntaje en la solicitud.
     * Infiere automáticamente complejidad si no fue establecida manualmente.
     */
    public function calculateAndSave(ServiceRequest $request, $cutDate = null): ServiceRequest
    {
        // Auto-inferir complejidad si no fue establecida o es el default
        if (empty($request->complexity_level) || $request->complexity_level === 'MEDIA') {
            $text = ($request->title ?? '') . ' ' . ($request->description ?? '');
            if (mb_strlen(trim($text)) > 5) {
                $request->complexity_level = $this->inferComplexity($text);
            }
        }

        $result = $this->calculateScore($request, $cutDate);

        $request->priority_score = $result['total_score'];
        $request->priority_level = $result['priority_level'];
        $request->antiquity_class = $result['antiquity_class'];

        if ($cutDate) {
            $request->cut_date = $cutDate->toDateString();
        }

        $request->save();

        return $request;
    }

    /**
     * Recalcular prioridades para todas las solicitudes abiertas.
     */
    public function recalculateAll($cutDate = null, ?int $companyId = null): int
    {
        $cutDate = $cutDate ?? Carbon::now();
        $count = 0;

        $query = ServiceRequest::withoutGlobalScope('workspace')
            ->whereNotIn('status', [
                ServiceRequest::STATUS_CLOSED,
                ServiceRequest::STATUS_CANCELLED,
                'RECHAZADA',
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->chunkById(100, function ($requests) use ($cutDate, &$count) {
            foreach ($requests as $request) {
                $this->calculateAndSave($request, $cutDate);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Obtener puntaje de criticidad.
     */
    public function getCriticalityScore(?string $level): int
    {
        $normalized = strtoupper(trim($level ?? 'MEDIA'));

        // Mapear URGENTE a CRITICA
        if ($normalized === 'URGENTE') {
            $normalized = 'CRITICA';
        }

        return self::CRITICALITY_SCORES[$normalized] ?? self::CRITICALITY_SCORES['MEDIA'];
    }

    /**
     * Obtener puntaje de complejidad.
     */
    public function getComplexityScore(?string $level): int
    {
        $normalized = strtoupper(trim($level ?? 'MEDIA'));

        return self::COMPLEXITY_SCORES[$normalized] ?? self::COMPLEXITY_SCORES['MEDIA'];
    }

    /**
     * Clasificar la antigüedad de una solicitud.
     */
    public function classifyAntiquity($createdAt, $cutDate = null): string
    {
        if (!$createdAt) {
            return 'MUY_RECIENTE';
        }

        $cutDate = $cutDate ?? Carbon::now();

        // Asegurar que ambos sean instancias de Carbon
        if (!($createdAt instanceof Carbon) && !($createdAt instanceof BaseCarbon)) {
            $createdAt = Carbon::parse($createdAt);
        }
        if (!($cutDate instanceof Carbon) && !($cutDate instanceof BaseCarbon)) {
            $cutDate = Carbon::parse($cutDate);
        }

        $days = (int) $createdAt->diffInDays($cutDate, false);

        // Asegurar que no sea negativo (solicitud futura)
        $days = max(0, $days);

        foreach (self::ANTIQUITY_RANGES as $class => [$min, $max]) {
            if ($days >= $min && $days <= $max) {
                return $class;
            }
        }

        return 'MUY_ANTIGUA';
    }

    /**
     * Obtener puntaje de antigüedad.
     */
    public function getAntiquityScore(string $antiquityClass): int
    {
        return self::ANTIQUITY_SCORES[$antiquityClass] ?? self::ANTIQUITY_SCORES['MEDIA'];
    }

    /**
     * Obtener puntaje de desconfianza.
     */
    public function getDistrustScore(int $factor): int
    {
        $factor = max(1, min(5, $factor));

        return ($factor - 1) * self::DISTRUST_MULTIPLIER;
    }

    /**
     * Obtener puntaje por número de correos en el hilo.
     */
    public function getThreadScore(int $threadCount): int
    {
        $extra = max(0, $threadCount - 1);

        return min($extra * self::THREAD_SCORE_PER_MESSAGE, self::THREAD_SCORE_CAP);
    }

    /**
     * Clasificar el puntaje total en nivel de prioridad P0-P4.
     */
    public function classifyPriority(int $totalScore): string
    {
        foreach (self::PRIORITY_THRESHOLDS as $level => $threshold) {
            if ($totalScore >= $threshold) {
                return $level;
            }
        }

        return 'P4';
    }

    /**
     * Obtener todas las opciones de prioridad con labels.
     */
    public static function getPriorityOptions(): array
    {
        return self::PRIORITY_LABELS;
    }

    /**
     * Obtener todas las opciones de antigüedad con labels.
     */
    public static function getAntiquityOptions(): array
    {
        return self::ANTIQUITY_LABELS;
    }

    /**
     * Obtener opciones de complejidad.
     */
    public static function getComplexityOptions(): array
    {
        return self::COMPLEXITY_LABELS;
    }

    /**
     * Obtener opciones de criticidad con descripciones.
     */
    public static function getCriticalityDescriptions(): array
    {
        return [
            'CRITICA' => [
                'label' => 'Crítica',
                'description' => 'Urgente/importante, errores, credenciales, VPN, permisos/accesos, producción, servidores, canales PQRSDF, ChatBot o enlaces rotos.',
                'keywords' => ['urgente', 'error', 'credencial', 'vpn', 'permiso', 'acceso', 'producción', 'servidor', 'pqrsdf', 'chatbot', 'enlace roto'],
            ],
            'ALTA' => [
                'label' => 'Alta',
                'description' => 'Solicitudes de publicación/actualización, portales, micrositios, landing pages, SINEFAC/PAPP/DEDE, datos abiertos, BIC, informes/caracterización o sistemas de información.',
                'keywords' => ['publicación', 'actualización', 'portal', 'micrositio', 'landing', 'sinefac', 'papp', 'dede', 'datos abiertos', 'bic', 'informe', 'sistema de información'],
            ],
            'MEDIA' => [
                'label' => 'Media',
                'description' => 'Solicitudes estándar de soporte, mantenimiento, contenido web, correo masivo, mesa técnica o seguimiento.',
                'keywords' => ['soporte', 'mantenimiento', 'contenido', 'web', 'correo masivo', 'mesa técnica', 'seguimiento'],
            ],
            'BAJA' => [
                'label' => 'Baja',
                'description' => 'Consultas informativas, solicitudes de baja prioridad sin impacto operativo inmediato.',
                'keywords' => ['consulta', 'información', 'bajo impacto'],
            ],
        ];
    }

    /**
     * Obtener descripciones de complejidad.
     */
    public static function getComplexityDescriptions(): array
    {
        return [
            'ALTA' => [
                'label' => 'Alta',
                'description' => 'Requiere desarrollo, integración, micrositio/landing/portal, arquitectura, SEO, Figma/pagelayouts, permisos/roles o coordinación técnica.',
                'keywords' => ['desarrollo', 'integración', 'micrositio', 'landing', 'portal', 'arquitectura', 'seo', 'figma', 'pagelayout', 'permisos', 'roles', 'coordinación técnica'],
            ],
            'MEDIA' => [
                'label' => 'Media',
                'description' => 'Configuración, ajustes de contenido, actualizaciones menores, soporte técnico estándar.',
                'keywords' => ['configuración', 'ajuste', 'actualización menor', 'soporte'],
            ],
            'BAJA' => [
                'label' => 'Baja',
                'description' => 'Tareas simples, consultas, revisiones rápidas sin componente técnico significativo.',
                'keywords' => ['simple', 'consulta', 'revisión rápida'],
            ],
        ];
    }

    /**
     * Inferir criticidad automáticamente basándose en palabras clave del título/descripción.
     */
    public function inferCriticality(string $text): string
    {
        $text = mb_strtolower($text);

        // Criticidad Crítica
        $criticalPatterns = '/urgente|importante|error|credencial|vpn|permiso|acceso|producci[oó]n|servidor|pqrsdf|chatbot|enlace.?roto|ca[ií]da|incidente|seguridad/u';
        if (preg_match($criticalPatterns, $text)) {
            return 'CRITICA';
        }

        // Criticidad Alta
        $highPatterns = '/publicaci[oó]n|actualizaci[oó]n|portal|micrositio|landing|sinefac|papp|dede|datos.?abiertos|bic|informe|caracterizaci[oó]n|sistema.?de.?informaci[oó]n/u';
        if (preg_match($highPatterns, $text)) {
            return 'ALTA';
        }

        // Criticidad Media
        $mediumPatterns = '/solicitud|revisi[oó]n|ajuste|web|soporte|correo.?masivo|mesa.?t[eé]cnica|seguimiento|mantenimiento|contenido/u';
        if (preg_match($mediumPatterns, $text)) {
            return 'MEDIA';
        }

        return 'BAJA';
    }

    /**
     * Inferir complejidad automáticamente basándose en palabras clave.
     */
    public function inferComplexity(string $text): string
    {
        $text = mb_strtolower($text);

        // Complejidad Alta
        $highPatterns = '/desarrollo|integraci[oó]n|micrositio|landing|portal|arquitectura|seo|figma|pagelayout|permisos|roles|coordinaci[oó]n.?t[eé]cnica|api|base.?de.?datos|migraci[oó]n/u';
        if (preg_match($highPatterns, $text)) {
            return 'ALTA';
        }

        // Complejidad Media
        $mediumPatterns = '/configuraci[oó]n|ajuste|actualizaci[oó]n|soporte|contenido|publicaci[oó]n|formulario|plantilla/u';
        if (preg_match($mediumPatterns, $text)) {
            return 'MEDIA';
        }

        return 'BAJA';
    }
}
