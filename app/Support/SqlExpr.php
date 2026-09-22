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
}
