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
        'start_date' => 'date',
        'end_date' => 'date',
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
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->end_date)->endOfDay();

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
            ->whereDate('start_date', '<=', Carbon::parse($endDate)->toDateString())
            ->whereDate('end_date', '>=', Carbon::parse($startDate)->toDateString());

        if ($ignoreCutId) {
            $query->where('id', '!=', $ignoreCutId);
        }

        return $query->exists();
    }
}
