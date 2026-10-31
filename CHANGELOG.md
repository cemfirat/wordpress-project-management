# Changelog

## Unreleased

- Rollen-/Capability-Grenze in CI abgesichert: Projektmitarbeiter behalten operative Rechte, erhalten aber keine Settings-, Finanz-, internen Preis-, Katalog-Schreib- oder Team-Schreibrechte.
- Auftragsnummern für neue Projekte über `ambra_pm_project_number` konfigurierbar gemacht; bestehende Nummern und der Default `AMB-YYYY-######` bleiben unverändert.
- Server-seitige Workflow-Invarianten gehärtet: Angebotsannahme/-ablehnung kann nicht über generische Workflow-Requests umgangen werden; Positionsstornierungen nach Angebotsversand werden blockiert.
- Sichtbare Produktbezeichnung über eine zentrale Branding-Grenze von historischen `ambra_*` Speicher-/API-Kennungen getrennt.
- UIkit-Provider von YOOtheme Pro entkoppelt; YOOtheme bleibt der automatisch erkannte Standard-Provider.
- AMBRA-/Sonnenschutz-Beispieldaten aus dem wiederverwendbaren Demo-Lifecycle in ein austauschbares Demo-Profil verschoben.
- Legacy-Kompatibilitätsverträge für gespeicherte IDs, UIkit-Provider, Branding, Demo-Profil und Workflow-Regeln in CI ergänzt.
- Branch-CI vor Pull Requests verbindlich gemacht.
- Reproduzierbaren, verifizierten Plugin-ZIP-Build mit deterministischem CI-Doppelbuild ergänzt.
- Universalisierungs-, Paketierungs- und Distributionsgrenzen dokumentiert; bestehende AMBRA-Datenkennungen bleiben unverändert.

## 2.0.2

- Eigene Frontend-Loginseite unter `/login/` ergänzt und automatisch angelegt.
- Nicht angemeldete Besucher werden nicht mehr zur klassischen WordPress-Anmeldung, sondern zur AMBRA-Loginseite geleitet.
- Erfolgreiche Anmeldung führt immer zum Dashboard; angemeldete Benutzer werden von `/login/` ebenfalls dorthin weitergeleitet.
- UIkit-Loginformular mit Benutzer-/Schloss-Icons, „Angemeldet bleiben“, Meldungen und Passwort-vergessen-Ansicht ergänzt.
- Authentifizierung erfolgt serverseitig über WordPress `wp_signon()` vor der Seitenausgabe.
- Passwort-vergessen-Anforderung verwendet WordPress `retrieve_password()` und zeigt absichtlich eine neutrale Bestätigung.
- Direkte Aufrufe der klassischen Login- und Passwort-vergessen-Ansicht werden auf `/login/` umgeleitet; WordPress-Endpunkte für Logout und Reset-Key bleiben funktionsfähig.
- Systemstatus und Seitenreparatur um die zehnte Systemseite „Login“ erweitert.

## 2.0.1

- Dashboard-Systemseite wird verbindlich als `Dashboard` benannt.
- Alle neun AMBRA-Systemseiten werden bei Update und Reparatur auf die oberste Seitenebene verschoben; Seiteninhalte und YOOtheme-Builder-Daten bleiben unverändert.
- Robuste YOOtheme-Pro-Erkennung für aktive Child-Themes ergänzt. Die Prüfung berücksichtigt aktives Theme, Parent-Theme, Theme-Verzeichnisse sowie verfügbare YOOtheme-Runtime-Signale.
- Systemstatus zeigt den erkannten Namen des aktiven YOOtheme-/Child-Themes an.
- Zusätzlicher Systemstatus für die Seitenhierarchie ergänzt.
- Seitenreparatur aktualisiert anschließend auch die WordPress-Rewrite-Regeln.

## 2.0.0

- Website vollständig auf angemeldete Benutzer beschränkt.
- Dashboard automatisch als statische Startseite gesetzt.
- `/wp-admin/` für Nicht-Administratoren gesperrt; Administratorzugriff bleibt erhalten.
- WordPress-Adminleiste für Nicht-Administratoren ausgeblendet.
- Plugin-interne obere Navigation entfernt.
- Frontend-Markup auf UIkit-Komponenten, Klassen und Attribute umgestellt.
- ACF-Formulare werden automatisch mit UIkit-Formklassen ergänzt.
- UIkit-Bestätigungsdialoge mit sicherem Browser-Fallback ergänzt.
- Teammitglied → WordPress-Benutzer vollständig automatisiert.
- WordPress-Benutzer mit Rolle Projektmitarbeiter → Teammitglied synchronisiert.
- Bestehende Teammitglieder und Projektmitarbeiter werden beim Update nachsynchronisiert.
- Präfix `Privat:` für AMBRA-Datensätze entfernt.
- Auftragserstellung repariert: Systemfelder werden nicht mehr im Frontend übermittelt und still serverseitig geschützt.
- Kommerzielle Positionen werden beim Angebotsversand inklusive Repeater-Konfiguration eingefroren.
- Einmalige, klar markierte Beispieldaten ergänzt.
- Admin-Funktionen zum Löschen und Neuerstellen der Beispieldaten ergänzt.
- Frontend-Tabellen responsiv mit UIkit und mobilen Spaltenbeschriftungen aufgebaut.
- Rollen- und Upgrade-Migration so optimiert, dass keine Rollenoptionen oder Rewrite-Regeln bei jedem Seitenaufruf neu geschrieben werden.
- Private REST-, Sitemap-, XML-RPC- und Suchmaschinenzugriffe eingeschränkt.
