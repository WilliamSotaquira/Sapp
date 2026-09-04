<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'identification_number',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($role)
    {
        // Si tienes una columna 'role' en la tabla users
        if (isset($this->role)) {
            return $this->role === $role;
        }

        // Si tienes una relación muchos a muchos con roles
        if (method_exists($this, 'roles')) {
            return $this->roles->contains('name', $role);
        }

        return false;
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->id === 1; // El usuario con ID 1 es admin
    }

    /**
     * Verificar si el usuario tiene rol de técnico
     */
    public function isTechnicianRole()
    {
        return $this->hasRole('technician');
    }

    /**
     * Relación con perfil de técnico
     */
    public function technician()
    {
        return $this->hasOne(\App\Models\Technician::class);
    }

    public function companies()
    {
        return $this->belongsToMany(\App\Models\Company::class)
            ->withPivot(['entity_email', 'entity_position'])
            ->withTimestamps();
    }

    /**
     * IDs de TODAS las entidades a las que el usuario tiene acceso.
     *
     * Une dos fuentes, porque una persona puede acceder a una entidad por ser
     * usuario de ella (pivote company_user) o por atenderla como técnico
     * (pivote company_technician). Antes solo se miraba company_user, lo que
     * impedía a un técnico alternar a los contratos que atiende cuando su
     * usuario no estaba vinculado a esa entidad.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function accessibleCompanyIds(): \Illuminate\Support\Collection
    {
        $fromUser = $this->companies()->pluck('companies.id');

        $technician = $this->relationLoaded('technician')
            ? $this->technician
            : $this->technician()->first();

        $fromTechnician = $technician
            ? $technician->companies()->pluck('companies.id')
            : collect();

        return $fromUser->merge($fromTechnician)->unique()->values();
    }

    /**
     * Verificar si el usuario es técnico
     */
    public function isTechnician()
    {
        return $this->technician()->exists();
    }

    /**
     * Retorna el correo del usuario para una entidad específica.
     * Si no existe correo por entidad, usa el correo principal del usuario.
     */
    public function getEmailForCompany(?int $companyId): ?string
    {
        if (empty($companyId)) {
            return null;
        }

        $technician = $this->relationLoaded('technician')
            ? $this->technician
            : $this->technician()->first();

        if (!$technician) {
            return null;
        }

        return $technician->getInstitutionalEmailForCompany((int) $companyId);
    }

    public function getPositionForCompany(?int $companyId): ?string
    {
        if (empty($companyId)) {
            return null;
        }

        $technician = $this->relationLoaded('technician')
            ? $this->technician
            : $this->technician()->first();

        if (!$technician) {
            return null;
        }

        return $technician->getPositionForCompany((int) $companyId);
    }
}
