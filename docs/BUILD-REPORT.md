# Build- und Prüfbericht 2.0.2

## Änderungen

- Neue Systemseite `Login` mit Slug `/login/` und Shortcode `[ambra_login]`.
- Eigene UIkit-Anmeldeoberfläche und eigene Passwort-vergessen-Anforderung.
- WordPress-Authentifizierung über `wp_signon()` vor der Seitenausgabe.
- Neutrale Passwort-Reset-Anforderung über `retrieve_password()`.
- Neue Redirectregeln für ausgeloggte Besucher, eingeloggte Besucher auf `/login/`, direkte klassische Loginaufrufe und Logout.
- Systemstatus und Seitenmigration von neun auf zehn Seiten erweitert.

## Kritisch geprüfte Punkte

- Loginseite ist die einzige normale Frontend-Seite, die abgemeldet erreichbar bleibt.
- Dashboard bleibt die statische Startseite.
- Login-POST läuft vor jeglicher HTML-Ausgabe, damit WordPress Auth-Cookies setzen kann.
- Passwörter werden nicht gespeichert oder protokolliert.
- Fehlertexte erlauben keine Benutzerkonten-Erkennung.
- Nicht berechtigte WordPress-Konten erhalten keinen AMBRA-Zugriff.
- Kernfunktionen für Logout und Passwort-Reset-Schlüssel werden nicht durch die Login-Umleitung blockiert.
- Bestehende Seiten-IDs, YOOtheme-Inhalte, ACF-Felder und Geschäftsdaten bleiben erhalten.

## Statische Prüfungen

- PHP-Syntaxprüfung aller PHP-Dateien
- JavaScript-Syntaxprüfung
- JSON-Parsing aller ACF-Dateien
- Eindeutigkeit aller ACF Field Keys
- Prüfung der zehn Seitendefinitionen einschließlich Login
- Suche nach veralteter Versionsnummer in ausführbarem Plugin-Code
- ZIP nach dem Packen erneut entpackt und geprüft

## Laufzeithinweis

Ein vollständiger Integrationstest mit der konkreten WordPress-, ACF-Pro-, YOOtheme-Pro-, PHP-, Mail- und Cache-Konfiguration muss auf Staging beziehungsweise der tatsächlichen Installation erfolgen. Insbesondere der Versand von Passwort-Reset-E-Mails hängt von der WordPress-Mailkonfiguration ab.
