<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceFamily extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function serviceLevelAgreements()
    {
        return $this->hasMany(ServiceLevelAgreement::class);
    }

    public function activeServices()
    {
        return $this->services()->where('is_active', true);
    }

    public function activeSlas()
    {
        return $this->serviceLevelAgreements()->where('is_active', true);
    }
}
