<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasanganCalon extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'partai_id' => 'integer',
    ];

    /**
     * Get the partai that owns the PasanganCalon
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partai(): BelongsTo
    {
        return $this->belongsTo(Partai::class, 'partai_id', 'id');
    }

    /**
     * Get all of the counting for the PasanganCalon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function counting(): HasMany
    {
        return $this->hasMany(QuickCount::class, 'pasangan_calon_id', 'id');
    }
}
