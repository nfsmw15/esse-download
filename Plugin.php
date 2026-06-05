<?php

declare(strict_types=1);

namespace EsseDownload;

use Esse\PageRenderer;
use Esse\Router;

class Plugin extends \Esse\Plugin
{
    public function boot(): void
    {
        $this->ensureStorageDirs();

        $this->addAdminNav('Downloads', '/admin/downloads', 'bi-download', 'admin.downloads');
        $this->registerPage('/downloads', 'Downloads', 'bi-download');

        $base = $this->basePath();

        Router::get('/downloads', function () use ($base) {
            PageRenderer::renderFile("{$base}/frontend/list.php", 'Downloads', 'public', 'download');
        }, ['name' => 'downloads.list', 'auth' => 'public']);

        Router::get('/downloads/get', function () use ($base) {
            require "{$base}/frontend/serve.php";
        }, ['name' => 'downloads.get', 'auth' => 'public']);

        Router::get('/admin/downloads', fn() => require "{$base}/admin/index.php",
            ['name' => 'admin.downloads', 'auth' => 'admin']);

        Router::post('/admin/downloads/upload', fn() => require "{$base}/admin/upload.php",
            ['name' => 'admin.downloads.upload', 'auth' => 'admin']);

        Router::post('/admin/downloads/delete', fn() => require "{$base}/admin/delete.php",
            ['name' => 'admin.downloads.delete', 'auth' => 'admin']);

        Router::post('/admin/downloads/mkdir', fn() => require "{$base}/admin/mkdir.php",
            ['name' => 'admin.downloads.mkdir', 'auth' => 'admin']);
    }

    private function ensureStorageDirs(): void
    {
        $base = ESSE_ROOT . '/storage/downloads';
        foreach (['public', 'private'] as $type) {
            $dir = "{$base}/{$type}";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function uninstall(): void
    {
        // Dateien in storage/downloads/ bleiben beim Deinstallieren erhalten.
    }
}
