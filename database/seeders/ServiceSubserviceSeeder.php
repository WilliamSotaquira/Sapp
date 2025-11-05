<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceFamily;
use App\Models\ServiceSubservice;
use Illuminate\Support\Facades\DB;

class ServiceSubserviceSeeder extends Seeder
{
    public function run()
    {
        // Limpiar tabla primero
        DB::table('service_subservices')->delete();

        $families = ServiceFamily::all();

        // Datos de subservicios organizados por familia
        $subservicesData = [
            'Soporte Técnico' => [
                ['name' => 'Instalación de Software', 'description' => 'Instalación y configuración de software empresarial'],
                ['name' => 'Configuración de Hardware', 'description' => 'Configuración y mantenimiento de equipos de computo'],
                ['name' => 'Solución de Problemas', 'description' => 'Diagnóstico y solución de incidencias técnicas'],
                ['name' => 'Mantenimiento Preventivo', 'description' => 'Mantenimiento periódico de equipos y sistemas'],
                ['name' => 'Actualizaciones de Seguridad', 'description' => 'Aplicación de parches y actualizaciones de seguridad'],
            ],
            'Desarrollo' => [
                ['name' => 'Desarrollo Frontend', 'description' => 'Desarrollo de interfaces de usuario y experiencias web'],
                ['name' => 'Desarrollo Backend', 'description' => 'Desarrollo de lógica de negocio y APIs'],
                ['name' => 'Base de Datos', 'description' => 'Diseño y administración de bases de datos'],
                ['name' => 'Aplicaciones Móviles', 'description' => 'Desarrollo de aplicaciones para dispositivos móviles'],
                ['name' => 'Integración de Sistemas', 'description' => 'Integración entre diferentes sistemas y plataformas'],
            ],
            'Infraestructura' => [
                ['name' => 'Servidores', 'description' => 'Administración y mantenimiento de servidores'],
                ['name' => 'Redes', 'description' => 'Configuración y monitoreo de redes empresariales'],
                ['name' => 'Almacenamiento', 'description' => 'Gestión de sistemas de almacenamiento y backup'],
                ['name' => 'Virtualización', 'description' => 'Administración de plataformas de virtualización'],
                ['name' => 'Cloud Computing', 'description' => 'Gestión de servicios en la nube'],
            ],
            'Consultoría' => [
                ['name' => 'Análisis de Procesos', 'description' => 'Análisis y optimización de procesos empresariales'],
                ['name' => 'Arquitectura de Soluciones', 'description' => 'Diseño de arquitecturas tecnológicas'],
                ['name' => 'Transformación Digital', 'description' => 'Asesoría en procesos de transformación digital'],
                ['name' => 'Ciberseguridad', 'description' => 'Consultoría en seguridad de la información'],
            ],
            'Capacitación' => [
                ['name' => 'Capacitación Técnica', 'description' => 'Entrenamiento en herramientas y tecnologías'],
                ['name' => 'Certificaciones', 'description' => 'Preparación para certificaciones tecnológicas'],
                ['name' => 'Workshops', 'description' => 'Talleres prácticos sobre tecnologías específicas'],
            ]
        ];

        foreach ($families as $family) {
            if (isset($subservicesData[$family->name])) {
                foreach ($subservicesData[$family->name] as $subserviceData) {
                    ServiceSubservice::create([
                        'service_family_id' => $family->id,
                        'name' => $subserviceData['name'],
                        'description' => $subserviceData['description'],
                        'is_active' => true
                    ]);

                    $this->command->info("Subservicio creado: {$subserviceData['name']} para {$family->name}");
                }
            }
        }

        $this->command->info('Subservicios creados exitosamente!');
    }
}
