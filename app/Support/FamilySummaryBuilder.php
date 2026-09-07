<?php

namespace App\Support;

use App\Models\ServiceRequest;
use Illuminate\Support\Collection;

/**
 * Construye un resumen de las actividades ejecutadas por familia dentro de un
 * corte, con ideas completas (nunca corta frases a la mitad) y el indicador de
 * cuántas actividades.
 *
 * Se usa tanto en el PDF como en el Excel del reporte de obligaciones para
 * mantener una única fuente de verdad del texto del resumen.
 */
class FamilySummaryBuilder
{
    /**
     * @param  Collection<int, ServiceRequest>  $familyRequests  Solicitudes de una familia
     * @return array{obligaciones:int, actividades:int, productos:int, resumen:string}
     */
    public static function build(Collection $familyRequests): array
    {
        $obligaciones = $familyRequests->count();

        // Contar actividades = tareas. Fallback: si no hay tareas, la propia
        // solicitud cuenta como una acción ejecutada.
        $actividades = 0;
        $productos = 0;
        $frases = [];

        foreach ($familyRequests as $sr) {
            $tasks = $sr->relationLoaded('tasks') ? $sr->tasks : collect();
            $actividades += max($tasks->count(), 0);

            // Productos = evidencias con archivo o enlace.
            if ($sr->relationLoaded('evidences')) {
                $productos += $sr->evidences->filter(function ($ev) {
                    $hasFile = filled($ev->file_path ?? null);
                    $isLink = ($ev->evidence_type ?? null) === 'ENLACE';
                    return $hasFile || $isLink;
                })->count();
            }

            // Recolectar una frase corta por solicitud para el resumen.
            $frase = self::shortActionPhrase($sr);
            if ($frase !== '') {
                $frases[] = $frase;
            }
        }

        // Si ninguna solicitud tenía tareas, usar el número de obligaciones como
        // aproximación de acciones ejecutadas (cada solicitud es una gestión).
        if ($actividades === 0) {
            $actividades = $obligaciones;
        }

        $resumen = self::composeResumen($obligaciones, $actividades, $frases);

        return [
            'obligaciones' => $obligaciones,
            'actividades' => $actividades,
            'productos' => $productos,
            'resumen' => $resumen,
        ];
    }

    /**
     * Primera idea COMPLETA de la acción realizada en una solicitud (la primera
     * oración de las notas de resolución, o el título si no hay notas). Nunca
     * corta palabras ni ideas a la mitad.
     */
    private static function shortActionPhrase(ServiceRequest $sr): string
    {
        $notes = self::cleanResolutionNotes((string) ($sr->resolution_notes ?? ''));
        $base = $notes !== '' ? $notes : (string) ($sr->title ?? '');
        $base = self::stripStatusPrefix($base);
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? '');

        if ($base === '') {
            return '';
        }

        // Tomar la primera oración completa (hasta el primer punto seguido de
        // espacio o fin). Así cada actividad aporta una idea entera, no cortada.
        if (preg_match('/^(.*?[.!?])(\s|$)/u', $base, $m)) {
            $primera = trim($m[1]);
            if (mb_strlen($primera) >= 15) {
                return $primera;
            }
        }

        return $base;
    }

    private static function composeResumen(int $obligaciones, int $actividades, array $frases): string
    {
        // Solo la narrativa de lo realizado. Los conteos ya se muestran aparte.
        // Se prioriza dejar IDEAS COMPLETAS: nunca se corta una frase a la mitad.
        if (empty($frases)) {
            return '';
        }

        // Numerar las ideas (una por solicitud) para que se lean como lista
        // continua sin ambigüedad. Cada frase queda completa.
        $numeradas = [];
        foreach (array_values($frases) as $i => $frase) {
            $frase = rtrim(trim($frase), '.');
            $numeradas[] = ($i + 1) . ') ' . $frase . '.';
        }

        return implode(' ', $numeradas);
    }

    private static function cleanResolutionNotes(string $notes): string
    {
        $notes = trim($notes);
        if ($notes === '') {
            return '';
        }

        $notes = preg_replace('/\s*===\s*CIERRE(?:\s+POR\s+VENCIMIENTO|\s+NORMAL)\s*===.*$/is', '', $notes) ?? $notes;
        $notes = preg_replace('/^\s*Fecha\/Hora:.*$/im', '', $notes) ?? $notes;
        $notes = preg_replace('/^\s*Usuario:\s*ID\s*\d+.*$/im', '', $notes) ?? $notes;
        $notes = trim($notes);

        if (preg_match('/Acciones realizadas:\s*(.*?)(?:\n\s*Notas adicionales:\s*|$)/is', $notes, $matches)) {
            $notes = trim((string) ($matches[1] ?? ''));
        }

        return trim($notes);
    }

    private static function stripStatusPrefix(string $title): string
    {
        if ($title === '') {
            return $title;
        }

        $statuses = [
            'PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'RESUELTA', 'CERRADA',
            'CANCELADA', 'PAUSADA', 'REABIERTO', 'RECHAZADA', 'NO_VIABLE',
        ];

        $pattern = '/^.+?\s-\s(' . implode('|', $statuses) . ')\s-\s/i';

        return preg_replace($pattern, '', $title) ?? $title;
    }
}
