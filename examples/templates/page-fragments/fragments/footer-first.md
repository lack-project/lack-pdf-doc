---
type: fragment
format: html
pages: first
position:
  x: 20mm
  y: 268mm
  width: 170mm
  height: 20mm
---
<table style="width:100%; font-size:7.5pt; line-height:1.25;">
  <tr>
    <td style="width:33%; vertical-align:top;">
      <strong>{{ meta.company.name }}</strong><br>
      {{ meta.company.address.street }}<br>
      {{ meta.company.address.postalCode }} {{ meta.company.address.city }}
    </td>
    <td style="width:34%; vertical-align:top;">
      <strong>Bankverbindung</strong><br>
      {{ meta.company.bank.name }}<br>
      IBAN {{ meta.company.bank.iban }}<br>
      BIC {{ meta.company.bank.bic }}
    </td>
    <td style="width:33%; vertical-align:top;">
      <strong>Kontakt</strong><br>
      {{ meta.company.contact.email }}<br>
      {{ meta.company.contact.phone }}
    </td>
  </tr>
</table>
