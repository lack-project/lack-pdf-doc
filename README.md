# lack-pdf-doc

PHP 8.5 library for generating styled PDF documents from Markdown without Pandoc or LaTeX. Markdown is converted with `phore/markdown`; PDF output is rendered by `tecnickcom/tc-lib-pdf`.

## Letterhead

```php
<?php

use Lack\PdfDoc\Document;
use Lack\PdfDoc\PdfRenderer;

$document = (new Document('letterhead'))
    ->with('logo', __DIR__ . '/logo.svg')
    ->with('sender', 'Example GmbH · Example Street 1 · 45130 Essen')
    ->with('address', "Jane Doe\nCustomer Street 2\n45131 Essen")
    ->markdown("# Your report\n\nThe rest of the document is **Markdown**.");

$pdf = (new PdfRenderer())->render($document);
file_put_contents('document.pdf', $pdf);
```

Templates live below `templates/` and consist of `document.html` and `document.css`. The `default` template is neutral. The `letterhead` template reserves the first-page header area, places a logo at the upper right, and provides `sender` and `address` placeholders before the Markdown body.
