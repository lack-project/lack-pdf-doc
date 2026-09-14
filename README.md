# lack-pdf-doc

Convenience- und Abstraktionsschicht für `tecnickcom/tc-lib-pdf`. Gemeinsame PDF-Erzeugung liegt unter `Lack\PdfDoc\Core`; konkrete Dokumenttypen kapseln ihre eigene API, Styles und Templates.

## Einstieg

Die Beispiele sind als Lesereihe aufgebaut:

1. [`examples/01-quickstart.php`](examples/01-quickstart.php) zeigt den kürzesten vollständigen Ablauf: Dokument erzeugen, Ressourcen unter Aliasen registrieren, Markdown setzen, PDF-Bytes rendern und speichern.
2. [`examples/letterhead/01-basic.php`](examples/letterhead/01-basic.php) baut einen vollständigen Briefkopf mit eigenem Styling, Logo-Alias, Kopfbereich, Absender, Empfänger, Footer-Spalten und Markdown-Inhalt auf.
3. [`examples/letterhead/02-images.php`](examples/letterhead/02-images.php) ergänzt den Briefkopf um Bilder, deren Bytes aus einem anwendungseigenen Cloud-/Storage-Resolver kommen.

`PdfRenderer::render()` liefert immer den fertigen PDF-Inhalt als String. Die Anwendung entscheidet anschließend selbst, ob sie ihn speichert, versendet oder als HTTP-Response ausgibt.

## Sicherheitsmodell für Ressourcen

tc-lib-pdf erhält weder Internetzugriff noch direkten Zugriff auf Dokumentressourcen im Dateisystem. `allowedHosts`, `allowedPaths` und `markupAllowedPaths` werden leer gesetzt. Dokumente referenzieren Bilder und Fonts ausschließlich über Aliase.

Markdown referenziert Bilder z. B. mit `![Diagramm](image:chart)`. Der Alias `chart` wird vorher aus bereits vorhandenen Bytes, einer Data-URL oder einem anwendungseigenen Resolver erzeugt. Ein Cloud-/Storage-Connector läuft damit außerhalb von tc-lib-pdf und liefert nur die Bildbytes an den Dokument-Layer. Direkte Bild-URLs und Dateipfade sind nicht erlaubt.

Fonts werden ebenfalls über Aliase angesprochen, z. B. `font:body`. Ein Font-Alias darf nur auf einen bereits bei der PDF-Laufzeit registrierten Fontnamen zeigen. Laufzeitimporte von TTF/OTF/WOFF aus Benutzerpfaden sind bewusst nicht Teil dieser API, da tc-lib-pdf-font dafür auf Dateien zugreift. Eigene Fonts müssen daher außerhalb des Dokument-Renderings vertrauenswürdig vorbereitet/registriert werden.

## Letterhead

`Lack\PdfDoc\Letterhead\LetterheadDocument` erzeugt einen klassischen Brief mit Logo, Absender-/Empfängerfenster, optionalem Kopfbereich, Markdown- oder HTML-Inhalt und bis zu vier Footer-Spalten. Logo und Fonts werden ausschließlich über Aliase referenziert.

Weitere Dokumenttypen können mit eigenem Namespace und eigenem Template-Verzeichnis ergänzt werden, ohne die Letterhead-API oder den Core zu erweitern.
