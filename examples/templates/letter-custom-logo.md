---
# Konkrete Dokument-Metadaten überschreiben Template-Defaults.
company:
  logo: data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=

recipient:
  name: Erika Mustermann
  street: Beispielweg 10
  postalCode: "45130"
  city: Essen

subject: Brief mit abweichendem Logo
---
dieses Dokument verwendet denselben Briefkopf und dasselbe Letter-Template, überschreibt aber ausschließlich `company.logo` im Front Matter.
