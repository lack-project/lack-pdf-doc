# Entwurf: erbbares Front-Matter + HTML-Template

Status: Entwurf, noch nicht Teil der produktiven API.

## Ziel

Ein Template soll selbst eine Datei mit YAML-Front-Matter sein. Der Header beschreibt, von welcher Letter-Konfiguration beziehungsweise welchem Basistemplate es erbt und welche Werte es überschreibt; der Body der Template-Datei enthält das eigentliche HTML-Layout mit Platzhaltern.

Für konkrete Dokumentdaten wird weiterhin eine Markdown-Datei mit YAML-Front-Matter verwendet. Dafür wird direkt `phore/filesystem` verwendet: `PhoreFile::get_front_matter()` liefert `header` und `content`. Der Header enthält strukturierte Dokumentdaten, der restliche Markdown-Body ist der Hauptinhalt.

Langfristig kann der heutige spezielle `LetterDocument` dadurch zu einem vordefinierten Template werden oder als Kompatibilitäts-Wrapper auf derselben Template-Engine aufsetzen. Briefkopf, Dossier und andere Dokumenttypen würden dann dieselbe Rendering-Pipeline verwenden.

## Template-Front-Matter

Beispiel:

```yaml
---
extends: file://../letter/letter.yaml
config:
  layout:
    pageLeft: 22mm
    pageRight: 18mm
  variables:
    documentType: Bewerberdossier
---
```

`extends` liefert die Basis-Konfiguration. `config` überschreibt einzelne Werte dieser Basis. Die endgültige Reihenfolge ist:

1. geerbte Basis-Konfiguration,
2. `config` aus dem Template-Front-Matter,
3. programmatische `configOverrides` beim Laden des Templates.

Damit kann eine allgemein gepflegte Firmenkonfiguration weiterverwendet und pro Dokumenttyp oder pro Aufruf gezielt angepasst werden.

## Safe-Modus und Dateizugriffe

Der Entwurf sieht `TemplateDocument::fromTemplateFile()` standardmäßig mit `safe: false` vor. Das Template selbst darf gelesen und sein Front Matter geparst werden, aber `extends: file://...` und andere relative Dateireferenzen aus dem Template werden nicht automatisch aufgelöst.

Im Standardmodus muss die Anwendung benötigte Konfiguration beziehungsweise Ressourcen explizit laden und an das Template übergeben. Dadurch kann ein fremdes Template nicht allein durch seinen Header weitere Dateien aus seinem Verzeichnis einlesen.

Mit `safe: true` erklärt die Anwendung die Template-Datei und deren Verzeichnis ausdrücklich für vertrauenswürdig. Dann dürfen relative Referenzen wie `file://../letter/letter.yaml` oder `file://assets/logo.png` relativ zur Template-Datei aufgelöst werden. Für Ressourcen gelten anschließend dieselben Regeln wie bei `LetterConfig::fromFile()`.

Konzeptionell:

```php
$template = TemplateDocument::fromTemplateFile(
    __DIR__ . '/candidate-dossier.template.html',
    safe: true,
    configOverrides: [
        'layout' => ['pageLeft' => '24mm'],
    ],
);
```

Ohne `safe: true` wäre stattdessen ein explizites Laden vorgesehen, zum Beispiel über eine noch zu definierende `loadConfig()`-API. Diese API ist Teil des Entwurfs und noch nicht implementiert.

## Platzhalter

Der Entwurf unterscheidet vier Platzhalterarten:

- `{{ meta.candidate.firstName }}`: skalarer Wert aus dem Dokument-Front-Matter; HTML-escaped.
- `{{ markdown:meta.salutation }}`: Wert aus dem Dokument-Front-Matter wird als Markdown gerendert.
- `{{ content }}`: der Markdown-Hauptinhalt der Dokumentdatei wird gerendert.
- `{{ render:candidateTable }}`: registrierter Renderer-Callback erzeugt kontrolliertes HTML für komplexe Strukturen wie Tabellen.

Renderer-Callbacks sind bewusst durch `render:` von normalen Datenwerten getrennt. Ein fehlender Renderer oder ein fehlender Pflichtwert soll eine Exception auslösen statt still leeres HTML zu erzeugen.

## Vorgeschlagener Ablauf

```php
$source = phore_file(__DIR__ . '/candidate-dossier.md')->get_front_matter();

$document = TemplateDocument::fromTemplateFile(
    __DIR__ . '/candidate-dossier.template.html',
    safe: true,
    configOverrides: [
        'variables' => ['preparedBy' => 'Recruiting Team'],
    ],
)
    ->renderer('candidateTable', function (TemplateContext $context): string {
        $candidate = $context->meta('candidate');

        return HtmlTable::fromRows([
            ['Name', $candidate['lastName']],
            ['Vorname', $candidate['firstName']],
            ['Wohnort', $candidate['city']],
            ['Geburtsdatum', $candidate['birthDate']],
        ]);
    })
    ->metadata((array) $source->header)
    ->markdown($source->content);

$pdf = $document->toPdf();
```

Die Namen `TemplateDocument`, `TemplateContext`, `HtmlTable`, `fromTemplateFile()`, `loadConfig()` und `metadata()` sind Teil dieses Entwurfs und noch nicht implementiert.

## Rolle von LetterConfig und LetterDocument

`LetterConfig` bleibt zunächst das Datenmodell für Briefpapier-Konfiguration und die bereits vorhandene `fromFile()`-Logik. Die Template-Engine kann diese Config aus `extends` laden und anschließend mit Template- und Laufzeit-Overrides zusammenführen.

Langfristig sollte die spezielle `LetterDocument`-Klasse keine eigene Rendering-Welt bilden. Ein Standardbrief kann selbst als mitgeliefertes Front-Matter-/HTML-Template modelliert werden. `LetterDocument` könnte dann entfallen oder nur noch eine Convenience-Fassade für dieses Standardtemplate sein.

## Sicherheits- und Rendering-Regeln

Skalare Metadaten werden standardmäßig escaped. Rohes HTML darf nicht über normale Metadaten-Platzhalter eingeschleust werden. HTML entsteht nur aus dem kontrollierten Template, aus Markdown-Rendering oder aus registrierten Renderer-Callbacks.

Dateizugriffe aus Template-Front-Matter werden nur bei explizitem `safe: true` automatisch relativ zum Template aufgelöst. Im Standardmodus müssen Config und Ressourcen explizit von der Anwendung geladen werden. tc-lib-pdf selbst erhält weiterhin keine frei auflösbaren Datei- oder Netzwerkpfade.
