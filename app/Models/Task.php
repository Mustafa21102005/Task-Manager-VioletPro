<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\Recurrence;
use App\Enums\RecurrenceUnit;
use App\Support\RecurrenceRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, SoftDeletes, MassPrunable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'due_date',
        'priority',
        'is_completed',
        'recurrence',
        'recurrence_interval',
        'recurrence_unit',
        'recurrence_days',
        'spawned_next',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
            'priority' => Priority::class,
            'recurrence' => Recurrence::class,
            'recurrence_interval' => 'integer',
            'recurrence_unit' => RecurrenceUnit::class,
            'recurrence_days' => 'array',
            'spawned_next' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(7));
    }

    /**
     * The repeat rule as an object, so the date math and the label live in one place.
     */
    public function recurrenceRule(): ?RecurrenceRule
    {
        return match ($this->recurrence) {
            null => null,
            Recurrence::Daily => new RecurrenceRule(RecurrenceUnit::Day),
            Recurrence::Weekly => new RecurrenceRule(RecurrenceUnit::Week),
            Recurrence::Monthly => new RecurrenceRule(RecurrenceUnit::Month),
            Recurrence::Custom => new RecurrenceRule(
                $this->recurrence_unit ?? RecurrenceUnit::Week,
                max(1, (int) $this->recurrence_interval),
                array_map('intval', $this->recurrence_days ?? []),
            ),
        };
    }
}
