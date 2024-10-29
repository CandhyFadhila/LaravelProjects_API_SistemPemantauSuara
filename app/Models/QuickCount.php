<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'jumlah_suara' => 'integer',
        'kategori_suara_id' => 'integer',
    ];

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
