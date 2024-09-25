<?php

namespace App\Helpers;

class PermissionHelper
{
	public static function getPermissionsByRole($roleId)
	{
		$permissions = [
			1 => [
				'create pengguna',
				'edit pengguna',
				'delete pengguna',
				'view pengguna',
				'create aktivitas',
				'edit aktivitas',
				'delete aktivitas',
				'view aktivitas'
			]
		];

		return $permissions[$roleId] ?? [];
	}
}
