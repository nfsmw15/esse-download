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
$subdir    = isset($_POST['dir']) ? preg_replace('#[^a-zA-Z0-9_\-]#', '', (string) $_POST['dir']) : '';

$redirectBack = '/admin/downloads?tab=' . ($type ?: 'public')
    . ($subdir !== '' ? '&dir=' . rawurlencode($subdir) : '');

if ($type === '' || empty($_FILES['file']['tmp_name'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ungültige Eingabe.'];
    header('Location: ' . $redirectBack);
    exit;
}

$allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'gz', 'tar', 'txt'];
$filename   = basename((string) $_FILES['file']['name']);
$ext        = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt, true)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Dateityp nicht erlaubt.'];
    header('Location: ' . $redirectBack);
    exit;
}

if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ungültiger Upload.'];
    header('Location: ' . $redirectBack);
    exit;
}

$uploadDir = ESSE_ROOT . '/storage/downloads/' . $type . ($subdir !== '' ? '/' . $subdir : '');

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Upload-Verzeichnis konnte nicht erstellt werden.'];
    header('Location: ' . $redirectBack);
    exit;
}

$filepath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Upload fehlgeschlagen.'];
    header('Location: ' . $redirectBack);
    exit;
}

if (class_exists(\Esse\Media::class)) {
    $mediaPath = '/downloads/get?type=' . $type . '&file=' . rawurlencode($filename)
        . ($subdir !== '' ? '&dir=' . rawurlencode($subdir) : '');

    \Esse\Media::register($mediaPath, [
        'filename'    => $filename,
        'mime_type'   => mime_content_type($filepath) ?: '',
        'size'        => filesize($filepath) ?: 0,
        'visibility'  => $type, // 'public' | 'private' entspricht 1:1 der Esse\Media-Sichtbarkeit
        'uploaded_by' => Auth::id(),
        'source'      => 'esse-download',
    ]);
}

$_SESSION['flash'] = ['type' => 'success', 'message' => htmlspecialchars($filename) . ' wurde hochgeladen.'];
header('Location: ' . $redirectBack);
exit;
