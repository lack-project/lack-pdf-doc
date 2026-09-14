# Entwurf: Front-Matter + HTML-Template

Status: Entwurf, noch nicht Teil der produktiven API.

## Ziel

Ein `LetterDocument` soll ein HTML-Template anwenden können. Eingabedaten kommen typischerweise aus einer Markdown-Datei mit YAML-Front-Matter. Der YAML-Header liefert strukturierte Metadaten; der Markdown-Body ist der Hauptinhalt.

Als Referenz dient `phore/phore-filesystem`: `PhoreFile::get_front_matter()` trennt Jekyll-Front-Matter in `header` und `content` und kann den Header optional hydratisieren.

## Platzhalter

Der Entwurf unterscheidet absichtlich drei Arten von Platzhaltern:

- `{{ meta.candidate.firstName }}`: skalarer Wert aus dem Front-Matter; HTML-escaped.
- `{{ markdown:meta.salutation }}`: Front-Matter-Wert wird als Markdown gerendert.
- `{{ content }}`: der Markdown-Hauptinhalt der Datei wird gerendert.
- `{{ render:candidateTable }}`: expliziter Renderer-Callback erzeugt HTML für komplexe Strukturen wie Tabellen.

Renderer-Callbacks sind bewusst durch `render:` von normalen Datenfeldern getrennt. Ein fehlender Renderer oder ein fehlender Pflichtwert soll eine Exception auslösen statt leeres HTML zu erzeugen.

## Vorgeschlagener Ablauf

```php
$source = phore_file(__DIR__ . '/candidate-dossier.md')->get_front_matter();

$template = HtmlDocumentTemplate::fromFile(__DIR__ . '/candidate-dossier.template.html')
    ->renderer('candidateTable', function (TemplateContext $context): string {
        $candidate = $context->meta('candidate');

        return HtmlTable::fromRows([
            ['Name', $candidate['lastName']],
            ['Vorname', $candidate['firstName']],
            ['Wohnort', $candidate['city']],
            ['Geburtsdatum', $candidate['birthDate']],
        ]);
    });

$letter = (new LetterDocument($config))
    ->applyTemplate($template)
    ->metadata($source->header)
    ->markdown($source->content);

$pdf = $letter->toPdf();
```

Die Namen `HtmlDocumentTemplate`, `TemplateContext`, `HtmlTable`, `applyTemplate()` und `metadata()` sind Teil dieses Entwurfs und noch nicht implementiert.

## Warum HTML-Template statt Blockliste

Die bestehende `LetterTemplate`-Blockliste ist gut für vollständig maschinell erzeugte Dokumente. Für gestaltete Dossiers ist ein HTML-Template flexibler: Tabellenstruktur, Klassen und Reihenfolge sind direkt sichtbar, während Daten und Freitext getrennt bleiben.

Die beiden Modelle können später nebeneinander bestehen. Die HTML-Template-Schicht sollte intern denselben sicheren Ressourcenresolver und denselben Markdown-Renderer verwenden.

## Beispielstruktur

Das Beispiel `candidate-dossier.template.html` enthält zuerst eine Grußformel aus Markdown, danach die per Callback gerenderte Stammdatentabelle, dann `{{ content }}` als Haupttext und abschließend einen festen Beurteilungsblock mit Metadaten.

Die zugehörige `candidate-dossier.md` enthält nur maschinenlesbare Front-Matter-Daten plus den eigentlichen Markdown-Hauptinhalt. Dadurch kann ein anderes System die Datei ohne Kenntnis des HTML-Layouts erzeugen.

## Sicherheits- und Rendering-Regeln

Skalare Metadaten werden standardmäßig escaped. Rohes HTML darf nicht über normale Metadaten-Platzhalter eingeschleust werden. HTML entsteht nur aus dem kontrollierten Template, aus Markdown-Rendering oder aus registrierten Renderer-Callbacks. Bilder und andere Ressourcen bleiben aliasbasiert und laufen über die bestehende Ressourcenauflösung.
