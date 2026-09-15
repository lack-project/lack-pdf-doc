# Front-Matter + Dokument-Templates

Status: Implementiert über `TemplateDocument` und `TemplateContext`.

## Begriffe

Das Dateimodell kennt drei Rollen. Jede neue Datei trägt ihren Typ explizit im Front Matter.

### `type: document`

Ein Dokument ist eine konkrete fachliche Instanz, zum Beispiel ein Brief oder eine Rechnung. Es enthält den Verweis auf genau ein Template, Metadaten und optional einen Markdown-Body. Ein Dokument definiert keine Seitenpositionen und importiert keine Fragmente.

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

Verwenden für konkrete Ausgabedokumente. Nicht verwenden, um wiederverwendbares Layout oder gemeinsame Inhalte abzulegen.

### `type: template`

Ein Template definiert den Aufbau eines Dokumenttyps. Es kann von einem Parent-Template erben, die Haupt-Content-Bereiche festlegen und Fragmente importieren. Das Template ist die einzige Ebene, die die Abhängigkeiten zwischen Dokumentaufbau und Fragmenten orchestriert.

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

Verwenden für Layout, Seitenaufbau und die Zusammenstellung wiederverwendbarer Fragmente. Nicht verwenden für konkrete Rechnungsnummern, Empfänger oder andere Instanzdaten.

### `type: fragment`

Ein Fragment ist ein wiederverwendbarer Inhaltsbaustein. Es kennt kein Dokument und wählt kein Template. Fragmente importieren keine weiteren Fragmente. Dadurch bleibt die Abhängigkeit immer eindeutig:

```text
document -> template -> fragment
```

Ein positioniertes Fragment kann beispielsweise Briefkopf, Logo oder Footer enthalten:

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

`if` referenziert derzeit bewusst nur einen `meta.*`-Pfad. Das Fragment wird nur angezeigt, wenn der endgültige Wert exakt der boolesche Wert `true` ist. Dadurch kann die Anwendung optionale Inhalte nach dem Laden mit `metadata(['showTerms' => true])` aktivieren.

## Haupt-Content-Bereich

`following` bildet den normalen Seitenfluss. Die `first`-Box muss innerhalb dieses Bereichs liegen. Auf Seite 1 reserviert der Renderer zusätzliche Kopf-/Fußbereiche mit page-spezifischen No-Write-Regions; automatisch erzeugte Folgeseiten besitzen diese Reservierung nicht und nutzen den größeren `following`-Bereich.

## Platzhalter

Template- und Fragmentinhalte verwenden dieselben Platzhalter:

- `{{ meta.* }}` — skalarer Wert, HTML-escaped.
- `{{ markdown:meta.* }}` — Metadatenwert als Markdown.
- `{{ image:meta.* }}` — Bildressource.
- `{{ render:name }}` — registrierter PHP-Renderer.
- `{{ content }}` — nur im Dokument-Template; rendert den Markdown-Body des Dokuments.
- `{{ template }}` — nur für Template-Vererbung.

Damit kann komplexer fachlicher Inhalt über `{{ render:name }}` erzeugt werden, während wiederverwendbare feste oder nachgelagerte Inhalte als Fragmente organisiert bleiben.

## Beispielaufbau

`examples/templates/page-fragments/business.template.html` enthält gemeinsame Firmen-Defaults, Content-Geometrie und Fragmente. `letter.template.html` und `invoice.template.html` erben davon. Der Brief liefert normalen Markdown-Content; die Rechnung rendert ihre fachlichen Felder direkt aus Metadaten und funktioniert auch mit leerem Markdown-Body.

## Safe-Modus und Typprüfung

`TemplateDocument::fromDocumentFile()` verlangt `type: document`. Über `fragments:` importierte Dateien müssen `type: fragment` tragen. Templates des neuen Modells tragen `type: template`; alte Templates ohne Typ bleiben für den bisherigen `fromTemplateFile()`-Ablauf kompatibel.

Template-Vererbung, Dokument-Template-Referenzen, Fragmentimporte und relative `file://`-Bildressourcen werden nur bei `safe: true` aufgelöst. Dateifehler bleiben an der Dateisystem-Abstraktionsgrenze und enthalten den tatsächlich verwendeten Dateipfad.
