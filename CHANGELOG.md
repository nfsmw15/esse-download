# Changelog

Alle nennenswerten Änderungen werden in dieser Datei dokumentiert.
Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
Versionierung nach [Semantic Versioning](https://semver.org/).

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
