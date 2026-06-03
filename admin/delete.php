<?php

declare(strict_types=1);

use Esse\Auth;

if (!Auth::verifyCsrf()) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'CSRF-Fehler.'];
    header('Location: /admin/downloads');
    exit;
}

$typeParam = $_POST['type'] ?? '';
$type      = in_array($typeParam, ['public', 'private'], true) ? $typeParam : '';
$file      = basename((string) ($_POST['file'] ?? ''));
$subdir    = isset($_POST['dir']) ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_POST['dir']) : '';

$redirectBack = '/admin/downloads?tab=' . ($type ?: 'public')
    . ($subdir !== '' ? '&dir=' . rawurlencode($subdir) : '');

if ($type === '' || $file === '') {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ungültige Eingabe.'];
    header('Location: ' . $redirectBack);
    exit;
}

$base     = ESSE_ROOT . '/storage/downloads/' . $type;
$filepath = $base . ($subdir !== '' ? '/' . $subdir : '') . '/' . $file;
$realBase = realpath($base);
$realFile = realpath($filepath);

if (!$realFile || !$realBase || !str_starts_with($realFile, $realBase . '/')) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Datei nicht gefunden.'];
    header('Location: ' . $redirectBack);
    exit;
}

@unlink($realFile);

$_SESSION['flash'] = ['type' => 'success', 'message' => htmlspecialchars($file) . ' wurde gelöscht.'];
header('Location: ' . $redirectBack);
exit;
