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

				'create aktivitas',
				'edit aktivitas',
				'delete aktivitas',
				'view aktivitas',
				'import aktivitas',
				'export aktivitas',

				// 'view suaraKPU',
				// 'create suaraKPU',
				// 'edit suaraKPU',
				// 'delete suaraKPU',
				'import suaraKPU'
			],
			2 => [
				'view publikRequest',

				'aktifkan pengguna',
				'reset password',
				'update password',

				'create pengguna',
				'edit pengguna',
				'view pengguna',

				'create aktivitas',
				'edit aktivitas',
				'delete aktivitas',
				'view aktivitas',
				'import aktivitas',
				'export aktivitas',

				// 'view suaraKPU',
			],
			3 => [
				'view publikRequest',

				'update password',

				'create aktivitas',
				'edit aktivitas',
				'delete aktivitas',
				'view aktivitas',
				'import aktivitas',
				'export aktivitas',
			]
		];

		return $permissions[$roleId] ?? [];
	}
}
