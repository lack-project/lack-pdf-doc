# lack-pdf-doc

Convenience- und Abstraktionsschicht für `tecnickcom/tc-lib-pdf`. Gemeinsame PDF-Erzeugung liegt unter `Lack\PdfDoc\Core`; konkrete Dokumenttypen kapseln ihre eigene API, Styles und Templates.

## Sicherheitsmodell für Ressourcen

tc-lib-pdf erhält weder Internetzugriff noch direkten Zugriff auf Dokumentressourcen im Dateisystem. `allowedHosts`, `allowedPaths` und `markupAllowedPaths` werden leer gesetzt. Bilder werden ausschließlich über Aliase registriert und vor dem PDF-Rendering in `data:image/...` aufgelöst.

Markdown referenziert daher z. B. `![Diagramm](image:chart)`. Der Alias `chart` kann vorher aus Bytes, einer Datei oder einem anwendungseigenen Resolver erzeugt werden. Ein externer Connector läuft damit außerhalb von tc-lib-pdf und liefert lediglich die Bildbytes an den Dokument-Layer. Direkte Bild-URLs oder Dateipfade im Markdown/HTML werden abgewiesen.

## Letterhead

`Lack\PdfDoc\Letterhead\LetterheadDocument` erzeugt einen klassischen Brief mit Logo, Absender-/Empfängerfenster, optionalem Kopfbereich, Markdown- oder HTML-Inhalt und bis zu vier Footer-Spalten. Auch das Logo wird nur über einen registrierten Bildalias referenziert.

Siehe [`examples/letterhead/01-basic.php`](examples/letterhead/01-basic.php) und [`examples/letterhead/02-images.php`](examples/letterhead/02-images.php).

Weitere Dokumenttypen können mit eigenem Namespace und eigenem Template-Verzeichnis ergänzt werden, ohne die Letterhead-API oder den Core zu erweitern.
