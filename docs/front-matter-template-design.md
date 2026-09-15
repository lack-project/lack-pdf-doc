# Front-Matter + HTML-Templates

Status: Implementiert über `TemplateDocument` und `TemplateContext`.

## Zielbild

Die Template-Pipeline verwendet `TemplateDocument` als allgemeinen Dokumenttyp. Ein Brief, Bewerberdossier oder anderes Dokument unterscheidet sich durch Template und Daten. Die bestehende Letter-API bleibt parallel bestehen und wird durch diese Pipeline nicht entfernt.

Sowohl Templates als auch konkrete Dokumente können YAML-Front-Matter besitzen. `TemplateDocument` liest die Datei selbst, trennt Front Matter vom Body und dekodiert den YAML-Header mit `phore_yaml_decode()`. Dateien ohne Front Matter werden als reiner Body akzeptiert.

- Beim Template ist `header` die Template-/Config-Metadatenebene und `content` der HTML-Template-Body.
- Beim konkreten Dokument ist `header` die Dokument-Metadatenebene und `content` der Markdown-Hauptinhalt.

## Template-Vererbung

Templates können mit `extends` von einem anderen Template erben. Der Child-HTML-Body wird im Parent an `{{ template }}` eingesetzt. `{{ content }}` bleibt ausschließlich dem Markdown-Hauptinhalt des konkreten Dokuments vorbehalten.

Damit lässt sich eine Kette aufbauen:

1. `letter-defaults.template.html` — Firmenstandardwerte, Standardlogo, Fonts und Grundlayout.
2. `letterhead.template.html` — erbt die Defaults und definiert Briefkopf/Footer.
3. `letter.template.html` — erbt den Briefkopf und definiert Empfängerblock, Betreff, Grußformel, Hauptinhalt und Abschluss.
4. `letter.md` — konkrete Dokumentmetadaten + Markdown-Hauptinhalt.

## Front-Matter-Struktur

Beispiel für Firmen-Defaults:

```yaml
---
defaults:
  company:
    name: Example GmbH
    logo: file://../letter/assets/company-logo.png
    address:
      street: Musterstraße 1
      postalCode: "45130"
      city: Essen

config:
  fonts:
    body: builtin://helvetica
  layout:
    bodyFont: body
---
{{ template }}
```

Ein erbendes Briefkopf-Template braucht dann nur noch:

```yaml
---
extends: file://letter-defaults.template.html
---
<header>
  {{ image:meta.company.logo }}
  {{ meta.company.name }}
</header>

{{ template }}
```

## Merge- und Override-Reihenfolge

Metadaten und Config werden rekursiv zusammengeführt. Spätere Ebenen gewinnen:

1. Defaults des ältesten Parent-Templates,
2. Defaults/Config der erbenden Child-Templates,
3. Front Matter des konkreten Markdown-Dokuments,
4. programmatische `metadataOverrides` / `configOverrides` beim Laden.

Dadurch kann ein konkretes Dokument gezielt nur einen einzelnen Wert überschreiben. Beispiel: Das Standardlogo kommt aus `letter-defaults.template.html`; ein einzelner Brief überschreibt nur:

```yaml
---
company:
  logo: data:image/png;base64,...
---
```

Der restliche Briefkopf bleibt unverändert geerbt.

## Platzhalter

Die Pipeline unterstützt folgende Platzhalter:

- `{{ meta.candidate.firstName }}` — skalarer Metadatenwert, HTML-escaped.
- `{{ markdown:meta.salutation }}` — Metadatenwert wird als Markdown gerendert.
- `{{ image:meta.company.logo }}` — Bildressource aus einem Metadatenpfad; Data-URL, `file://` im Safe-Modus oder registrierter `image:`-Alias.
- `{{ template }}` — HTML-Body des erbenden Child-Templates.
- `{{ content }}` — gerenderter Markdown-Hauptinhalt des konkreten Dokuments.
- `{{ render:candidateTable }}` — registrierter Renderer-Callback für komplexes HTML wie Tabellen.

Ein fehlender Pflichtwert, Renderer oder Parent löst eine Exception aus statt still leeres HTML zu erzeugen.

## Standardablauf

```php
$document = TemplateDocument::fromTemplateFile(
    __DIR__ . '/letter.template.html',
    safe: true,
)
    ->fromMarkdownFile(__DIR__ . '/letter.md');

$pdf = $document->toPdf();
```

`fromMarkdownFile()` liest und validiert die Datei, dekodiert den optionalen YAML-Header und übernimmt den Body als Markdown-Hauptinhalt. Dateibezogene Fehler enthalten den betroffenen Dateinamen.

Ein Dokument mit abweichendem Logo braucht keinen anderen PHP-Code. Es verwendet dieselbe Template-Kette und überschreibt `company.logo` nur in seinem Front Matter.

## Renderer-Callbacks

Komplexe Darstellungen bleiben explizit registriert:

```php
$document->renderer('candidateTable', function (TemplateContext $context): string {
    $candidate = $context->meta('candidate');

    return HtmlTable::fromRows([
        ['Name', $candidate['lastName']],
        ['Vorname', $candidate['firstName']],
        ['Wohnort', $candidate['city']],
        ['Geburtsdatum', $candidate['birthDate']],
    ]);
});
```

Callbacks sind durch `render:` klar von normalen Datenwerten getrennt.

## Safe-Modus und Dateizugriffe

`TemplateDocument::fromTemplateFile()` ist standardmäßig `safe: false`.

Im Standardmodus wird die angegebene Template-Datei gelesen und geparst, aber `extends` und `file://`-Bildressourcen werden nicht automatisch geöffnet.

Mit `safe: true` erklärt die Anwendung die Template-Kette als vertrauenswürdig. Dann dürfen relative Referenzen wie `file://letter-defaults.template.html` oder `file://../letter/assets/company-logo.png` relativ zur jeweils referenzierenden Datei aufgelöst werden. `file:///...` bleibt ein absoluter Pfad.

Dateinahe Fehler nennen den tatsächlich verwendeten Dateipfad in der Exception. Die Auflösung geschieht ausschließlich in der Template-Ladeschicht; tc-lib-pdf selbst erhält weiterhin keine frei auflösbaren Datei- oder Netzwerkpfade.

## Rolle der bisherigen Letter-Klassen

Die generische Template-Pipeline ergänzt die vorhandene `LetterDocument`-API. `LetterConfig`, `LetterLayout`, `LetterFooter` und `LetterDocument` bleiben bestehen; eine spätere Migration oder Entfernung ist nicht Bestandteil dieser Implementierung.

## Sicherheits- und Rendering-Regeln

Skalare Metadaten werden standardmäßig escaped. Rohes HTML darf nicht über normale Metadaten-Platzhalter eingeschleust werden. HTML entsteht nur aus den kontrollierten Template-Bodies, Markdown-Rendering oder registrierten Renderer-Callbacks.

Dateizugriffe aus Template-Front-Matter werden nur bei explizitem `safe: true` automatisch relativ aufgelöst. Bildressourcen werden beim Rendern in interne `ImageSource`-Ressourcen überführt; der PDF-Renderer selbst bleibt vom Dateisystem und Netzwerk isoliert.
