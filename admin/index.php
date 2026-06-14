<?php

declare(strict_types=1);

use Esse\Auth;

$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$tabParam = $_GET['tab'] ?? 'public';
$tab      = in_array($tabParam, ['public', 'private'], true) ? $tabParam : 'public';
$subdir   = isset($_GET['dir']) ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_GET['dir']) : '';

$allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'gz', 'tar', 'txt'];
$storageDir = ESSE_ROOT . '/storage/downloads/' . $tab . ($subdir !== '' ? '/' . $subdir : '');

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

[$subdirs, $files] = esse_dl_admin_read_dir($storageDir, $allowedExt);

// Mediathek nachträglich mit vorhandenen Dateien befüllen (additiv, nur wenn verfügbar).
if (class_exists(\Esse\Media::class)) {
    foreach ($files as $f) {
        $mediaPath = '/downloads/get?type=' . $tab . '&file=' . rawurlencode($f['name'])
            . ($subdir !== '' ? '&dir=' . rawurlencode($subdir) : '');

        if (\Esse\Media::findByPath($mediaPath)) {
            continue;
        }

        $filepath = $storageDir . '/' . $f['name'];
        \Esse\Media::register($mediaPath, [
            'filename'   => $f['name'],
            'mime_type'  => mime_content_type($filepath) ?: '',
            'size'       => $f['size'],
            'visibility' => $tab, // 'public' | 'private' entspricht 1:1 der Esse\Media-Sichtbarkeit
            'source'     => 'esse-download',
        ]);
    }
}

$csrf = Auth::csrfToken();

$pageTitle   = 'Downloads';
$activeNav   = 'admin.downloads';
$topbarRight = '<a href="/downloads" target="_blank" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-box-arrow-up-right me-1"></i> Frontend
</a>';

ob_start();
?>
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link<?= $tab === 'public' ? ' active' : '' ?>" href="/admin/downloads?tab=public">
            <i class="bi bi-globe2 me-1"></i>Öffentlich
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $tab === 'private' ? ' active' : '' ?>" href="/admin/downloads?tab=private">
            <i class="bi bi-lock me-1"></i>Intern
        </a>
    </li>
</ul>

<?php if ($subdir !== ''): ?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="/admin/downloads?tab=<?= $tab ?>">
                <i class="bi bi-folder2-open me-1"></i><?= $tab === 'public' ? 'Öffentlich' : 'Intern' ?>
            </a>
        </li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($subdir) ?></li>
    </ol>
</nav>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header py-2">
        <i class="bi bi-upload me-1"></i> Datei hochladen
    </div>
    <div class="card-body">
        <form method="post" action="/admin/downloads/upload" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="type"  value="<?= $tab ?>">
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Hochladen
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($subdir === ''): ?>
<div class="card mb-4">
    <div class="card-header py-2">
        <i class="bi bi-folder-plus me-1"></i> Ordner erstellen
    </div>
    <div class="card-body">
        <form method="post" action="/admin/downloads/mkdir">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="type"  value="<?= $tab ?>">
            <div class="row g-2 align-items-end">
                <div class="col">
                    <input type="text" name="dirname" class="form-control"
                           placeholder="Ordnername (Buchstaben, Zahlen, -, _)"
                           pattern="[a-zA-Z0-9_\-]+" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-folder-plus"></i> Erstellen
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header py-2">
        <i class="bi bi-folder2-open me-1"></i>
        <?= $tab === 'public' ? 'Öffentliche' : 'Interne' ?> Dateien
        <?php if ($subdir !== ''): ?>
        &nbsp;/&nbsp;<strong><?= htmlspecialchars($subdir) ?></strong>
        <?php endif; ?>
    </div>
    <?php if (empty($subdirs) && empty($files)): ?>
    <div class="card-body text-secondary">
        <i class="bi bi-info-circle me-1"></i>Noch keine Dateien vorhanden.
    </div>
    <?php else: ?>
    <div class="list-group list-group-flush">

        <?php foreach ($subdirs as $dir): ?>
        <a href="/admin/downloads?tab=<?= $tab ?>&dir=<?= rawurlencode($dir) ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-2">
            <i class="bi bi-folder text-warning fs-5"></i>
            <span class="flex-grow-1"><?= htmlspecialchars($dir) ?></span>
            <i class="bi bi-chevron-right text-secondary ms-auto"></i>
        </a>
        <?php endforeach; ?>

        <?php foreach ($files as $f):
            [$icon, $color] = esse_dl_admin_icon($f['ext']);
        ?>
        <div class="list-group-item d-flex align-items-center gap-2">
            <i class="bi <?= $icon ?> <?= $color ?> fs-5"></i>
            <div class="flex-grow-1">
                <div><?= htmlspecialchars($f['name']) ?></div>
                <small class="text-secondary">
                    <?= esse_dl_admin_size($f['size']) ?> &middot; <?= date('d.m.Y', $f['mtime']) ?>
                </small>
            </div>
            <form method="post" action="/admin/downloads/delete" class="flex-shrink-0"
                  onsubmit="return confirm('<?= htmlspecialchars(addslashes($f['name']), ENT_QUOTES) ?> wirklich löschen?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="type"  value="<?= $tab ?>">
                <input type="hidden" name="file"  value="<?= htmlspecialchars($f['name']) ?>">
                <?php if ($subdir !== ''): ?>
                <input type="hidden" name="dir" value="<?= htmlspecialchars($subdir) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-outline-danger btn-sm" title="Löschen">
                    <i class="bi bi-trash3"></i>
                </button>
            </form>
        </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ESSE_ROOT . '/admin/layout.php';
