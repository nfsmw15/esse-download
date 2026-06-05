<?php

declare(strict_types=1);

use Esse\Auth;
use Esse\Ui;

$isLoggedIn = Auth::check();
$allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'gz', 'tar', 'txt'];

$pubSub = isset($_GET['pdir']) ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_GET['pdir']) : '';
$prvSub = isset($_GET['idir']) ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_GET['idir']) : '';

function esse_dl_read_dir(string $dir, array $allowedExt): array
{
    $subdirs = [];
    $files   = [];
    if (!is_dir($dir)) {
        return [$subdirs, $files];
    }
    $h = opendir($dir);
    while (false !== ($e = readdir($h))) {
        if ($e === '.' || $e === '..') {
            continue;
        }
        $full = $dir . '/' . $e;
        if (is_dir($full)) {
            $subdirs[] = $e;
        } else {
            $ext = strtolower(pathinfo($e, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt, true)) {
                $files[] = [
                    'name'  => $e,
                    'ext'   => $ext,
                    'size'  => filesize($full),
                    'mtime' => filemtime($full),
                ];
            }
        }
    }
    closedir($h);
    sort($subdirs);
    usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    return [$subdirs, $files];
}

function esse_dl_icon(string $ext): array
{
    return match ($ext) {
        'pdf'              => ['bi-file-pdf',        'esse-color--danger'],
        'doc', 'docx'      => ['bi-file-word',        'esse-color--primary'],
        'xls', 'xlsx'      => ['bi-file-spreadsheet', 'esse-color--success'],
        'ppt', 'pptx'      => ['bi-file-slides',      'esse-color--warning'],
        'zip', 'rar',
        'gz',  'tar'       => ['bi-file-zip',         'esse-color--muted'],
        'txt'              => ['bi-file-text',         'esse-color--muted'],
        default            => ['bi-file',             'esse-color--muted'],
    };
}

function esse_dl_size(int $bytes): string
{
    if ($bytes >= 1_048_576) {
        return round($bytes / 1_048_576, 1) . ' MB';
    }
    if ($bytes >= 1_024) {
        return round($bytes / 1_024, 1) . ' KB';
    }
    return $bytes . ' B';
}

$pubDir = ESSE_ROOT . '/storage/downloads/public' . ($pubSub !== '' ? '/' . $pubSub : '');
$prvDir = ESSE_ROOT . '/storage/downloads/private' . ($prvSub !== '' ? '/' . $prvSub : '');

[$pubSubdirs, $pubFiles] = esse_dl_read_dir($pubDir, $allowedExt);
[$prvSubdirs, $prvFiles] = esse_dl_read_dir($prvDir, $allowedExt);

if (isset($_GET['err'])) {
    $errMsg = $_GET['err'] === 'login'
        ? 'Bitte <a href="/admin/login?redirect=/downloads">einloggen</a>, um interne Dateien herunterzuladen.'
        : 'Datei nicht gefunden.';
    echo Ui::alert('warning', $errMsg);
}

// ── Öffentliche Downloads ───────────────────────────────────────────────────
ob_start();

if ($pubSub !== '') {
    echo Ui::breadcrumb([
        ['label' => 'Öffentliche Downloads', 'url' => '/downloads'],
        ['label' => $pubSub],
    ]);
}

if (!empty($pubSubdirs) && $pubSub === '') {
    $items = [];
    foreach ($pubSubdirs as $dir) {
        $items[] = '<a href="/downloads?pdir=' . rawurlencode($dir) . '" class="esse-grid-item--link">'
                 . '<i class="bi bi-folder esse-color--warning esse-size--lg"></i>'
                 . '<div class="esse-grid-item-label">' . htmlspecialchars($dir) . '</div>'
                 . '</a>';
    }
    echo Ui::grid($items, ['cols' => 4]);
}

if (!empty($pubFiles)) {
    $rows = [];
    foreach ($pubFiles as $f) {
        [$icon, $color] = esse_dl_icon($f['ext']);
        $url = '/downloads/get?type=public&file=' . rawurlencode($f['name'])
             . ($pubSub !== '' ? '&dir=' . rawurlencode($pubSub) : '');
        $rows[] = [
            '<i class="bi ' . $icon . ' ' . $color . ' esse-size--lg"></i> ' . htmlspecialchars($f['name']),
            esse_dl_size($f['size']) . ' &middot; ' . date('d.m.Y', $f['mtime']),
            strtoupper($f['ext']),
            Ui::button('Herunterladen', $url, ['icon' => 'bi bi-download', 'size' => 'sm']),
        ];
    }
    echo Ui::table(['Name', 'Größe', 'Typ', ''], $rows);
} elseif (empty($pubSubdirs)) {
    echo Ui::emptyState('Keine Downloads verfügbar', '', ['icon' => 'bi bi-folder2-open']);
}

echo Ui::section('Öffentliche Downloads', ob_get_clean());

// ── Private Downloads (nur für eingeloggte User) ────────────────────────────
if ($isLoggedIn) {
    ob_start();

    if ($prvSub !== '') {
        echo Ui::breadcrumb([
            ['label' => 'Private Downloads', 'url' => '/downloads#intern'],
            ['label' => $prvSub],
        ]);
    }

    if (!empty($prvSubdirs) && $prvSub === '') {
        $items = [];
        foreach ($prvSubdirs as $dir) {
            $items[] = '<a href="/downloads?idir=' . rawurlencode($dir) . '#intern" class="esse-grid-item--link">'
                     . '<i class="bi bi-folder esse-color--warning esse-size--lg"></i>'
                     . '<div class="esse-grid-item-label">' . htmlspecialchars($dir) . '</div>'
                     . '</a>';
        }
        echo Ui::grid($items, ['cols' => 4]);
    }

    if (!empty($prvFiles)) {
        $rows = [];
        foreach ($prvFiles as $f) {
            [$icon, $color] = esse_dl_icon($f['ext']);
            $url = '/downloads/get?type=private&file=' . rawurlencode($f['name'])
                 . ($prvSub !== '' ? '&dir=' . rawurlencode($prvSub) : '');
            $rows[] = [
                '<i class="bi ' . $icon . ' ' . $color . ' esse-size--lg"></i> ' . htmlspecialchars($f['name']),
                esse_dl_size($f['size']) . ' &middot; ' . date('d.m.Y', $f['mtime']),
                strtoupper($f['ext']),
                Ui::button('Herunterladen', $url, ['icon' => 'bi bi-download', 'size' => 'sm']),
            ];
        }
        echo Ui::table(['Name', 'Größe', 'Typ', ''], $rows);
    } elseif (empty($prvSubdirs)) {
        echo Ui::emptyState('Keine Downloads verfügbar', '', ['icon' => 'bi bi-folder2-open']);
    }

    echo '<div id="intern">' . Ui::section('Private Downloads', ob_get_clean()) . '</div>';
}
