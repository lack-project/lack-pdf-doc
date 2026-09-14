# PDF reference files

This directory contains versioned PDF snapshots (golden masters) that document the expected visual output of the library.

`test/DemoPdfTest.php` regenerates the current PDFs into `demo/generated/` on every test run. GitHub Actions uploads those generated files as the `demo-pdfs` artifact.

The initial snapshots come from a successful CI run. Reference PDFs are never overwritten automatically by PHPUnit. Future replacements are made deliberately after visual review, so a rendering regression remains visible in Git history instead of silently replacing the baseline.
