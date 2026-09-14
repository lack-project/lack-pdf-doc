# lack-pdf-doc

Convenience- und Abstraktionsschicht für `tecnickcom/tc-lib-pdf`. Gemeinsame PDF-Erzeugung liegt unter `Lack\PdfDoc\Core`; konkrete Dokumenttypen kapseln ihre eigene API, Styles und Templates.

## Letterhead

`Lack\PdfDoc\Letterhead\LetterheadDocument` erzeugt einen klassischen Brief mit Logo, Absender-/Empfängerfenster, optionalem Kopfbereich, Markdown- oder HTML-Inhalt und bis zu vier Footer-Spalten. Das Styling wird über `LetterheadStyle` konfiguriert und vor dem Rendering in von tc-lib-pdf verarbeitbares CSS eingesetzt.

Siehe [`examples/letterhead/01-basic.php`](examples/letterhead/01-basic.php).

Weitere Dokumenttypen können mit eigenem Namespace und eigenem Template-Verzeichnis ergänzt werden, ohne die Letterhead-API oder den Core zu erweitern.
