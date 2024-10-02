<?php

namespace App\Helpers\Filters;

use Illuminate\Database\Eloquent\Builder;

class AktivitasFilterHelper
{
	/**
	 * Menerapkan filter ke query Aktivitas berdasarkan request
	 *
	 * @param Builder $query
	 * @param array $filters
	 * @return Builder
	 */
	public static function applyFiltersAktivitas(Builder $query, array $filters): Builder
	{
		if (isset($filters['status_aktivitas'])) {
			$statusAktivitas = $filters['status_aktivitas'];
			$query->whereHas('status_aktivitas', function ($query) use ($statusAktivitas) {
				if (is_array($statusAktivitas)) {
					$query->whereIn('id', $statusAktivitas);
				} else {
					$query->where('id', '=', $statusAktivitas);
				}
			});
		}

		if (isset($filters['kelurahan'])) {
			$kelurahan = $filters['kelurahan'];
			$query->whereHas('kelurahans', function ($query) use ($kelurahan) {
				if (is_array($kelurahan)) {
					$query->whereIn('id', $kelurahan);
				} else {
					$query->where('id', '=', $kelurahan);
				}
			});
		}

		if (isset($filters['search'])) {
			$searchTerm = '%' . $filters['search'] . '%';
			$query->where(function ($query) use ($searchTerm) {
				$query->whereHas('pelaksana_users', function ($query) use ($searchTerm) {
					$query->where('nama', 'like', $searchTerm);
				})->orWhere('nama_aktivitas', 'like', $searchTerm);
			});
		}

		return $query;
	}
}
