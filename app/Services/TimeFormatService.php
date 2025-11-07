<?php

namespace App\Services;

class TimeFormatService
{
    /**
     * Convierte minutos a formato legible (horas, días, etc.)
     * Para tiempos planificados del SLA
     */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            if ($remainingMinutes > 0) {
                return "{$hours}h {$remainingMinutes}min";
            }
            return "{$hours} " . ($hours === 1 ? 'hora' : 'horas');
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($days < 7) {
            if ($remainingHours > 0) {
                return "{$days}d {$remainingHours}h";
            }
            return "{$days} " . ($days === 1 ? 'día' : 'días');
        }

        $weeks = floor($days / 7);
        $remainingDays = $days % 7;

        if ($remainingDays > 0) {
            return "{$weeks} " . ($weeks === 1 ? 'semana' : 'semanas') . " {$remainingDays}d";
        }

        return "{$weeks} " . ($weeks === 1 ? 'semana' : 'semanas');
    }

    /**
     * Formatea minutos para mostrar tiempo restante/transcurrido
     * Optimizado para tiempos largos y maneja tiempos vencidos
     */
    public static function formatRemainingTime(int $minutes): string
    {
        // Si es negativo (vencido), convertimos a positivo
        $isOverdue = $minutes < 0;
        $absoluteMinutes = abs($minutes);

        if ($absoluteMinutes < 60) {
            return $isOverdue ? "{$absoluteMinutes} min" : "{$absoluteMinutes} min";
        }

        $hours = floor($absoluteMinutes / 60);
        $remainingMinutes = $absoluteMinutes % 60;

        if ($hours < 24) {
            if ($remainingMinutes > 0) {
                return $isOverdue ? "{$hours}h {$remainingMinutes}min" : "{$hours}h {$remainingMinutes}min";
            }
            return $isOverdue ? "{$hours}h" : "{$hours}h";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($days < 7) {
            if ($remainingHours > 0) {
                return $isOverdue ? "{$days}d {$remainingHours}h" : "{$days}d {$remainingHours}h";
            }
            return $isOverdue ? "{$days}d" : "{$days}d";
        }

        $weeks = floor($days / 7);
        $remainingDays = $days % 7;

        if ($remainingDays > 0) {
            return $isOverdue ? "{$weeks}sem {$remainingDays}d" : "{$weeks}sem {$remainingDays}d";
        }

        return $isOverdue ? "{$weeks}sem" : "{$weeks}sem";
    }

    /**
     * Formatea específicamente para tiempos vencidos
     */
    public static function formatOverdueTime(int $minutes): string
    {
        $absoluteMinutes = abs($minutes);

        if ($absoluteMinutes < 60) {
            return "{$absoluteMinutes} min";
        }

        $hours = floor($absoluteMinutes / 60);
        $remainingMinutes = $absoluteMinutes % 60;

        if ($hours < 24) {
            if ($remainingMinutes > 0) {
                return "{$hours}h {$remainingMinutes}min";
            }
            return "{$hours}h";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($days < 7) {
            if ($remainingHours > 0) {
                return "{$days}d {$remainingHours}h";
            }
            return "{$days}d";
        }

        $weeks = floor($days / 7);
        $remainingDays = $days % 7;

        if ($remainingDays > 0) {
            return "{$weeks}sem {$remainingDays}d";
        }

        return "{$weeks}sem";
    }
}
