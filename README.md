<p align="center">
  <img src="https://raw.githubusercontent.com/cemfirat/repository-governance/main/assets/brand-banner.webp" alt="Cem Firat creative consultancy artwork" width="900" />
</p>

# AMBRA Projektmanagement 2.0.2

Geschlossene, frontend-basierte WordPress-Anwendung für AMBRA mit eigener UIkit-Loginseite.

## Neu in 2.0.2

- Automatisch angelegte Seite **Login** unter `/login/`.
- Nicht angemeldete Besucher werden immer zu `/login/` geleitet, nicht mehr zur klassischen WordPress-Loginseite.
- Nach erfolgreicher Anmeldung wird immer die statische Startseite **Dashboard** geöffnet.
- Wer angemeldet `/login/` aufruft, wird automatisch zum Dashboard weitergeleitet.
- Loginformular, Fehlermeldungen und Passwort-vergessen-Anforderung verwenden UIkit-Markup.
- Direkte Aufrufe der normalen WordPress-Anmeldung werden auf `/login/` umgeleitet. Technisch notwendige WordPress-Endpunkte für Abmeldung und den eigentlichen Passwort-Reset bleiben verfügbar.
- Die Loginseite liegt wie alle übrigen Systemseiten auf der obersten Seitenebene.
- Im Systemstatus wird die Loginseite separat geprüft.

## Voraussetzungen

- WordPress 6.5 oder neuer
- PHP 8.0 oder neuer
- ACF Pro
- YOOtheme Pro empfohlen und für das vollständige UIkit-Design vorgesehen

## Update

Das ZIP kann über **Plugins → Installieren → Plugin hochladen** über die vorhandene Version installiert werden. Der Plugin-Ordner und die Hauptdatei bleiben identisch; bestehende AMBRA-Daten werden nicht gelöscht.

Beim ersten Aufruf nach dem Update erstellt die Migration die fehlende Loginseite, hält alle Systemseiten auf der obersten Ebene und lässt das Dashboard als statische Startseite bestehen.

## Verhalten

- Abgemeldet: jeder normale Seitenaufruf führt zu `/login/`.
- Anmeldung erfolgreich: Weiterleitung zu `/` beziehungsweise zum Dashboard.
- Angemeldet und `/login/` aufgerufen: Weiterleitung zum Dashboard.
- Projektmitarbeiter: kein Zugriff auf `/wp-admin/`.
- Administratoren: Backend bleibt nach der Anmeldung weiterhin manuell erreichbar.

## Empfohlener Ablauf nach dem Update

1. Datenbanksicherung erstellen und möglichst zuerst auf Staging testen.
2. ZIP über die vorhandene Plugin-Version installieren und die vorhandene Version ersetzen.
3. Als Administrator **AMBRA → Einrichtung** öffnen.
4. **Seiten prüfen / reparieren** ausführen.
5. YOOtheme-, WordPress- und Server-Cache leeren.
6. In einem privaten Browserfenster `/` öffnen und die Weiterleitung zu `/login/` prüfen.
7. Anmeldung testen und kontrollieren, dass danach das Dashboard erscheint.
8. „Passwort vergessen?“ testen und den E-Mail-Versand der Installation kontrollieren.

## YOOtheme-Anpassung

Die Seite **Login** enthält den Shortcode `[ambra_login]`. Das Plugin liefert bereits die vollständige UIkit-Struktur. Du kannst die Seite mit YOOtheme Pro gestalten und den Shortcode in einem Shortcode-Element platzieren. Das Plugin überschreibt vorhandene Seiteninhalte oder YOOtheme-Builder-Daten bei späteren Reparaturen nicht.

## Private Dateien

Die WordPress-Anwendung selbst ist geschützt. Direkte Dateiadressen im normalen WordPress-Uploadordner benötigen für vollständigen Zugriffsschutz zusätzlich eine hostingabhängige Serverregel oder eine Private-Media-Lösung. Details stehen in `docs/SECURITY.md`.
