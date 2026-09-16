<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Public app-distribution page (/download-app).
 *
 * Every release APK is dropped into storage/app/public/apks/. The download
 * endpoint ALWAYS serves the newest APK found there, so the download link
 * never changes — deploying an update is just: copy the new .apk into that
 * folder (see scripts/deploy-apk.sh) and the /download-app page + download
 * button instantly serve the updated build.
 */
class AppDownloadController extends Controller
{
    /** Folder that receives every build's APK. */
    public const APK_DIR = 'apks';

    /**
     * Landing page: shows app info + a download button for the latest build.
     */
    public function page()
    {
        return view('download-app', [
            'apk' => $this->latestApk(),
            'downloadUrl' => route('download-app.download'),
        ]);
    }

    /**
     * Streams the newest APK with Android-friendly headers. Because the file
     * is resolved on every request, uploading a new build automatically makes
     * this same URL serve the updated APK.
     */
    public function download(Request $request): SymfonyResponse
    {
        $apk = $this->latestApk();

        if ($apk === null) {
            abort(404, 'No app build is available yet. Please try again later.');
        }

        return Response::download($apk['path'], $apk['filename'], [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    /**
     * Finds the latest APK in storage/app/public/apks.
     *
     * Builds are ranked by the version encoded in the filename
     * (e.g. "bizbite-1.2.3+45.apk" → 1.2.3 build 45); when versions tie or a
     * file has no version, the most recently modified file wins.
     *
     * @return array{path: string, filename: string, size: int, modified: int, version: string, build: string}|null
     */
    public static function latestApk(): ?array
    {
        $dir = storage_path('app/public/'.self::APK_DIR);

        if (! is_dir($dir)) {
            return null;
        }

        $candidates = [];

        foreach (glob($dir.'/*.apk') ?: [] as $path) {
            $filename = basename($path);
            $version = null;
            $build = null;

            if (preg_match('/(\d+(?:\.\d+){1,3})(?:\+(\d+))?/', $filename, $m)) {
                $version = $m[1];
                $build = $m[2] ?? null;
            }

            $candidates[] = [
                'path' => $path,
                'filename' => $filename,
                'size' => (int) filesize($path),
                'modified' => (int) filemtime($path),
                'version' => $version ?? '',
                'build' => $build ?? '',
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (array $a, array $b): int {
            // Both have versions → semantic compare, build number breaks ties.
            if ($a['version'] !== '' && $b['version'] !== '') {
                $cmp = version_compare($a['version'], $b['version']);

                if ($cmp !== 0) {
                    return $cmp;
                }

                return version_compare(
                    $a['build'] !== '' ? $a['build'] : '0',
                    $b['build'] !== '' ? $b['build'] : '0'
                );
            }

            // No usable version info → newest file on disk wins.
            return $a['modified'] <=> $b['modified'];
        });

        return array_pop($candidates);
    }
}
