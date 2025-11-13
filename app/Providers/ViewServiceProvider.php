<?php
// app/Providers/ViewServiceProvider.php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Compartir configuraciones de service requests con todas las vistas
        View::composer('*', function ($view) {
            $view->with('statusConfig', [
                'PENDIENTE' => [
                    'color' => 'bg-yellow-500 text-white',
                    'icon' => 'fa-clock',
                    'label' => 'Pendiente'
                ],
                'ACEPTADA' => [
                    'color' => 'bg-blue-500 text-white',
                    'icon' => 'fa-check',
                    'label' => 'Aceptada'
                ],
                'EN_PROCESO' => [
                    'color' => 'bg-purple-500 text-white',
                    'icon' => 'fa-cog',
                    'label' => 'En Proceso'
                ],
                'PAUSADA' => [
                    'color' => 'bg-orange-500 text-white',
                    'icon' => 'fa-pause',
                    'label' => 'Pausada'
                ],
                'RESUELTA' => [
                    'color' => 'bg-green-500 text-white',
                    'icon' => 'fa-check-double',
                    'label' => 'Resuelta'
                ],
                'CERRADA' => [
                    'color' => 'bg-gray-500 text-white',
                    'icon' => 'fa-lock',
                    'label' => 'Cerrada'
                ],
                'CANCELADA' => [
                    'color' => 'bg-red-500 text-white',
                    'icon' => 'fa-times',
                    'label' => 'Cancelada'
                ]
            ]);

            $view->with('criticalityConfig', [
                'BAJA' => [
                    'color' => 'bg-green-500 text-white',
                    'icon' => 'fa-flag',
                    'label' => 'Baja'
                ],
                'MEDIA' => [
                    'color' => 'bg-yellow-500 text-white',
                    'icon' => 'fa-flag',
                    'label' => 'Media'
                ],
                'ALTA' => [
                    'color' => 'bg-orange-500 text-white',
                    'icon' => 'fa-exclamation-triangle',
                    'label' => 'Alta'
                ],
                'URGENTE' => [
                    'color' => 'bg-red-500 text-white',
                    'icon' => 'fa-exclamation-circle',
                    'label' => 'Urgente'
                ],
                'CRITICA' => [
                    'color' => 'bg-red-500 text-white',
                    'icon' => 'fa-skull-crossbones',
                    'label' => 'Crítica'
                ]
            ]);
        });
    }
}
