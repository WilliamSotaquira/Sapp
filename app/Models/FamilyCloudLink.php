<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyCloudLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'service_family_id',
        'url',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function family()
    {
        return $this->belongsTo(ServiceFamily::class, 'service_family_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
