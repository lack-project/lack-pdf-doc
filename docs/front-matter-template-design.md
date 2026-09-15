# Front-Matter + Dokument-Templates

Status: Implementiert über `Document`, `TemplateDocument`, `Fragment` und `TemplateContext`.

## Rollen und Abhängigkeiten

Das Dateimodell kennt drei Rollen. Jede neue Datei trägt ihren Typ explizit im Front Matter, und die Abhängigkeit läuft ausschließlich in eine Richtung:

```text
document -> template -> fragment
```

### `type: document`

Ein Dokument ist eine konkrete fachliche Instanz, zum Beispiel ein Brief oder eine Rechnung. Es enthält genau einen Template-Verweis, Metadaten und optional einen Markdown-Body. Ein Dokument definiert keine Seitenpositionen und importiert keine Fragmente.

```yaml
---
type: document
template: file://letter.template.html
recipient:
  name: Erika Mustermann
showTerms: false
---

# Betreff

Normaler Markdown-Inhalt.
```

Geladen wird es direkt als konkretes Dokument:

```php
$document = Document::fromFile(__DIR__ . '/letter.md', safe: true);
```

`Document::template()` liefert das zugrunde liegende `TemplateDocument`. Nach dem Laden können Metadaten und Markdown-Hauptinhalt mit `metadata()` und `markdown()` geändert werden.

### `type: template`

Ein Template definiert den Aufbau eines Dokumenttyps. Es kann von einem Parent-Template erben, Haupt-Content-Bereiche festlegen und Fragmente importieren. Es enthält keine konkrete Rechnungsnummer oder Empfängerinstanz.

```yaml
---
type: template
content:
  first:
    x: 20mm
    y: 82mm
    width: 170mm
    bottom: 34mm
  following:
    x: 20mm
    y: 24mm
    width: 170mm
    bottom: 15mm
fragments:
  - file://fragments/letterhead-first.md
  - file://fragments/logo-following.md
  - file://fragments/footer-first.md
  - file://fragments/terms.md
---
{{ content }}
```

Ein Template wird unabhängig geladen und kann mehrere Dokumente erzeugen:

```php
$template = TemplateDocument::fromFile(__DIR__ . '/invoice.template.html', safe: true);

$invoice = $template->createDocument()
    ->metadata($invoiceData)
    ->markdown($optionalContent);
```

### `type: fragment`

Ein Fragment ist ein wiederverwendbarer Inhaltsbaustein. Es kennt kein Dokument, wählt kein Template und importiert keine weiteren Fragmente. `Fragment::fromFile()` lädt ausschließlich Dateien mit `type: fragment`.

Ein positioniertes Fragment kann Briefkopf, Logo oder Footer enthalten:

```yaml
---
type: fragment
format: html
pages: first
position:
  x: 120mm
  y: 12mm
  width: 70mm
  height: 55mm
---
<div style="text-align:right;">
  {{ image:meta.company.logo }}
  {{ meta.company.name }}
</div>
```

`pages` unterstützt `first`, `following` und `all`. `width` und `height` sind für positionierte Fragmente verpflichtend, damit `tc-lib-pdf` sie als begrenzte absolute `getHTMLCell()`-Box behandelt.

Ein Fragment ohne `position` kann zusätzlichen Flow-Content liefern, zum Beispiel AGB:

```yaml
---
type: fragment
placement: after
if: meta.showTerms
pageBreakBefore: true
format: markdown
---
# Allgemeine Geschäftsbedingungen

...
```

`if` referenziert derzeit bewusst nur einen `meta.*`-Pfad. Das Fragment wird nur angezeigt, wenn der endgültige Wert exakt der boolesche Wert `true` ist.

## Haupt-Content-Bereich

`following` bildet den normalen Seitenfluss. Die `first`-Box muss innerhalb dieses Bereichs liegen. Auf Seite 1 reserviert der Renderer zusätzliche Kopf-/Fußbereiche mit page-spezifischen No-Write-Regions; automatisch erzeugte Folgeseiten besitzen diese Reservierung nicht und nutzen den größeren `following`-Bereich.

## Platzhalter

Template- und Fragmentinhalte verwenden dieselben Platzhalter:

- `{{ meta.* }}` — skalarer Wert, HTML-escaped.
- `{{ markdown:meta.* }}` — Metadatenwert als Markdown.
- `{{ image:meta.* }}` — Bildressource.
- `{{ render:name }}` — registrierter PHP-Renderer.
- `{{ content }}` — Markdown-Hauptinhalt des konkreten Dokuments.
- `{{ template }}` — nur für Template-Vererbung.

## Lade-API

Die Typen bilden die Dateien direkt ab:

```text
Document::fromFile()         -> type: document
TemplateDocument::fromFile() -> type: template
Fragment::fromFile()         -> type: fragment
```

Für bestehende Aufrufer bleibt `TemplateDocument::fromTemplateFile(...)->fromMarkdownFile(...)` als Legacy-Einstieg erhalten. Neue Implementierungen sollen die typisierte `fromFile()`-API verwenden.

## Beispielaufbau

`examples/templates/04-letter-page-fragments.php` zeigt ein vollständiges Dokument über `Document::fromFile()`. `examples/templates/05-invoice-page-fragments.php` lädt dagegen nur das Rechnungstemplate und erzeugt mit `createDocument()` eine programmatisch befüllte Rechnung.

## Safe-Modus und Typprüfung

`Document::fromFile()` verlangt `type: document`, `TemplateDocument::fromFile()` verlangt `type: template`, und `Fragment::fromFile()` verlangt `type: fragment`. Template-Vererbung, Dokument-Template-Referenzen, Fragmentimporte und relative `file://`-Bildressourcen werden nur bei `safe: true` aufgelöst. Dateifehler bleiben an der Dateisystem-Abstraktionsgrenze und enthalten den tatsächlich verwendeten Dateipfad.
