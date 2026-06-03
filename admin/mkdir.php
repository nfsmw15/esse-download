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
$dirname   = basename(trim((string) ($_POST['dirname'] ?? '')));

if ($type === '' || $dirname === '' || !preg_match('/^[a-zA-Z0-9_\-]+$/', $dirname)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ungültiger Ordnername. Nur Buchstaben, Zahlen, - und _ erlaubt.'];
    header('Location: /admin/downloads?tab=' . ($type ?: 'public'));
    exit;
}

$targetDir = ESSE_ROOT . '/storage/downloads/' . $type . '/' . $dirname;

if (is_dir($targetDir)) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Ordner "' . htmlspecialchars($dirname) . '" existiert bereits.'];
    header('Location: /admin/downloads?tab=' . $type);
    exit;
}

mkdir($targetDir, 0755, true);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Ordner "' . htmlspecialchars($dirname) . '" wurde erstellt.'];
header('Location: /admin/downloads?tab=' . $type);
exit;
