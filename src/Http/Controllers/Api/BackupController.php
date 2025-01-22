<?php

namespace GIS\LaravelBackups\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $files = [];
        $folder = $request->get("folder", config("laravel-backups.folder"));
        foreach (Storage::disk("yandex")->files($folder) as $fileName) {
            $ts = Storage::disk("yandex")->lastModified($fileName);
            $carbon = Carbon::createFromTimestamp($ts);
            $carbon->timezone = "Europe/Moscow";
            $files[] = [
                "name" => $fileName,
                "time" => $carbon->format("d.m.Y H:i:s"),
                "download" => route("admin.backups.download", ["file" => $fileName])
            ];
        }
        return response()->json($files);
    }

    public function make(Request $request, string $period): JsonResponse
    {
        $data = [
            "period" => $period,
        ];
        if ($folder = $request->get("folder", false)) {
            $data["--folder"] = $folder;
        }
        Artisan::queue("backup:app", $data);

        return response()
            ->json("Added to queue");
    }

    public function restore(Request $request, string $period): JsonResponse
    {
        $data = [
            "period" => $period,
        ];
        if ($folder = $request->get("folder", false)) {
            $data["--folder"] = $folder;
        }
        Artisan::queue("restore:app", $data);

        return response()
            ->json("Added to queue");
    }

    public function download(Request $request)
    {
        if (
            ! empty(config("laravel-backups.keyId")) &&
            ! empty(config("laravel-backups.keySecret")) &&
            ! empty(config("laravel-backups.bucket")) &&
            ! empty(config("laravel-backups.folder"))
        ) {
            $file = $request->get("file");
            if (Storage::disk("yandex")->exists($file)) {
                return Storage::disk("yandex")->download($file);
            }
        }
        abort(404);
    }
}
