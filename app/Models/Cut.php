<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cut extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'name',
        'start_date',
        'end_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function serviceRequests()
    {
        return $this->belongsToMany(ServiceRequest::class, 'cut_service_request')
            ->withTimestamps();
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDateRangeForQuery(): array
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        return [$start, $end];
    }

    public function containsDate($date): bool
    {
        if (empty($date)) {
            return false;
        }

        [$start, $end] = $this->getDateRangeForQuery();
        $reference = Carbon::parse($date);

        return $reference->between($start, $end, true);
    }

    public function overlapsRange($startDate, $endDate, ?int $ignoreCutId = null): bool
    {
        $query = static::query()
            ->where('contract_id', $this->contract_id)
            ->where('start_date', '<=', Carbon::parse($endDate))
            ->where('end_date', '>=', Carbon::parse($startDate));

        if ($ignoreCutId) {
            $query->where('id', '!=', $ignoreCutId);
        }

        return $query->exists();
    }
}
