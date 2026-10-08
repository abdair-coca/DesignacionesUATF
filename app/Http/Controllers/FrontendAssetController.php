<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class FrontendAssetController extends Controller
{
    public function __invoke(string $path): Response
    {
        if ($path === '' || preg_match('~(^|[/\\\\])\.\.([/\\\\]|$)~', $path)) {
            abort(404);
        }

        $assetRoot = realpath(resource_path('assets'));

        if ($assetRoot === false) {
            abort(404);
        }

        $assetPath = realpath($assetRoot.DIRECTORY_SEPARATOR.$path);

        if (
            $assetPath === false
            || ! str_starts_with($assetPath, $assetRoot.DIRECTORY_SEPARATOR)
            || ! is_file($assetPath)
        ) {
            abort(404);
        }

        $contentType = match (strtolower(pathinfo($assetPath, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            default => abort(404),
        };

        $content = file_get_contents($assetPath);

        if (! is_string($content)) {
            abort(404);
        }

        return response($content, 200, [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
