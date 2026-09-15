---
format: html
pages: first
position:
  x: 120mm
  y: 12mm
  width: 70mm
  height: 55mm
---
<style>.letterhead-first img { width: 38mm; }</style>
<div class="letterhead-first" style="text-align:right;">
  <div style="height:20mm;">{{ image:meta.company.logo }}</div>
  <strong>{{ meta.company.name }}</strong><br>
  {{ meta.company.address.street }}<br>
  {{ meta.company.address.postalCode }} {{ meta.company.address.city }}<br>
  {{ meta.company.contact.email }}<br>
  {{ meta.company.contact.phone }}
</div>
