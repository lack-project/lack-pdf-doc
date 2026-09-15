# Front-Matter + Dokument-Templates

Status: Implementiert über `TemplateDocument` und `TemplateContext`.

## Rollen

Die Pipeline trennt drei Verantwortlichkeiten:

1. Eine Dokumentdatei (`.md`) enthält `template: file://...`, fachliche Metadaten und optionalen Markdown-Hauptinhalt. Sie enthält keine Seitenpositionen oder Styling-Konfiguration.
2. Ein Dokument-Template definiert Defaults, Vererbung, den Haupt-Content-Bereich und die importierten Elemente.
3. Ein Element (`.md`) ist entweder eine absolut positionierte Seitenbox oder zusätzlicher Flow-Content.

`TemplateDocument::fromDocumentFile()` lädt diese Kette direkt. Der bisherige Ablauf über `fromTemplateFile()` und `fromMarkdownFile()` bleibt verfügbar.

## Dokument

```yaml
---
template: file://letter.template.html
recipient:
  name: Erika Mustermann
showTerms: false
---

# Betreff

Normaler Markdown-Inhalt.
```

Darstellungsparameter gehören bewusst nicht in das konkrete Dokument. Metadaten können nach dem Laden weiterhin mit `metadata()` überschrieben oder ergänzt werden.

## Haupt-Content-Bereich

Das Template kann den Haupttext für erste und folgende Seiten unterschiedlich begrenzen:

```yaml
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
```

`following` bildet den normalen Seitenfluss. Die `first`-Box muss innerhalb dieses Bereichs liegen. Auf Seite 1 reserviert der Renderer die zusätzlichen Kopf-/Fußbereiche mit page-spezifischen No-Write-Regions; automatisch erzeugte Folgeseiten besitzen diese Reservierung nicht und nutzen dadurch den größeren `following`-Bereich.

## Elemente

Templates importieren Elemente als Dateien:

```yaml
elements:
  - file://elements/letterhead-first.md
  - file://elements/logo-following.md
  - file://elements/footer-first.md
  - file://elements/terms.md
```

Ein festes Element beschreibt seine Seite und Box:

```yaml
---
pages: first
format: html
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

`pages` unterstützt `first`, `following` und `all`. `width` und `height` sind für positionierte Elemente verpflichtend, damit `tc-lib-pdf` den Inhalt als begrenzte absolute `getHTMLCell()`-Box behandelt.

## Zusätzlicher Flow-Content und Bedingungen

Ein Element ohne `position` kann mit `placement: after` an das Ende des Hauptinhalts gehängt werden:

```yaml
---
placement: after
if: meta.showTerms
pageBreakBefore: true
format: markdown
---
# Allgemeine Geschäftsbedingungen

...
```

`if` referenziert derzeit bewusst nur einen `meta.*`-Pfad. Das Element wird nur angezeigt, wenn der endgültige Wert exakt der boolesche Wert `true` ist. Dadurch bleibt die Bedingungslogik klein und vorhersehbar und kann von außen über `metadata(['showTerms' => true])` gesteuert werden.

## Platzhalter

Template- und Elementinhalte verwenden dieselben Platzhalter:

- `{{ meta.* }}` — skalarer Wert, HTML-escaped.
- `{{ markdown:meta.* }}` — Metadatenwert als Markdown.
- `{{ image:meta.* }}` — Bildressource.
- `{{ render:name }}` — registrierter PHP-Renderer.
- `{{ content }}` — nur im Dokument-Template; rendert den Markdown-Body des Dokuments.
- `{{ template }}` — nur für Template-Vererbung.

Damit kann eine Tabelle normal im Hauptfluss über `{{ render:name }}` aus PHP erzeugt werden, während Briefkopf, Logo oder Footer als positionierte Elemente außerhalb des Flows bleiben.

## Beispielaufbau

`examples/templates/page-elements/business.template.html` enthält gemeinsame Firmen-Defaults, Content-Geometrie und Elemente. `letter.template.html` und `invoice.template.html` erben davon. Der Brief liefert normalen Markdown-Content; die Rechnung rendert ihre fachlichen Felder direkt aus Metadaten und funktioniert auch mit leerem Markdown-Body.

## Safe-Modus

Template-Vererbung, Dokument-Template-Referenzen, Elementimporte und relative `file://`-Bildressourcen werden nur bei `safe: true` aufgelöst. Dateifehler bleiben an der Dateisystem-Abstraktionsgrenze und enthalten den tatsächlich verwendeten Dateipfad.
