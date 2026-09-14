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

`LetterConfig` beschreibt das wiederverwendbare Briefpapier. Der typische Einstieg lädt eine JSON- oder YAML-Datei mit `LetterConfig::fromFile()`; `fromArray()` bleibt für bereits vorliegende Arrays verfügbar.

```php
$config = \Lack\PdfDoc\Letter\LetterConfig::fromFile(__DIR__ . '/letter.yaml');

$letter = (new \Lack\PdfDoc\Letter\LetterDocument($config))
    ->recipientAddress("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->variables(['customerNumber' => '12345'])
    ->markdown($markdown);

$pdf = $letter->toPdf();
```

JSON wird immer unterstützt. YAML/YML wird nur unterstützt, wenn die optionale PHP-YAML-Extension installiert ist; andernfalls wirft `fromFile()` für YAML eine klare Exception und JSON bleibt vollständig nutzbar.

Ressourcen können direkt in der Config angegeben werden. `file://assets/company-logo.png` ist relativ zur geladenen YAML-/JSON-Datei. `file:///opt/company/company-logo.png` ist ein absoluter Pfad. Eine `data:image/...`-URL kann direkt eingebettet werden. `fromArray()` lehnt `file://` ab, weil dort bewusst kein Basisverzeichnis bekannt ist. Die Datei wird beim Config-Laden gelesen; tc-lib-pdf selbst erhält den Pfad nicht.

Fonts werden in derselben Config unter Aliasnamen festgelegt, zum Beispiel `body: builtin://helvetica`. Das Layout referenziert anschließend nur den Alias `body`. Eigene TTF/OTF-Dateien sind im isolierten Renderer derzeit bewusst nicht freigegeben, weil der aktuelle tc-lib-pdf-font-Importer echte Datei-I/O und erzeugte Font-Dateien benötigt.

Siehe [`examples/letter/01-basic.php`](examples/letter/01-basic.php) und die kopierbare [`examples/letter/letter.yaml`](examples/letter/letter.yaml).

## Datengetriebene Brief-Templates

`LetterTemplate` legt einen wiederverwendbaren Dokumentaufbau fest, ohne konkrete Bewerber- oder Vorgangsdaten einzubauen. Unterstützt werden Tabellen aus strukturierten Daten, ein optionales Bild pro Tabellenblock, benannte Markdown-Freitext-Slots und feste Textblöcke mit `{{daten.pfad}}`-Platzhaltern.

```php
$template = \Lack\PdfDoc\Template\LetterTemplate::fromArray([
    'sections' => [
        [
            'type' => 'table',
            'title' => 'Bewerberprofil',
            'image' => 'candidate.photo',
            'rows' => [
                ['label' => 'Nachname', 'value' => 'candidate.lastName'],
                ['label' => 'Vorname', 'value' => 'candidate.firstName'],
                ['label' => 'Wohnort', 'value' => 'candidate.city'],
            ],
        ],
        ['type' => 'markdown', 'title' => 'Profil', 'slot' => 'profile'],
        ['type' => 'fixed', 'text' => 'Referenz: {{application.reference}}'],
    ],
]);

$dossier = $template->document($config)
    ->data($structuredData)
    ->text('profile', $profileMarkdown);

$pdf = $dossier->toPdf();
```

Datenpfade verwenden Punktnotation wie `candidate.lastName`. Ein Bildwert enthält ausschließlich einen bereits registrierten Bild-Alias; die eigentlichen Bildbytes werden separat mit `image()`, `imageBytes()` oder `imageFromCallback()` registriert.

Siehe [`examples/templates/01-candidate-dossier.php`](examples/templates/01-candidate-dossier.php).

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

## Ressourcen-Sicherheit

tc-lib-pdf erhält weder Internetzugriff noch direkten Zugriff auf Dokumentressourcen im Dateisystem. Dateireferenzen in `LetterConfig::fromFile()` werden ausschließlich von der Config-Schicht gelesen und sofort in interne Ressourcen umgewandelt. Im eigentlichen Dokument und Renderer bleiben Bilder und Fonts aliasbasiert.

Bilder können als Bytes, Data-URL oder über einen anwendungseigenen Callback registriert werden. Im Markdown werden Bilder mit `image:<alias>` referenziert, zum Beispiel `![Chart](image:chart)`.

Fonts werden über `FontSource::builtIn()` beziehungsweise Config-Werte wie `builtin://helvetica` auf isoliert nutzbare PDF-Core-Fonts abgebildet.

Siehe [`examples/letter/02-images.php`](examples/letter/02-images.php).

## Struktur

- `Lack\PdfDoc\SimpleDocument` – neutrales Markdown-/HTML-Dokument.
- `Lack\PdfDoc\Letter\LetterConfig` – wiederverwendbare Briefpapier-Konfiguration mit `fromFile()` und `fromArray()`.
- `Lack\PdfDoc\Letter\LetterDocument` – konkreter Brief.
- `Lack\PdfDoc\Letter\LetterLayout` – Maße und Typografie des Briefs.
- `Lack\PdfDoc\Letter\LetterFooter` – Firmen-, Bank-, Kontakt- und Rechtsangaben.
- `Lack\PdfDoc\Template\LetterTemplate` – deklarativer Aufbau für datengetriebene Briefe/Dossiers.
- `Lack\PdfDoc\Template\LetterTemplateDocument` – konkrete, mit Daten und Freitext gefüllte Template-Instanz.
- `Lack\PdfDoc\Form\InteractiveForm` – ausfüllbare Formular- und digitale Signaturfelder.
- `Lack\PdfDoc\Resource\ImageSource` / `FontSource` – explizit registrierte Ressourcen.
- `Lack\PdfDoc\Core` – interne Rendering-Infrastruktur.
