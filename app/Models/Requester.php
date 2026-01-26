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

    public static function getDepartmentOptions(): array
    {
        return DepartmentOptions::all();
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
