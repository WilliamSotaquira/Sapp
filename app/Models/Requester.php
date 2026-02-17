<?php
// app/Models/Requester.php

namespace App\Models;

use App\Support\DepartmentOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requester extends Model
{
    use HasFactory, SoftDeletes;

    public static function getDepartmentOptions(?int $companyId = null): array
    {
        $companyId = $companyId ?: (int) session('current_company_id');
        $catalogOptions = Department::query()
            ->when($companyId > 0, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('is_active', true)
            ->ordered()
            ->pluck('name')
            ->toArray();

        $baseOptions = DepartmentOptions::all();

        if ($companyId > 0) {
            $company = Company::query()->select('id', 'name')->find($companyId);
            $isMobilityWorkspace = $company && str_contains(mb_strtolower($company->name), 'movil');
            if (!$isMobilityWorkspace) {
                $baseOptions = [];
            }
        }

        $companyOptions = static::withoutGlobalScopes()
            ->when($companyId > 0, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->toArray();

        $options = array_values(array_filter(array_unique(array_map(function ($value) {
            return is_string($value) ? trim($value) : '';
        }, array_merge($catalogOptions, $baseOptions, $companyOptions)))));

        natcasesort($options);

        return array_values($options);
    }

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('workspace', function ($query) {
            $companyId = session('current_company_id');
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        });
    }

    /**
     * Relación con ServiceRequests
     * Un solicitante puede tener muchas solicitudes
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope para solicitantes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para búsqueda
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('department', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%");
        });
    }

    /**
     * Obtener el nombre completo para display
     */
    public function getDisplayNameAttribute()
    {
        $display = $this->name;

        if ($this->department) {
            $display .= " ({$this->department})";
        }

        if ($this->position) {
            $display .= " - {$this->position}";
        }

        return $display;
    }

    /**
     * Verificar si el solicitante puede ser eliminado
     */
    public function getCanBeDeletedAttribute()
    {
        return !$this->serviceRequests()->exists();
    }
}
