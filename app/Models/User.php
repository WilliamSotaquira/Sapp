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
        return $this->belongsToMany(\App\Models\Company::class)->withTimestamps();
    }

    /**
     * Verificar si el usuario es técnico
     */
    public function isTechnician()
    {
        return $this->technician()->exists();
    }
}
