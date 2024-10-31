<?php

namespace App\Helpers;

class PermissionHelper
{
	public static function getPermissionsByRole($roleId)
	{
		$permissions = [
			1 => [
				'view publikRequest',

				'aktifkan pengguna',
				'reset password',
				'update password',

				'create pengguna',
				'edit pengguna',
				'view pengguna',
				'import pengguna',
				'export pengguna',

				// 'create aktivitas',
				'edit aktivitas',
				// 'delete aktivitas',
				'view aktivitas',
				'import aktivitas',
				'export aktivitas',

				'view suaraKPU',
				// 'create suaraKPU',
				// 'edit suaraKPU',
				// 'delete suaraKPU',
				'import suaraKPU',

				'create upcomingTPS',
				'edit upcomingTPS',
				'delete upcomingTPS',
				'view upcomingTPS',
				// 'import upcomingTPS',
				// 'export upcomingTPS',

				'create quickCount',
				'edit quickCount',
				'view quickCount',

				'create paslon',
				'edit paslon',
				'view paslon',
			],
			2 => [
				'view publikRequest',

				'aktifkan pengguna',
				'update password',

				'create pengguna',
				'edit pengguna',
				'view pengguna',
				'export pengguna',

				'create aktivitas',
				// 'edit aktivitas',
				'delete aktivitas',
				'view aktivitas',
				'import aktivitas',
				'export aktivitas',

				'view suaraKPU',

				'create upcomingTPS',
				'edit upcomingTPS',
				'delete upcomingTPS',
				'view upcomingTPS',

				'edit quickCount', // quick count
				'view quickCount',

				'view paslon',
			],
			3 => [
				'view publikRequest',

				'update password',

				'create aktivitas',
				// 'edit aktivitas',
				// 'delete aktivitas',
				'view aktivitas',
				'import aktivitas',
				'export aktivitas',

				'view suaraKPU',

				'create upcomingTPS',
				'edit upcomingTPS',
				'delete upcomingTPS',
				'view upcomingTPS',

				'edit quickCount', // quick count
				'view quickCount',

				'view paslon',
			]
		];

		return $permissions[$roleId] ?? [];
	}
}
