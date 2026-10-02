<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Fragmentos SQL portables entre motores (MySQL en producción, SQLite en tests).
 *
 * Centraliza expresiones que difieren por driver para no repetir SQL crudo
 * específico de un motor a lo largo del código.
 */
class SqlExpr
{
    /**
     * Expresión que devuelve la "fecha efectiva de cierre/resolución": la MENOR
     * entre resolved_at y closed_at, tolerando nulos.
     *
     * MySQL usa LEAST(...); SQLite no tiene LEAST pero su MIN(a, b) escalar
     * cumple el mismo rol. Ambos reciben dos COALESCE que garantizan no-nulos
     * cuando al menos una de las dos fechas existe.
     */
    public static function effectiveCloseDate(): string
    {
        $inner = 'COALESCE(resolved_at, closed_at), COALESCE(closed_at, resolved_at)';

        return DB::getDriverName() === 'sqlite'
            ? "MIN($inner)"
            : "LEAST($inner)";
    }

    /**
     * Expresión de ordenamiento por una lista explícita de valores (el orden en
     * que aparecen define la prioridad), portable entre motores.
     *
     * MySQL dispone de FIELD(col, v1, v2, ...) que devuelve la posición 1..N del
     * valor en la lista (0 si no está). SQLite no tiene FIELD, así que se emite
     * un CASE WHEN equivalente: cada valor recibe su índice 1..N y el resto 0,
     * replicando exactamente la semántica de FIELD para usar en ORDER BY.
     *
     * El resultado es un fragmento SQL seguro para `orderByRaw(...)`: el nombre
     * de columna debe ser un identificador controlado por el código (no entrada
     * de usuario) y los valores se escapan como literales entrecomillados.
     */
    public static function fieldOrder(string $column, array $values): string
    {
        $quoted = array_map(static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'", $values);

        if (DB::getDriverName() !== 'sqlite') {
            return 'FIELD(' . $column . ', ' . implode(', ', $quoted) . ')';
        }

        // SQLite: reproducir FIELD con un CASE WHEN (posición 1..N, 0 si no coincide).
        $cases = '';
        foreach ($quoted as $index => $literal) {
            $position = $index + 1;
            $cases .= " WHEN {$column} = {$literal} THEN {$position}";
        }

        return "CASE{$cases} ELSE 0 END";
    }
}
