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
        // Resumen breve y genérico: describe el TIPO de trabajo realizado en la
        // familia (acciones y temas recurrentes), sin enumerar cada actividad.
        if (empty($frases)) {
            return '';
        }

        // Una sola actividad: usar su idea completa tal cual.
        if (count($frases) === 1) {
            return rtrim(trim($frases[0]), '.') . '.';
        }

        $acciones = self::topKeywords($frases, self::ACTION_VERBS, 4);
        $temas = self::topKeywords($frases, self::TOPIC_TERMS, 4);

        $partes = [];
        if (!empty($acciones)) {
            $partes[] = self::joinNatural($acciones);
        }
        if (!empty($temas)) {
            $partes[] = 'principalmente sobre ' . self::joinNatural($temas);
        }

        if (empty($partes)) {
            // Sin coincidencias de vocabulario: primera idea + indicador de resto.
            $primera = rtrim(trim($frases[0]), '.');
            return $primera . ', entre otras acciones de gestión.';
        }

        return 'Se realizaron acciones de ' . implode(', ', $partes) . '.';
    }

    /**
     * Verbos/acciones típicos del trabajo web para describir el tipo de gestión.
     */
    private const ACTION_VERBS = [
        'publicación' => ['public'],
        'actualización' => ['actualiz', 'modific', 'editó', 'edición', 'editar'],
        'carga de archivos' => ['subió', 'subieron', 'cargó', 'cargu', 'carga'],
        'descarga de recursos' => ['descarg'],
        'validación' => ['valid', 'verific', 'revis'],
        'configuración' => ['configur', 'ajust', 'redirec', 'reenvío'],
        'diseño/maquetación' => ['implement', 'estructur', 'diseñ', 'html', 'maquet'],
        'recopilación de información' => ['recopil', 'consolid', 'filtr'],
    ];

    /**
     * Temas/objetos recurrentes del contenido gestionado.
     */
    private const TOPIC_TERMS = [
        'banners' => ['banner'],
        'contenidos del portal' => ['portal', 'cms', 'gestor de contenidos', 'micrositio', 'sitio'],
        'noticias' => ['noticia'],
        'videos' => ['video'],
        'documentos' => ['documento', 'acta', 'resolución', 'calendario'],
        'imágenes/gráficos' => ['gráfic', 'imagen', 'imágen'],
        'intranet' => ['intranet'],
        'menús/enlaces' => ['menú', 'enlace', 'url'],
    ];

    /**
     * Devuelve las etiquetas cuyos términos aparecen más veces en las frases,
     * ordenadas por frecuencia, hasta $limit.
     *
     * @param  array<int, string>  $frases
     * @param  array<string, array<int, string>>  $dictionary
     * @return array<int, string>
     */
    private static function topKeywords(array $frases, array $dictionary, int $limit): array
    {
        $texto = mb_strtolower(implode(' ', $frases));
        $conteo = [];

        foreach ($dictionary as $label => $needles) {
            $n = 0;
            foreach ($needles as $needle) {
                $n += substr_count($texto, mb_strtolower($needle));
            }
            if ($n > 0) {
                $conteo[$label] = $n;
            }
        }

        arsort($conteo);

        return array_slice(array_keys($conteo), 0, $limit);
    }

    /**
     * Une una lista con comas y "y" antes del último elemento.
     *
     * @param  array<int, string>  $items
     */
    private static function joinNatural(array $items): string
    {
        $items = array_values(array_filter($items));
        $count = count($items);

        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ' y ' . $last;
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
