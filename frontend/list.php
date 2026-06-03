<?php

declare(strict_types=1);

use Esse\Auth;

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
?>
<?php if (isset($_GET['err'])): ?>
<div class="alert alert-warning">
    <?php if ($_GET['err'] === 'login'): ?>
        Bitte <a href="/admin/login?redirect=/downloads" class="alert-link">einloggen</a>,
        um interne Dateien herunterzuladen.
    <?php else: ?>
        Datei nicht gefunden.
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Öffentliche Downloads -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-globe2"></i>
        <strong>Öffentliche Downloads</strong>
        <small class="text-secondary ms-1">– für alle verfügbar</small>
    </div>
    <div class="card-body">
        <?php if ($pubSub !== ''): ?>
        <p class="mb-3">
            <a href="/downloads" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Zurück
            </a>
            / <strong><?= htmlspecialchars($pubSub) ?></strong>
        </p>
        <?php endif; ?>

        <?php if (!empty($pubSubdirs) && $pubSub === ''): ?>
        <div class="row row-cols-2 row-cols-sm-4 g-3 mb-3">
            <?php foreach ($pubSubdirs as $dir): ?>
            <div class="col">
                <a href="/downloads?pdir=<?= rawurlencode($dir) ?>"
                   class="card h-100 text-center text-decoration-none">
                    <div class="card-body py-3">
                        <i class="bi bi-folder text-warning fs-3"></i>
                        <div class="mt-1 small"><?= htmlspecialchars($dir) ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($pubFiles)): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($pubFiles as $f):
                [$icon, $color] = esse_dl_icon($f['ext']);
                $url = '/downloads/get?type=public&file=' . rawurlencode($f['name'])
                     . ($pubSub !== '' ? '&dir=' . rawurlencode($pubSub) : '');
            ?>
            <a href="<?= $url ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                <i class="bi <?= $icon ?> <?= $color ?> fs-5"></i>
                <span class="flex-grow-1"><?= htmlspecialchars($f['name']) ?></span>
                <small class="text-secondary me-2">
                    <?= esse_dl_size($f['size']) ?> &middot; <?= date('d.m.Y', $f['mtime']) ?>
                </small>
                <i class="bi bi-download text-secondary"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php elseif (empty($pubSubdirs)): ?>
        <p class="text-secondary mb-0">
            <i class="bi bi-info-circle me-1"></i>Noch keine öffentlichen Dateien vorhanden.
        </p>
        <?php endif; ?>
    </div>
</div>

<!-- Interne Downloads (nur für eingeloggte User) -->
<?php if ($isLoggedIn): ?>
<div class="card" id="intern">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-lock"></i>
        <strong>Interne Downloads</strong>
        <small class="text-secondary ms-1">– nur für Mitglieder</small>
    </div>
    <div class="card-body">
        <?php if ($prvSub !== ''): ?>
        <p class="mb-3">
            <a href="/downloads#intern" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Zurück
            </a>
            / <strong><?= htmlspecialchars($prvSub) ?></strong>
        </p>
        <?php endif; ?>

        <?php if (!empty($prvSubdirs) && $prvSub === ''): ?>
        <div class="row row-cols-2 row-cols-sm-4 g-3 mb-3">
            <?php foreach ($prvSubdirs as $dir): ?>
            <div class="col">
                <a href="/downloads?idir=<?= rawurlencode($dir) ?>#intern"
                   class="card h-100 text-center text-decoration-none">
                    <div class="card-body py-3">
                        <i class="bi bi-folder text-warning fs-3"></i>
                        <div class="mt-1 small"><?= htmlspecialchars($dir) ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($prvFiles)): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($prvFiles as $f):
                [$icon, $color] = esse_dl_icon($f['ext']);
                $url = '/downloads/get?type=private&file=' . rawurlencode($f['name'])
                     . ($prvSub !== '' ? '&dir=' . rawurlencode($prvSub) : '');
            ?>
            <a href="<?= $url ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                <i class="bi <?= $icon ?> <?= $color ?> fs-5"></i>
                <span class="flex-grow-1"><?= htmlspecialchars($f['name']) ?></span>
                <small class="text-secondary me-2">
                    <?= esse_dl_size($f['size']) ?> &middot; <?= date('d.m.Y', $f['mtime']) ?>
                </small>
                <i class="bi bi-download text-secondary"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php elseif (empty($prvSubdirs)): ?>
        <p class="text-secondary mb-0">
            <i class="bi bi-info-circle me-1"></i>Noch keine internen Dateien vorhanden.
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
