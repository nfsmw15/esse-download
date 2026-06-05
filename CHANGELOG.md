# Changelog

Alle nennenswerten Änderungen werden in dieser Datei dokumentiert.
Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
Versionierung nach [Semantic Versioning](https://semver.org/).

---

## [0.0.2] – 2026-06-05

### Geändert

- **Theme-agnostische UI-Schicht** — Alle Bootstrap-Klassen in Admin und Frontend durch `Esse\Ui`-Komponenten ersetzt; das Plugin funktioniert nun korrekt mit jedem ESSE-Theme
- Admin-Navigation: `<ul class="nav nav-tabs">` → `Ui::tabs()` (Öffentlich / Intern als JS-Tabs, beide Bereiche werden gleichzeitig geladen)
- Admin-Panels: `<div class="card">` → `Ui::panel()` für Upload-, Ordner-erstellen- und Dateiliste-Bereiche
- Admin-Dateiliste: `list-group` → `Ui::table()` mit Spalten Name / Größe / Typ / Aktionen
- Admin-Breadcrumb: Bootstrap Breadcrumb → `Ui::breadcrumb()`
- Admin-Löschen-Button: standalone `<form><button>` → `Ui::button()` mit `method='post'`
- Admin-Submit-Buttons: `<button class="btn …">` → `Ui::button()` mit `type='submit'` (kein eigenes Form)
- Admin-Leerzustand: `text-secondary` Absatz → `Ui::emptyState()`
- Frontend-Fehlermeldung: `<div class="alert">` → `Ui::alert()`
- Frontend-Bereiche: `<div class="card">` → `Ui::section()` (Öffentliche / Private Downloads)
- Frontend-Ordner-Grid: Bootstrap Row/Col-Raster → `Ui::grid()` mit `esse-grid-item--link` und `esse-grid-item-label`
- Frontend-Dateiliste: `list-group` → `Ui::table()` mit Spalten Name / Größe / Typ / Download-Button
- Frontend-Download-Button: einfacher Link → `Ui::button()` mit Icon
- Frontend-Navigation im Unterordner: Bootstrap Breadcrumb → `Ui::breadcrumb()`
- Frontend-Leerzustand: `<p class="text-secondary">` → `Ui::emptyState()`
- Frontend-Icon-Farben: Bootstrap `text-danger / text-primary / …` → `esse-color--danger / --primary / …`
- Frontend-Icon-Größen: Bootstrap `fs-3 / fs-5` → `esse-size--lg`
- Plugin.php: `PageRenderer::renderFile()` erhält jetzt den Icon-Parameter `'download'`, damit das Seitentitel-Icon im Theme erscheint

---

## [0.0.1] – 2026-06-02

### Hinzugefügt

- Öffentlicher Download-Bereich (`/downloads`) — für alle Besucher
- Interner Download-Bereich — nur für eingeloggte Mitglieder sichtbar und downloadbar
- Unterordner-Navigation (1 Ebene)
- Sichere Dateiauslieferung via PHP-Route mit Path-Traversal-Schutz
- Admin-Panel: Dateien hochladen, löschen, Ordner erstellen (Tabs Öffentlich / Intern)
- CSRF-Schutz auf allen POST-Actions
- Automatische Anlage von `storage/downloads/{public,private}/` beim ersten Start
- Unterstützte Formate: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, GZ, TAR, TXT
- Theme-Integration über `PageRenderer::renderFile()`
