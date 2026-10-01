# Architektur

## Verantwortlichkeiten

- **Plugin:** private Anwendung, Frontend-Login, Rollen, Datenmodell, Workflow, Kalkulation, Validierung, Team-/Benutzersynchronisation, Beispieldaten und Frontend-Ausgabe.
- **WordPress:** Authentifizierung, Auth-Cookies, Passwort-Reset-E-Mails und Benutzerkonten.
- **ACF Pro:** Feldgruppen, Beziehungen, Medienfelder und Frontend-Formulare.
- **UIkit-Provider:** Designsystem und UI-Komponenten. Die bestehende AMBRA-Installation verwendet YOOtheme Pro; die Geschäftslogik ist nicht an YOOtheme gebunden.

## Custom Post Types

- `ambra_customer` – Kunden
- `ambra_project` – Aufträge
- `ambra_visit` – Besichtigungen
- `ambra_position` – Auftragspositionen
- `ambra_blueprint` – Produkt-Blueprints
- `ambra_manufacturer` – Hersteller
- `ambra_supplier` – Lieferanten/Transport
- `ambra_team` – Team

Alle Geschäftsdaten werden als private Beiträge gespeichert. Nicht angemeldete REST-Anfragen werden blockiert.

## Frontend-Seiten

Das Plugin verwaltet Seiten-IDs statt fester Slugs. Es gibt zehn Systemseiten auf derselben obersten Ebene:

1. Login
2. Dashboard
3. Aufträge
4. Kunden
5. Besichtigungen
6. Auftragspositionen
7. Produkt-Blueprints
8. Hersteller
9. Lieferanten
10. Team

`Dashboard` bleibt die statische WordPress-Startseite. `Login` ist unter `/login/` öffentlich erreichbar, während alle anderen normalen Frontend-Seiten eine Anmeldung voraussetzen. Bei Migrationen werden bestehende Seiteninhalte und YOOtheme-Builder-Daten nicht überschrieben.

## Login-Ablauf

1. Ein nicht angemeldeter Besucher ruft eine normale Frontend-Seite auf.
2. `Privacy::require_login()` leitet zu der von WordPress verwalteten AMBRA-Seiten-ID für `Login` weiter.
3. Der Shortcode `[ambra_login]` rendert das UIkit-Formular.
4. `Login::handle_login_page()` verarbeitet POST-Daten vor der Ausgabe.
5. WordPress `wp_signon()` authentifiziert und setzt die Auth-Cookies.
6. Nach erfolgreicher Prüfung der AMBRA-Berechtigung erfolgt die Weiterleitung zum Dashboard.

Das Plugin speichert keine Passwörter und führt keine eigene Passwortprüfung durch.

## UI-Provider-Grenze

Das Frontend-Markup verwendet UIkit-Klassen und -Attribute. YOOtheme Pro ist der aktuell automatisch erkannte Provider, aber kein Bestandteil des Daten- oder Workflow-Kerns.

`Utils::uikit_provider_info()` kapselt diese Grenze. Andere Integrationen können über `ambra_pm_uikit_provider_info` einen UIkit-Provider melden, ohne bestehende AMBRA-Speicherkennungen oder Geschäftslogik zu verändern.

Das Plugin bündelt derzeit bewusst keine zweite UIkit-Kopie.

## Update/Migration

`Plugin::maybe_upgrade()` läuft auch beim Ersetzen eines aktiven Plugins durch ein ZIP-Update. Die Migration ist idempotent, erzeugt keine mehrfachen Seiten und ergänzt in 2.0.2 die Loginseite.
