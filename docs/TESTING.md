# Abnahmetest 2.0.2

1. Plugin über die bestehende Installation aktualisieren.
2. **AMBRA → Einrichtung → Seiten prüfen / reparieren** ausführen.
3. Prüfen, dass zehn Systemseiten vorhanden sind und alle auf der obersten Seitenebene liegen.
4. Prüfen, dass `Dashboard` als statische Startseite gesetzt bleibt.
5. In einem privaten Browserfenster `/` aufrufen: Weiterleitung zu `/login/`.
6. Eine andere AMBRA-Seite abgemeldet aufrufen: ebenfalls Weiterleitung zu `/login/`.
7. `/wp-login.php` ohne Aktion aufrufen: Weiterleitung zu `/login/`.
8. Falsche Zugangsdaten eingeben: UIkit-Fehlermeldung auf `/login/`, keine klassische WordPress-Loginseite.
9. Gültige Projektmitarbeiter-Zugangsdaten eingeben: Weiterleitung zum Dashboard.
10. Gültige Administrator-Zugangsdaten eingeben: ebenfalls Weiterleitung zum Dashboard; `/wp-admin/` bleibt danach manuell erreichbar.
11. Angemeldet `/login/` aufrufen: Weiterleitung zum Dashboard.
12. „Angemeldet bleiben“ testen.
13. „Passwort vergessen?“ öffnen, Anfrage absenden und neutrale Erfolgsmeldung prüfen.
14. Abmelden und kontrollieren, dass wieder `/login/` angezeigt wird.
15. Als Projektmitarbeiter `/wp-admin/` aufrufen: Weiterleitung zur Startseite.
16. YOOtheme-/Server-Caches leeren und Loginseite auf Smartphone- und Desktopbreite prüfen.
17. Kunden, Aufträge und Beispielworkflow stichprobenartig prüfen, um Regressionen auszuschließen.
