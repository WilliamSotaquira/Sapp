<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['service_family_id', 'name', 'code', 'description', 'is_active', 'order'];

    public function family()
    {
        return $this->belongsTo(ServiceFamily::class, 'service_family_id');
    }

    public function subServices()
    {
        return $this->hasMany(SubService::class);
    }

    public function activeSubServices()
    {
        return $this->subServices()->where('is_active', true);
    }
}
