<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaveWinner extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'quick_count_id' => 'integer',
        'kelurahan_id' => 'integer',
        'tps' => 'integer',
        'jumlah_suara' => 'integer',
    ];

    /**
     * Get the quick_counts that owns the SaveWinner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quick_counts(): BelongsTo
    {
        return $this->belongsTo(QuickCount::class, 'quick_count_id', 'id');
    }

        /**
     * Get the kelurahans that owns the QuickCount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kelurahans(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id', 'id');
    }
}
