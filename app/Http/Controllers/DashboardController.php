<?php

namespace App\Http\Controllers;

use App\Models\Requirement;
use App\Models\Project;
use App\Models\Reporter;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Estadísticas básicas
            $stats = [
                'total_requirements' => Requirement::count(),
                'pending_requirements' => Requirement::where('status', 'pending')->count(),
                'in_progress_requirements' => Requirement::where('status', 'in_progress')->count(),
                'completed_requirements' => Requirement::where('status', 'completed')->count(),
                'urgent_requirements' => Requirement::where('priority', 'urgent')
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count(),
                'active_projects' => Project::where('status', 'active')->count(),
                'total_reporters' => Reporter::where('is_active', true)->count(),
                'active_alerts' => Alert::where('is_active', true)->count()
            ];

            // Requerimientos recientes
            $recentRequirements = Requirement::with(['reporter', 'classification', 'project'])
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();

            // Proyectos activos
            $activeProjects = Project::withCount(['requirements'])
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Alertas activas
            $activeAlerts = Alert::where('is_active', true)
                ->orderBy('alert_date', 'desc')
                ->limit(5)
                ->get();

            return view('dashboard', compact(
                'stats',
                'recentRequirements',
                'activeProjects',
                'activeAlerts'
            ));

        } catch (\Exception $e) {
            // En caso de error, mostrar dashboard básico
            return $this->showBasicDashboard();
        }
    }

    private function showBasicDashboard()
    {
        try {
            $stats = [
                'total_requirements' => DB::table('requirements')->count(),
                'pending_requirements' => DB::table('requirements')->where('status', 'pending')->count(),
                'in_progress_requirements' => DB::table('requirements')->where('status', 'in_progress')->count(),
                'completed_requirements' => DB::table('requirements')->where('status', 'completed')->count(),
                'urgent_requirements' => DB::table('requirements')->where('priority', 'urgent')
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count(),
                'active_projects' => DB::table('projects')->where('status', 'active')->count(),
                'total_reporters' => DB::table('reporters')->count(),
                'active_alerts' => DB::table('alerts')->where('is_active', true)->count()
            ];

            $recentRequirements = DB::table('requirements')
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();

            $activeProjects = DB::table('projects')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $activeAlerts = DB::table('alerts')
                ->where('is_active', true)
                ->orderBy('alert_date', 'desc')
                ->limit(5)
                ->get();

            return view('dashboard', compact(
                'stats',
                'recentRequirements',
                'activeProjects',
                'activeAlerts'
            ));

        } catch (\Exception $e) {
            // Último recurso: dashboard vacío
            $stats = [
                'total_requirements' => 0,
                'pending_requirements' => 0,
                'in_progress_requirements' => 0,
                'completed_requirements' => 0,
                'urgent_requirements' => 0,
                'active_projects' => 0,
                'total_reporters' => 0,
                'active_alerts' => 0
            ];

            $recentRequirements = collect();
            $activeProjects = collect();
            $activeAlerts = collect();

            return view('dashboard', compact(
                'stats',
                'recentRequirements',
                'activeProjects',
                'activeAlerts'
            ));
        }
    }
}
