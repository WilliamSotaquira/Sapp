<?php
// app/Providers/ViewServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Las configuraciones de statusConfig y criticalityConfig se definen
        // localmente en cada componente Blade que las necesita.
        // No es necesario compartirlas globalmente con View::composer('*').
    }
}
