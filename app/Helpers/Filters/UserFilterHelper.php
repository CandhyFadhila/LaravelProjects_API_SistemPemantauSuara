<?php

namespace App\Helpers\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserFilterHelper
{
	/**
	 * Menerapkan filter ke query User berdasarkan request
	 *
	 * @param Builder $query
	 * @param array $filters
	 * @return Builder
	 */
	public static function applyFiltersUser(Builder $query, array $filters): Builder
	{
		// Filter status aktif
		if (isset($filters['status_aktif'])) {
			$statusAktif = $filters['status_aktif'];
			$query->where('status_aktif', '=', $statusAktif);
		}

		// Filter tahun periode
		if (isset($filters['periode'])) {
			$periode = $filters['periode'];
			$query->whereYear('tgl_diangkat', '<=', $periode);
		}

		// Filter pencarian
		if (isset($filters['search'])) {
			$searchTerm = '%' . $filters['search'] . '%';
			$query->where(function ($query) use ($searchTerm) {
				$query->where('nama', 'like', $searchTerm)
					->orWhere('no_hp', 'like', $searchTerm);
			});
		}

		return $query;
	}
}
