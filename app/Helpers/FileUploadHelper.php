<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadHelper
{
	/**
	 * Simpan foto profil ke direktori storage
	 *
	 * @param UploadedFile $file
	 * @param string $directory
	 * @return string $filePath
	 */
	public static function storePhoto(UploadedFile $file, $directory)
	{
		$extension = $file->getClientOriginalExtension();
		$filename = Str::random(25) . '.' . $extension;
		$filePath = $file->storeAs($directory, $filename, 'public');
		return $filePath;
	}

	/**
	 * Hapus foto profil yang ada di direktori storage
	 *
	 * @param string $filePath
	 * @return void
	 */
	public static function deletePhoto($filePath)
	{
		if (Storage::disk('public')->exists($filePath)) {
			Storage::disk('public')->delete($filePath);
		}
	}
}
