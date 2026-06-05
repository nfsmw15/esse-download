<?php

declare(strict_types=1);

use Esse\Auth;
use Esse\Ui;

$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$tabParam = $_GET['tab'] ?? 'public';
$tab      = in_array($tabParam, ['public', 'private'], true) ? $tabParam : 'public';
$subdir   = isset($_GET['dir']) ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_GET['dir']) : '';

$allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'gz', 'tar', 'txt'];

function esse_dl_admin_read_dir(string $dir, array $allowedExt): array
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

function esse_dl_admin_icon(string $ext): array
{
    return match ($ext) {
        'pdf'              => ['bi-file-pdf',        'text-danger'],
        'doc', 'docx'      => ['bi-file-word',        'text-primary'],
        'xls', 'xlsx'      => ['bi-file-spreadsheet', 'text-success'],
        'ppt', 'pptx'      => ['bi-file-slides',      'text-warning'],
        'zip', 'rar',
        'gz',  'tar'       => ['bi-file-zip',         'text-secondary'],
        'txt'              => ['bi-file-text',         'text-secondary'],
        default            => ['bi-file',             'text-secondary'],
    };
}

function esse_dl_admin_size(int $bytes): string
{
    if ($bytes >= 1_048_576) {
        return round($bytes / 1_048_576, 1) . ' MB';
    }
    if ($bytes >= 1_024) {
        return round($bytes / 1_024, 1) . ' KB';
    }
    return $bytes . ' B';
}

function esse_dl_admin_build_tab(
    string $type,
    string $subdir,
    array  $subdirs,
    array  $files,
    string $csrf,
    array  $allowedExt
): string {
    $label = $type === 'public' ? 'Öffentlich' : 'Intern';

    ob_start();

    if ($subdir !== '') {
        echo Ui::breadcrumb([
            ['label' => $label, 'url' => '/admin/downloads?tab=' . $type],
            ['label' => $subdir],
        ]);
    }

    // Upload panel
    ob_start();
    ?>
    <form method="post" action="/admin/downloads/upload" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type"  value="<?= $type ?>">
        <?php if ($subdir !== ''): ?>
        <input type="hidden" name="dir" value="<?= htmlspecialchars($subdir) ?>">
        <?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col">
                <input type="file" name="file" class="form-control"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.gz,.tar,.txt" required>
                <div class="form-text">Erlaubt: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, GZ, TAR, TXT</div>
            </div>
            <div class="col-auto">
                <?= Ui::button('Hochladen', '#', ['type' => 'submit', 'icon' => 'bi bi-upload']) ?>
            </div>
        </div>
    </form>
    <?php
    echo Ui::panel('Datei hochladen', ob_get_clean(), ['icon' => 'bi bi-upload']);

    // Mkdir panel (only at folder root)
    if ($subdir === '') {
        ob_start();
        ?>
        <form method="post" action="/admin/downloads/mkdir">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="type"  value="<?= $type ?>">
            <div class="row g-2 align-items-end">
                <div class="col">
                    <input type="text" name="dirname" class="form-control"
                           placeholder="Ordnername (Buchstaben, Zahlen, -, _)"
                           pattern="[a-zA-Z0-9_\-]+" required>
                </div>
                <div class="col-auto">
                    <?= Ui::button('Ordner erstellen', '#', ['type' => 'submit', 'variant' => 'secondary', 'icon' => 'bi bi-folder-plus']) ?>
                </div>
            </div>
        </form>
        <?php
        echo Ui::panel('Ordner erstellen', ob_get_clean(), ['icon' => 'bi bi-folder-plus']);
    }

    // File list panel
    $listTitle = ($type === 'public' ? 'Öffentliche' : 'Interne') . ' Dateien'
               . ($subdir !== '' ? ' / ' . htmlspecialchars($subdir) : '');

    if (empty($subdirs) && empty($files)) {
        $listContent = Ui::emptyState('Noch keine Dateien vorhanden.', '', ['icon' => 'bi bi-folder2-open']);
    } else {
        $rows = [];
        foreach ($subdirs as $dir) {
            $nameCell = '<a href="/admin/downloads?tab=' . $type . '&dir=' . rawurlencode($dir) . '"'
                      . ' class="d-flex align-items-center gap-2 text-decoration-none">'
                      . '<i class="bi bi-folder text-warning fs-5"></i> '
                      . htmlspecialchars($dir) . '</a>';
            $rows[] = [$nameCell, '-', 'Ordner', ''];
        }
        foreach ($files as $f) {
            [$icon, $color] = esse_dl_admin_icon($f['ext']);
            $nameCell = '<div class="d-flex align-items-center gap-2">'
                      . '<i class="bi ' . $icon . ' ' . $color . ' fs-5"></i>'
                      . '<div>' . htmlspecialchars($f['name']) . '</div>'
                      . '</div>';
            $hidden = '<input type="hidden" name="type" value="' . htmlspecialchars($type) . '">'
                    . '<input type="hidden" name="file" value="' . htmlspecialchars($f['name']) . '">'
                    . ($subdir !== '' ? '<input type="hidden" name="dir" value="' . htmlspecialchars($subdir) . '">' : '');
            $deleteBtn = Ui::button('', '/admin/downloads/delete', [
                'variant' => 'danger',
                'size'    => 'sm',
                'icon'    => 'bi bi-trash3',
                'method'  => 'post',
                'hidden'  => $hidden,
                'attr'    => ['title' => 'Löschen'],
            ]);
            $rows[] = [
                $nameCell,
                esse_dl_admin_size($f['size']) . ' &middot; ' . date('d.m.Y', $f['mtime']),
                strtoupper($f['ext']),
                $deleteBtn,
            ];
        }
        $listContent = Ui::table(['Name', 'Größe', 'Typ', ''], $rows);
    }
    echo Ui::panel($listTitle, $listContent, ['icon' => 'bi bi-folder2-open']);

    return ob_get_clean();
}

// Subdir is tab-specific: only the active tab browses into a subfolder
$pubSubdir = $tab === 'public'  ? $subdir : '';
$prvSubdir = $tab === 'private' ? $subdir : '';

$pubStorageDir = ESSE_ROOT . '/storage/downloads/public'  . ($pubSubdir !== '' ? '/' . $pubSubdir : '');
$prvStorageDir = ESSE_ROOT . '/storage/downloads/private' . ($prvSubdir !== '' ? '/' . $prvSubdir : '');

[$pubSubdirs, $pubFiles] = esse_dl_admin_read_dir($pubStorageDir, $allowedExt);
[$prvSubdirs, $prvFiles] = esse_dl_admin_read_dir($prvStorageDir, $allowedExt);

$csrf = Auth::csrfToken();

$pageTitle   = 'Downloads';
$activeNav   = 'admin.downloads';
$topbarRight = Ui::button('Frontend', '/downloads', [
    'variant' => 'ghost',
    'size'    => 'sm',
    'icon'    => 'bi bi-box-arrow-up-right',
    'attr'    => ['target' => '_blank'],
]);

ob_start();

echo Ui::tabs([
    [
        'label'   => 'Öffentlich',
        'content' => esse_dl_admin_build_tab('public',  $pubSubdir, $pubSubdirs, $pubFiles, $csrf, $allowedExt),
        'active'  => $tab === 'public',
    ],
    [
        'label'   => 'Intern',
        'content' => esse_dl_admin_build_tab('private', $prvSubdir, $prvSubdirs, $prvFiles, $csrf, $allowedExt),
        'active'  => $tab === 'private',
    ],
]);

$content = ob_get_clean();
require ESSE_ROOT . '/admin/layout.php';
