<?php

declare(strict_types=1);

use Esse\Auth;

$type   = isset($_GET['type']) ? (string) $_GET['type'] : '';
$file   = isset($_GET['file']) ? basename((string) $_GET['file']) : '';
$subdir = isset($_GET['dir'])  ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_GET['dir']) : '';

$allowedTypes = ['public', 'private'];
$allowedExt   = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'gz', 'tar', 'txt'];

if (!in_array($type, $allowedTypes, true) || $file === '') {
    http_response_code(400);
    exit;
}

if ($type === 'private' && !Auth::check()) {
    $redirect = urlencode('/downloads?err=login#intern');
    header('Location: /admin/login?redirect=' . $redirect);
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(403);
    exit;
}

$base     = ESSE_ROOT . '/storage/downloads/' . $type;
$filepath = $base . ($subdir !== '' ? '/' . $subdir : '') . '/' . $file;
$realBase = realpath($base);
$realFile = realpath($filepath);

if (!$realFile || !$realBase || !str_starts_with($realFile, $realBase . '/')) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $file) . '"');
header('Content-Length: ' . filesize($realFile));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
while (ob_get_level()) {
    ob_end_clean();
}
readfile($realFile);
exit;
