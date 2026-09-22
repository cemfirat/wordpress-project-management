# Sicherheitskonzept

- Nicht angemeldete Frontend-Zugriffe werden zur eigenen AMBRA-Loginseite `/login/` umgeleitet.
- Die Frontend-Anmeldung verwendet `wp_signon()` und damit die normale WordPress-Authentifizierung und Auth-Cookies.
- Das Passwort wird ausschließlich für den aktuellen Login-Request verarbeitet und nicht vom Plugin gespeichert.
- Das Formular enthält einen WordPress-Nonce und wird vor jeder Seitenausgabe verarbeitet.
- Öffentliche Loginfehler sind absichtlich generisch, damit nicht erkennbar ist, ob ein Benutzername oder eine E-Mail-Adresse existiert.
- Konten ohne `ambra_use_app` oder `manage_options` werden nach der Authentifizierung wieder abgemeldet und erhalten keinen Anwendungszugriff.
- Die Passwort-vergessen-Anforderung zeigt unabhängig vom Ergebnis dieselbe neutrale Meldung und verwendet WordPress `retrieve_password()`.
- Nicht angemeldete REST-Anfragen werden mit HTTP 401 blockiert.
- XML-RPC und WordPress-Sitemaps sind deaktiviert; die Website erhält `noindex, nofollow`.
- `/wp-admin/` ist für alle Benutzer ohne `manage_options` gesperrt. Technisch notwendige Endpunkte bleiben für authentifizierte Frontend-Aktionen erreichbar.
- Jede schreibende Plugin-Aktion prüft Nonce, Datensatztyp und Capability.
- Interne Preise und Finanzfelder sind nur mit den entsprechenden AMBRA-Capabilities sichtbar.

## Klassische WordPress-Endpunkte

Normale sichtbare Aufrufe der klassischen Loginseite und der klassischen Passwort-vergessen-Ansicht werden zu `/login/` geleitet. WordPress-Endpunkte für Abmeldung und die Verarbeitung eines Passwort-Reset-Schlüssels bleiben technisch verfügbar, weil WordPress diese für sichere Sitzungs- und Passwortfunktionen benötigt.

## Direkte Medien-URLs

Dateien unter `wp-content/uploads` werden normalerweise direkt vom Webserver ausgeliefert. Für vollständig geschützte Kundenfotos und Dokumente ist zusätzlich eine Serverregel oder ein Private-Media-Modul erforderlich.
