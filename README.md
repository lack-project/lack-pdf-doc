# lack-pdf-doc

PHP-8.5-Convenience-Layer über `tecnickcom/tc-lib-pdf` für dokumentorientierte PDF-Erzeugung.

## Quickstart

Der einfache Einstieg braucht nur ein Dokument und Markdown:

```php
$document = (new \Lack\PdfDoc\SimpleDocument())
    ->markdown("# Hallo\n\nMein erstes PDF.");

$pdf = $document->toPdf();
```

Siehe [`examples/01-quickstart.php`](examples/01-quickstart.php).

## Brief mit wiederverwendbarer Konfiguration

`LetterConfig` beschreibt das wiederverwendbare Briefpapier: Logo-Alias, Rücksendeadresse, Layout, Footer und eigene Template-Variablen. `LetterDocument` enthält nur die Daten des konkreten Briefes wie Empfänger, Referenzblock, Dokumentvariablen und Markdown/HTML.

```php
$config = \Lack\PdfDoc\Letter\LetterConfig::fromArray($configData);

$letter = (new \Lack\PdfDoc\Letter\LetterDocument($config))
    ->recipientAddress("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->variables(['customerNumber' => '12345'])
    ->markdown($markdown);

$pdf = $letter->toPdf();
```

`fromArray()` ist absichtlich das einzige Konfigurations-Eingabeformat. JSON oder YAML werden von der Anwendung dekodiert und anschließend als Array übergeben. Dadurch bleibt die Library unabhängig von einem bestimmten Config-Parser.

Siehe [`examples/letter/01-basic.php`](examples/letter/01-basic.php).

## Ausfüllbare Formulare und Signaturfelder

Jeder Dokumenttyp kann interaktive PDF-Formulare erhalten. `form()` liefert eine kleine Convenience-API für Textfelder, Checkboxen, Auswahlfelder und digitale Signaturfelder. Die erzeugten AcroForm-Werte können in kompatiblen PDF-Viewern ausgefüllt, gespeichert und mit der gespeicherten PDF-Datei zurückgesendet werden.

```php
$document->form()
    ->text('full_name', x: 25, y: 80, width: 90, label: 'Name')
    ->checkbox('confirmed', x: 25, y: 110, label: 'Angaben geprüft')
    ->signature('signature', x: 25, y: 140, width: 80, height: 25, label: 'Digital unterschreiben');
```

Das Signaturfeld ist ein echtes PDF-Signatur-Widget (`/FT /Sig`) für eine digitale, typischerweise zertifikatsbasierte PDF-Signatur. Freihändiges Zeichnen mit Maus oder Finger ist dagegen eine Funktion des jeweiligen PDF-Viewers (z. B. Fill & Sign/Ink) und kann nicht viewerunabhängig als identisches Formularfeld erzwungen werden.

Siehe [`examples/forms/01-fill-and-sign.php`](examples/forms/01-fill-and-sign.php).

## Ressourcen-Sicherheit

tc-lib-pdf erhält weder Internetzugriff noch direkten Zugriff auf Dokumentressourcen im Dateisystem. Bilder und Fonts werden ausschließlich unter Aliasnamen registriert. In einer JSON-/YAML-Konfiguration stehen daher nur Aliase wie `company-logo` oder `body`, niemals URLs oder Dateipfade.

Bilder kommen als Bytes, Data-URL oder über einen anwendungseigenen Callback in die Library. Ein Cloud-/Storage-Connector läuft außerhalb von tc-lib-pdf. Im Markdown werden Bilder mit `image:<alias>` referenziert, zum Beispiel `![Chart](image:chart)`.

Fonts werden über `FontSource::builtIn()` auf die isoliert nutzbaren PDF-Core-Fonts abgebildet. Laufzeitimporte von TTF/OTF/WOFF gehören bewusst nicht in den Renderer, da der aktuelle Font-Importer dafür Dateizugriff benötigt.

Siehe [`examples/letter/02-images.php`](examples/letter/02-images.php).

## Struktur

- `Lack\PdfDoc\SimpleDocument` – neutrales Markdown-/HTML-Dokument.
- `Lack\PdfDoc\Letter\LetterConfig` – wiederverwendbare Briefpapier-Konfiguration.
- `Lack\PdfDoc\Letter\LetterDocument` – konkreter Brief.
- `Lack\PdfDoc\Letter\LetterLayout` – Maße und Typografie des Briefs.
- `Lack\PdfDoc\Letter\LetterFooter` – Firmen-, Bank-, Kontakt- und Rechtsangaben.
- `Lack\PdfDoc\Form\InteractiveForm` – ausfüllbare Formular- und digitale Signaturfelder.
- `Lack\PdfDoc\Resource\ImageSource` / `FontSource` – explizit registrierte Ressourcen.
- `Lack\PdfDoc\Core` – interne Rendering-Infrastruktur.
