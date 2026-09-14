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

`LetterConfig` beschreibt aktuell das wiederverwendbare Briefpapier. Der typische Einstieg lädt eine JSON- oder YAML-Datei mit `LetterConfig::fromFile()`; `fromArray()` bleibt für bereits vorliegende Arrays verfügbar.

```php
$config = \Lack\PdfDoc\Letter\LetterConfig::fromFile(__DIR__ . '/letter.yaml');

$letter = (new \Lack\PdfDoc\Letter\LetterDocument($config))
    ->recipientAddress("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->variables(['customerNumber' => '12345'])
    ->markdown($markdown);

$pdf = $letter->toPdf();
```

Mit `phore/filesystem` steht für YAML-Front-Matter die bestehende `PhoreFile::get_front_matter()`-Implementierung zur Verfügung.

Ressourcen können direkt in der Config angegeben werden. `file://assets/company-logo.png` ist relativ zur geladenen YAML-/JSON-Datei. `file:///opt/company/company-logo.png` ist ein absoluter Pfad. Eine `data:image/...`-URL kann direkt eingebettet werden. Die Datei wird beim Config-Laden gelesen; tc-lib-pdf selbst erhält den Pfad nicht.

Fonts werden in derselben Config unter Aliasnamen festgelegt, zum Beispiel `body: builtin://helvetica`.

Siehe [`examples/letter/01-basic.php`](examples/letter/01-basic.php) und [`examples/letter/letter.yaml`](examples/letter/letter.yaml).

## Entwurf: generische TemplateDocument-Pipeline

Das Zielbild verwendet `TemplateDocument` für alle gestalteten Dokumenttypen. Ein Brief ist dann kein eigener PHP-Dokumenttyp mehr, sondern eine Template-Vererbungskette.

Die Beispiele zeigen drei Ebenen:

1. [`letter-defaults.template.html`](examples/templates/letter-defaults.template.html) enthält Standardlogo, Firmenadresse, Fonts und Grundlayout.
2. [`letterhead.template.html`](examples/templates/letterhead.template.html) erbt diese Defaults und baut daraus Briefkopf und Footer.
3. [`letter.template.html`](examples/templates/letter.template.html) erbt den Briefkopf und ergänzt Empfänger, Betreff, Grußformel, `{{ content }}` und Abschluss.

Die konkrete [`letter.md`](examples/templates/letter.md) enthält nur Front-Matter-Dokumentdaten und den Markdown-Hauptinhalt. Metadaten aus dem Dokument überschreiben geerbte Template-Defaults. [`letter-custom-logo.md`](examples/templates/letter-custom-logo.md) zeigt das gezielt für `company.logo`: derselbe Briefkopf wird verwendet, nur das Logo wird im Dokument-Front-Matter ersetzt.

Template-Vererbung verwendet `{{ template }}` für den Body des erbenden Child-Templates. `{{ content }}` ist ausschließlich der gerenderte Markdown-Hauptinhalt des konkreten Dokuments. Bilder können über `{{ image:meta.company.logo }}` aus Metadaten eingebunden werden.

```php
$document = \Lack\PdfDoc\Template\TemplateDocument::fromTemplateFile(
    __DIR__ . '/letter.template.html',
    safe: true,
)
    ->fromMarkdownFile(__DIR__ . '/letter.md');

$pdf = $document->toPdf();
```

`fromMarkdownFile()` soll intern `phore_file(...)->get_front_matter()` verwenden. `safe: false` bleibt Standard und verhindert automatisches relatives Nachladen aus Template-Front-Matter; `safe: true` erlaubt relative `file://`-Referenzen innerhalb einer ausdrücklich vertrauenswürdigen Template-Kette.

Siehe [`docs/front-matter-template-design.md`](docs/front-matter-template-design.md) und [`examples/templates/03-letter-inheritance.php`](examples/templates/03-letter-inheritance.php). Dieser Bereich ist noch Entwurf und noch keine produktive API. Die bestehenden Letter-Klassen bleiben bis zur Implementierung dieser Pipeline bestehen.

## Ausfüllbare Formulare und Signaturfelder

Jeder Dokumenttyp kann interaktive PDF-Formulare erhalten. `form()` liefert eine Convenience-API für Textfelder, Checkboxen, Auswahlfelder und digitale Signaturfelder. Die erzeugten AcroForm-Werte können in kompatiblen PDF-Viewern ausgefüllt, gespeichert und mit der gespeicherten PDF-Datei zurückgesendet werden.

```php
$document->form()
    ->text('full_name', x: 25, y: 80, width: 90, label: 'Name')
    ->checkbox('confirmed', x: 25, y: 110, label: 'Angaben geprüft')
    ->signature('signature', x: 25, y: 140, width: 80, height: 25, label: 'Digital unterschreiben');
```

Das Signaturfeld ist ein echtes PDF-Signatur-Widget (`/FT /Sig`) für eine digitale, typischerweise zertifikatsbasierte PDF-Signatur. Freihändiges Zeichnen mit Maus oder Finger ist dagegen eine Funktion des jeweiligen PDF-Viewers.

Siehe [`examples/forms/01-fill-and-sign.php`](examples/forms/01-fill-and-sign.php).

## Demo- und Referenz-PDFs

`test/DemoPdfTest.php` erzeugt bei jedem PHPUnit-Lauf feste Demo-Dokumente unter `demo/generated/`. Die GitHub-Action lädt diese Dateien zusätzlich als Artefakt `demo-pdfs` hoch, sodass die Ausgabe eines konkreten Commits direkt angesehen werden kann.

Unter `demo/reference/` liegen bewusst geprüfte Referenz-PDFs als Golden Master. Diese Dateien werden nicht automatisch überschrieben: Nach einer absichtlichen Rendering-Änderung werden die neu erzeugten PDFs visuell geprüft und erst dann als neue Referenz eingecheckt. Dadurch bleibt die visuelle Entwicklung zusätzlich über die Git-Historie nachvollziehbar.

## Ressourcen-Sicherheit

tc-lib-pdf erhält weder Internetzugriff noch direkten Zugriff auf Dokumentressourcen im Dateisystem. Dateireferenzen werden von der vertrauenswürdigen Config-/Template-Ladeschicht gelesen und in interne Ressourcen umgewandelt. Im eigentlichen Renderer bleiben Bilder und Fonts alias- beziehungsweise datenbasiert.

Bilder können als Bytes, Data-URL oder über einen anwendungseigenen Callback registriert werden. Im Markdown werden Bilder mit `image:<alias>` referenziert.

Fonts werden über `FontSource::builtIn()` beziehungsweise Config-Werte wie `builtin://helvetica` auf isoliert nutzbare PDF-Core-Fonts abgebildet.

## Struktur

- `Lack\PdfDoc\SimpleDocument` – neutrales Markdown-/HTML-Dokument.
- `Lack\PdfDoc\Letter\LetterConfig` / `LetterDocument` – aktuelle produktive Letter-API während der Migration.
- `Lack\PdfDoc\Template\TemplateDocument` – Zielentwurf für die generische Template-Pipeline.
- `Lack\PdfDoc\Form\InteractiveForm` – ausfüllbare Formular- und digitale Signaturfelder.
- `Lack\PdfDoc\Resource\ImageSource` / `FontSource` – explizit registrierte Ressourcen.
- `Lack\PdfDoc\Core` – interne Rendering-Infrastruktur.
