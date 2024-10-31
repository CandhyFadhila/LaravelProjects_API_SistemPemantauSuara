<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuickCount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'pasangan_calon_id' => 'integer',
        'periode' => 'integer',
        'kategori_suara_id' => 'integer',
    ];

    /**
     * Get all of the save_winners for the QuickCount
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function save_winners(): HasMany
    {
        return $this->hasMany(SaveWinner::class, 'quick_count_id', 'id');
    }

    /**
     * Get the paslon that owns the QuickCount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paslon(): BelongsTo
    {
        return $this->belongsTo(PasanganCalon::class, 'pasangan_calon_id', 'id');
    }

    /**
     * Get the suara_kategori that owns the QuickCount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function suara_kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSuara::class, 'kategori_suara_id', 'id');
    }
}
