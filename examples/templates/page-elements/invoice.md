---
template: file://invoice.template.html
customer:
  name: Musterkunde GmbH
  street: Kundenstraße 8
  postalCode: "45128"
  city: Essen
invoice:
  number: RE-2026-0042
  date: 15.09.2026
  servicePeriod: September 2026
  items: |
    | Leistung | Menge | Einzelpreis | Gesamt |
    |---|---:|---:|---:|
    | Beratung | 4 h | 120,00 € | 480,00 € |
    | Dokumentation | 1 | 180,00 € | 180,00 € |
  net: 660,00 €
  taxRate: 19 %
  tax: 125,40 €
  total: 785,40 €
  dueDate: 29.09.2026
showTerms: false
---
