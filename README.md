# esse-download

Download-Bereich Plugin für [ESSE CMS](https://github.com/nfsmw15/esse-cms).

[![Version](https://img.shields.io/badge/version-0.0.1-blue)](CHANGELOG.md)
[![License](https://img.shields.io/badge/license-AGPL--3.0--or--later-green)](LICENSE)
[![ESSE CMS](https://img.shields.io/badge/esse--cms-%3E%3D0.1.0-orange)](https://github.com/nfsmw15/esse-cms)

> Dieses Plugin funktioniert **nur in Verbindung mit [ESSE CMS](https://github.com/nfsmw15/esse-cms)** (≥ 0.1.0).

---

## Features

- **Öffentliche Downloads** — für alle Besucher abrufbar
- **Interne Downloads** — Dateiliste sichtbar, Download nur für eingeloggte Mitglieder
- **Unterordner-Navigation** (1 Ebene)
- **Sichere Dateiauslieferung** via PHP-Route — kein Direktzugriff auf Dateien
- **Path-Traversal-Schutz** via `realpath()`
- **Admin-Panel** — Dateien hochladen, löschen, Ordner erstellen
- **CSRF-Schutz** auf allen POST-Actions
- Theme-integriert über `PageRenderer`

**Unterstützte Dateiformate:** PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, GZ, TAR, TXT

---

## Voraussetzungen

- ESSE CMS ≥ 0.1.0
- PHP ≥ 8.1
- Schreibrechte auf `storage/downloads/` im CMS-Verzeichnis

---

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

---

## Verwendung

### Dateien hochladen

Über **Admin → Downloads** lassen sich Dateien in beide Bereiche hochladen.
Unterordner können im Admin-Panel erstellt werden und erscheinen automatisch
als Ordner-Navigation auf der Frontend-Seite.

### Menü-Eintrag anlegen

Den Download-Bereich unter **Admin → Menüs** auf die URL `/downloads` verlinken.

### Seitenstruktur

| URL | Beschreibung | Zugriff |
|-----|-------------|---------|
| `/downloads` | Download-Übersicht | öffentlich |
| `/downloads/get?type=public&file=...` | Datei herunterladen (öffentlich) | öffentlich |
| `/downloads/get?type=private&file=...` | Datei herunterladen (intern) | eingeloggt |
| `/admin/downloads` | Admin-Verwaltung | Admin |

---

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

---

## Sicherheit

- Alle Dateien werden über eine PHP-Route ausgeliefert — kein Direktzugriff möglich
- `realpath()` verhindert Path-Traversal-Angriffe
- Private Dateien prüfen `Auth::check()` vor der Auslieferung
- Dateiendungen werden gegen eine Whitelist geprüft
- Alle POST-Actions sind mit CSRF-Token gesichert
- Datei-Uploads werden mit `is_uploaded_file()` validiert

---

## ZIP erstellen

```bash
# Im Elternverzeichnis des Plugins:
zip -r esse-download-v0.0.1.zip esse-download/ \
  --exclude "esse-download/.git/*" \
  --exclude "esse-download/.vscode/*" \
  --exclude "esse-download/.claude/*" \
  --exclude "esse-download/node_modules/*" \
  --exclude "esse-download/.DS_Store"
```

---

## Lizenz

Copyright (C) 2026 Andreas P. <https://github.com/nfsmw15>  
SPDX-License-Identifier: AGPL-3.0-or-later

Dieses Programm ist freie Software. Details siehe [LICENSE](LICENSE).
