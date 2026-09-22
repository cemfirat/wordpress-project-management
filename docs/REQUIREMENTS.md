# AMBRA Projektmanagement 2.0.2 – Anforderungen

## Betriebsmodus

- Die WordPress-Website ist eine geschlossene interne Anwendung.
- Nicht angemeldete Besucher werden immer zur eigenen Frontend-Seite `/login/` weitergeleitet.
- Die klassische WordPress-Anmeldeseite ist für den normalen Login nicht die sichtbare Oberfläche.
- Nach erfolgreicher Anmeldung öffnet sich immer die statische Startseite `Dashboard`.
- Angemeldete Benutzer werden beim Aufruf von `/login/` zum Dashboard weitergeleitet.
- Login, Dashboard, Aufträge, Kunden, Besichtigungen, Auftragspositionen, Hersteller, Lieferanten, Produkt-Blueprints und Team liegen auf derselben obersten Seitenebene.
- Nur Administratoren mit `manage_options` dürfen das WordPress-Backend verwenden.
- Projektmitarbeiter arbeiten ausschließlich im Frontend und sehen keine WordPress-Adminleiste.
- Navigation wird in WordPress/YOOtheme erstellt; das Plugin gibt keine eigene Hauptnavigation aus.

## Login

- Das Loginformular verwendet WordPress-Authentifizierung und keine eigene Benutzerdatenbank.
- Das Formular unterstützt Benutzername oder E-Mail-Adresse, Passwort und „Angemeldet bleiben“.
- Fehlermeldungen verraten nicht, ob ein bestimmtes Benutzerkonto existiert.
- Nur Administratoren und Benutzer mit Zugriff auf die AMBRA-Anwendung dürfen sich über die Frontend-Anmeldung anmelden.
- Die Passwort-vergessen-Anforderung befindet sich ebenfalls auf `/login/` und verwendet den WordPress-E-Mail-Prozess.
- Der eigentliche Link aus der WordPress-Passwort-E-Mail darf weiterhin den technisch erforderlichen WordPress-Reset-Endpunkt verwenden.

## Frontend und UIkit

- Die Plugin-Ausgabe verwendet UIkit-Komponenten und deren `uk-*` Klassen/Attribute.
- Die Loginseite verwendet unter anderem `uk-section`, `uk-flex`, `uk-height-viewport`, `uk-background-secondary`, `uk-form-*`, `uk-input`, `uk-checkbox`, `uk-button` und `uk-icon`.
- YOOtheme Pro stellt das UIkit-CSS und -JavaScript bereit.
- Tabellen sind responsiv, ACF-Formulare werden im Browser mit UIkit-Klassen ergänzt.

## Team und Benutzer

- Ein Teammitglied erzeugt automatisch einen WordPress-Benutzer mit der Rolle `Projektmitarbeiter`, sofern zur E-Mail-Adresse noch kein Benutzer existiert.
- Ein im Backend angelegter Benutzer mit der Rolle `Projektmitarbeiter` erzeugt beziehungsweise aktualisiert automatisch den passenden Team-Datensatz.
- Das Verknüpfungsfeld wird im Frontend nicht angezeigt.

## Daten

- Bestehende AMBRA-Daten bleiben beim Update erhalten.
- Beispieldaten bleiben klar gekennzeichnet und können getrennt von echten Geschäftsdaten gelöscht oder neu angelegt werden.
- Automatisch verwaltete Systemfelder werden nicht in Frontend-Formularen übermittelt und serverseitig geschützt.
