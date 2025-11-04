<?php

namespace App\Models\Managers;

trait ServiceRequestWebRoutesManager
{
    // =============================================
    // MÉTODOS PARA GESTIÓN DE RUTAS WEB
    // =============================================

    /**
     * Agregar una ruta web a la solicitud
     */
    public function addWebRoute($route, $description = null, $isMain = false)
    {
        $routes = $this->web_routes ?? [];

        $newRoute = [
            'id' => uniqid(),
            'route' => $route,
            'description' => $description,
            'added_at' => now()->toISOString(),
            'added_by' => auth()->id()
        ];

        $routes[] = $newRoute;

        $this->update([
            'web_routes' => $routes,
            'main_web_route' => $isMain ? $route : $this->main_web_route
        ]);

        return $newRoute;
    }

    /**
     * Establecer ruta web principal
     */
    public function setMainWebRoute($route)
    {
        $this->update(['main_web_route' => $route]);
        return $this;
    }

    /**
     * Obtener todas las rutas web (CORREGIDO Y MEJORADO)
     */
    public function getWebRoutesAttribute($value)
    {
        // Si ya es un array, retornarlo directamente
        if (is_array($value)) {
            return $value;
        }

        // Si es null o vacío, retornar array vacío
        if (is_null($value) || $value === '') {
            return [];
        }

        // Si es string, intentar decodificar como JSON
        if (is_string($value)) {
            // Limpiar el string de escapes y comillas extras
            $cleanedValue = stripslashes(trim($value, '"'));

            $decoded = json_decode($cleanedValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Limpiar cada elemento del array si es necesario
                return array_map(function($item) {
                    if (is_string($item)) {
                        return trim($item, '"');
                    }
                    if (is_array($item) && isset($item['route'])) {
                        $item['route'] = trim($item['route'], '"');
                        return $item;
                    }
                    return $item;
                }, $decoded);
            }

            // Si no es JSON válido, tratar como string simple y convertirlo a array
            return [trim($cleanedValue, '"')];
        }

        // Fallback: array vacío
        return [];
    }

    /**
     * Obtener ruta web principal
     */
    public function getMainWebRouteAttribute($value)
    {
        return $value ?: $this->getDefaultMainRoute();
    }

    /**
     * Obtener ruta principal por defecto (CORREGIDO)
     */
    private function getDefaultMainRoute()
    {
        $routes = $this->web_routes;

        if (empty($routes)) {
            return null;
        }

        $firstItem = reset($routes);

        // Si es un array complejo
        if (is_array($firstItem) && isset($firstItem['route'])) {
            return $firstItem['route'];
        }

        // Si es un string simple
        if (is_string($firstItem)) {
            return $firstItem;
        }

        return null;
    }

    /**
     * Verificar si tiene rutas web (CORREGIDO)
     */
    public function hasWebRoutes()
    {
        if (empty($this->web_routes)) {
            return false;
        }

        if (is_array($this->web_routes)) {
            return !empty($this->web_routes);
        }

        return !empty($this->web_routes);
    }

    /**
     * Obtener número de rutas web (CORREGIDO)
     */
    public function getWebRoutesCountAttribute()
    {
        if (empty($this->web_routes)) {
            return 0;
        }

        if (is_array($this->web_routes)) {
            return count($this->web_routes);
        }

        return 0;
    }

    /**
     * Eliminar una ruta web por ID (CORREGIDO)
     */
    public function removeWebRoute($routeId)
    {
        $routes = $this->web_routes;

        if (empty($routes)) {
            return $this;
        }

        $filteredRoutes = array_filter($routes, function ($route) use ($routeId) {
            // Verificar si es array complejo o string simple
            if (is_array($route) && isset($route['id'])) {
                return $route['id'] !== $routeId;
            }
            // Si es string simple, no podemos eliminarlo por ID
            return true;
        });

        // Si eliminamos la ruta principal, actualizar main_web_route
        $removedRoute = array_filter($routes, function ($route) use ($routeId) {
            if (is_array($route) && isset($route['id'])) {
                return $route['id'] === $routeId;
            }
            return false;
        });

        $removedRoute = reset($removedRoute);
        if ($removedRoute && $this->main_web_route === $removedRoute['route']) {
            $newMainRoute = $this->getDefaultMainRoute();
            $this->update([
                'web_routes' => array_values($filteredRoutes),
                'main_web_route' => $newMainRoute
            ]);
        } else {
            $this->update(['web_routes' => array_values($filteredRoutes)]);
        }

        return $this;
    }
}
