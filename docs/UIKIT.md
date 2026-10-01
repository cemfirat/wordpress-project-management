# UIkit-Umsetzung

Die Plugin-Oberfläche verwendet UIkit-Komponenten und Attribute. Die neue Loginseite folgt der vom Projekt vorgegebenen Struktur und verwendet insbesondere:

- `uk-section`, `uk-flex`, `uk-flex-middle` und `uk-height-viewport` für die vertikale Ausrichtung
- `uk-container-expand` und `uk-width-large` für die Seiten- und Kartenbreite
- `uk-background-secondary`, `uk-light`, `uk-box-shadow-large` und `uk-border-rounded` für die Loginkarte
- `uk-form-stacked`, `uk-form-label`, `uk-form-controls`, `uk-inline`, `uk-input`, `uk-form-large` und `uk-checkbox` für das Formular
- `uk-form-icon` und `uk-icon` für Benutzer-, Schloss- und E-Mail-Symbole
- `uk-button`, `uk-button-primary`, `uk-button-large` für Aktionen
- `uk-alert-*` für Login-, Logout- und Passwortmeldungen

Das Plugin liefert UIkit bewusst nicht ein zweites Mal aus. In der bestehenden AMBRA-Installation stellt YOOtheme Pro das Framework bereit und wird automatisch erkannt. Für die universelle Plugin-Architektur ist YOOtheme Pro jedoch kein Bestandteil der Geschäftslogik: ein anderer UIkit-Provider kann dieselbe Aufgabe übernehmen und seinen Status über den Filter `ambra_pm_uikit_provider_info` melden. Die Plugin-CSS-Datei enthält nur kleine Layout-Fallbacks für Breite und Mobilabstände.

ACF erzeugt eigenes Formular-Markup. `assets/js/frontend.js` ergänzt deshalb dynamisch die passenden UIkit-Klassen und aktualisiert UIkit nach ACF-AJAX-Ereignissen.
