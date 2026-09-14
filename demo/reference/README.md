# PDF reference files

This directory contains reviewed PDF snapshots (golden masters) that document the expected visual output of the library.

`test/DemoPdfTest.php` regenerates the current PDFs into `demo/generated/` on every test run. GitHub Actions uploads those generated files as the `demo-pdfs` artifact.

Reference PDFs are updated deliberately after visual review. They are not overwritten automatically by PHPUnit, so a rendering regression remains visible in Git history instead of silently replacing the baseline.
