<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceOrganizationLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used; no updated_at column exists.
     */
    public $timestamps = false;

    protected $fillable = [
        'evidence_id',
        'cut_id',
        'user_id',
        'source_path',
        'destination_path',
        'result',
        'error_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model and automatically set created_at on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (is_null($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Relación con la evidencia organizada.
     */
    public function evidence()
    {
        return $this->belongsTo(ServiceRequestEvidence::class, 'evidence_id');
    }

    /**
     * Relación con el corte destino.
     */
    public function cut()
    {
        return $this->belongsTo(Cut::class);
    }

    /**
     * Relación con el usuario que ejecutó la operación.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
