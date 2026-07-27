<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cut extends Model
{
    use HasFactory;

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'contract_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'notes',
        'folder_path',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    // ==================== HELPERS ====================

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Close this cut at the given date and create the next open cut.
     *
     * @param Carbon|null $closeAt Date to close at (defaults to now)
     * @return Cut The newly created open cut
     */
    public function closeAndCreateNext(?Carbon $closeAt = null): Cut
    {
        $closeAt = $closeAt ?? now();

        // Fix the end_date to the close moment
        $this->update([
            'end_date' => $closeAt,
            'status' => self::STATUS_CLOSED,
            'closed_at' => $closeAt,
        ]);

        // Create the next cut starting 1 second after this one closes
        $nextStart = $closeAt->copy()->addSecond();
        $nextEnd = $nextStart->copy()->addDays(30)->setTime(23, 59, 59);
        $nextName = $this->generateNextCutName($nextStart);

        $nextCut = self::create([
            'contract_id' => $this->contract_id,
            'name' => $nextName,
            'start_date' => $nextStart,
            'end_date' => $nextEnd,
            'status' => self::STATUS_OPEN,
            'notes' => null,
            'created_by' => $this->created_by,
        ]);

        return $nextCut;
    }

    /**
     * Generate a name for the next cut. If a cut with the same month name
     * already exists for this contract, use the following month.
     */
    private function generateNextCutName(Carbon $startDate): string
    {
        $candidateName = ucfirst($startDate->locale('es')->translatedFormat('F Y'));

        // Check if a cut with this name already exists for the same contract
        $exists = self::where('contract_id', $this->contract_id)
            ->where('name', $candidateName)
            ->exists();

        if ($exists) {
            $candidateName = ucfirst($startDate->copy()->addMonth()->locale('es')->translatedFormat('F Y'));
        }

        return $candidateName;
    }

    // ==================== RELATIONS ====================

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

    // ==================== UTILITIES ====================

    /**
     * Determine if the cut has an associated folder on the filesystem.
     */
    public function hasFolder(): bool
    {
        return !empty($this->folder_path) && is_dir($this->folder_path);
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
