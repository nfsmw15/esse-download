# esse-download

Download-Bereich Plugin für [ESSE CMS](https://github.com/nfsmw15/esse-cms).

[![Release](https://img.shields.io/github/v/release/nfsmw15/esse-download?label=release&color=blue)](https://github.com/nfsmw15/esse-download/releases)
[![License](https://img.shields.io/badge/license-AGPL--3.0--or--later-green)](LICENSE)
[![ESSE CMS](https://img.shields.io/badge/esse--cms-%3E%3D0.1.0-orange)](https://github.com/nfsmw15/esse-cms)

## Über das Plugin

esse-download stellt einen Download-Bereich für ESSE CMS bereit, der Dateien in zwei
getrennten Bereichen verwaltet: öffentlich zugängliche Downloads und interne Downloads,
deren Dateiliste zwar sichtbar ist, deren Download aber eingeloggten Mitgliedern
vorbehalten bleibt. Die Auslieferung erfolgt ausschließlich über eine geschützte
PHP-Route mit Path-Traversal-Schutz — ein Direktzugriff auf die Dateien ist nicht
möglich. Admin- und Frontend-Ausgabe sind vollständig theme-agnostisch über `Esse\Ui`
umgesetzt und passen sich automatisch an das aktive Theme an.

## Voraussetzungen

- ESSE CMS ≥ 0.1.0
- PHP ≥ 8.1
- Schreibrechte auf `storage/downloads/` im CMS-Verzeichnis

## Installation

### Via Admin-Panel (empfohlen)

1. [Aktuellste ZIP-Datei herunterladen](https://github.com/nfsmw15/esse-download/releases)
2. In ESSE CMS unter **Admin → Plugins → ZIP hochladen** installieren
3. Plugin unter **Admin → Plugins** aktivieren

### Manuell

1. Dieses Repository in `plugins/esse-download/` des CMS kopieren
2. Plugin unter **Admin → Plugins** aktivieren

Beim ersten Seitenaufruf legt das Plugin automatisch folgende Verzeichnisse an:

```
storage/downloads/
├── public/    ← Dateien für alle Besucher
└── private/   ← Dateien nur für eingeloggte Mitglieder
```

### ZIP selbst erstellen

```bash
# Im Elternverzeichnis des Plugins:
zip -r esse-download-v0.1.1.zip esse-download/ \
  --exclude "esse-download/.git/*" \
  --exclude "esse-download/.vscode/*" \
  --exclude "esse-download/.claude/*" \
  --exclude "esse-download/node_modules/*" \
  --exclude "esse-download/.DS_Store"
```

## Routen

| Route | Beschreibung | Sichtbarkeit |
|---|---|---|
| `/downloads` | Download-Übersicht mit Ordner-Navigation | öffentlich |
| `/downloads/get?type=public&file=...` | Datei aus dem öffentlichen Bereich herunterladen | öffentlich |
| `/downloads/get?type=private&file=...` | Datei aus dem internen Bereich herunterladen | Mitglieder |
| `/admin/downloads` | Admin-Verwaltung (Upload, Löschen, Ordner anlegen) | Admin |

## Features

- **Öffentliche Downloads** — für alle Besucher abrufbar
- **Interne Downloads** — Dateiliste sichtbar, Download nur für eingeloggte Mitglieder
- **Unterordner-Navigation** (1 Ebene)
- **Sichere Dateiauslieferung** via PHP-Route — kein Direktzugriff auf Dateien
- **Path-Traversal-Schutz** via `realpath()`
- **Admin-Panel** — Dateien hochladen, löschen, Ordner erstellen
- **CSRF-Schutz** auf allen POST-Actions
- **Mediathek-Integration** — hochgeladene Dateien werden automatisch in der ESSE-Mediathek
  (`/admin/media`) registriert, sofern verfügbar; Sichtbarkeit entspricht dem Download-Bereich
- Theme-integriert über `PageRenderer` und `Esse\Ui`

**Unterstützte Dateiformate:** PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, GZ, TAR, TXT

## Dateistruktur

```
esse-download/
├── plugin.json              ← Plugin-Metadaten
├── Plugin.php               ← Hauptklasse (boot, Routen, Storage-Init)
├── admin/
│   ├── index.php            ← Admin-Übersicht (Tabs, Upload, Dateiliste)
│   ├── upload.php           ← Upload-Handler
│   ├── delete.php           ← Lösch-Handler
│   └── mkdir.php            ← Ordner-erstellen-Handler
├── frontend/
│   ├── list.php             ← Download-Seite (Theme-integriert)
│   └── serve.php            ← Sichere Dateiauslieferung
├── README.md
├── CHANGELOG.md
└── LICENSE
```

Dateien werden **außerhalb des Plugin-Ordners** gespeichert, da `plugins/` per
`.htaccess` gesperrt ist:

```
ESSE_ROOT/storage/downloads/
├── public/    ← web-gesperrt, Auslieferung via PHP
└── private/   ← web-gesperrt, Auslieferung via PHP (nur eingeloggt)
```

## Sicherheit

- Alle Dateien werden über eine PHP-Route ausgeliefert — kein Direktzugriff möglich
- `realpath()` verhindert Path-Traversal-Angriffe
- Private Dateien prüfen `Auth::check()` vor der Auslieferung
- Dateiendungen werden gegen eine Whitelist geprüft
- Alle POST-Actions sind mit CSRF-Token gesichert
- Datei-Uploads werden mit `is_uploaded_file()` validiert

## Lizenz

AGPL-3.0-or-later — siehe [LICENSE](LICENSE)

Copyright (C) 2026 Andreas P. <https://github.com/nfsmw15>
