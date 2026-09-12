# Flexible Gruppenverwaltung

Gruppen werden auf Site-Ebene gepflegt und anschließend pro Plugin-Instanz zugewiesen.

## 1. Standardgruppe (Site Configuration)

Die Standardliste liegt im Backend-Reiter **Cleverreach**:

- Standard List ID
- Bezeichnung der Standard-Listen-ID (optional)
- Standard Formular ID (Flow-UUID oder Formular-ID)

Wählt ein Plugin keine Zusatzgruppen, wird diese Standardgruppe für die Anmeldung verwendet.

## 2. Zusatzgruppen (Site-`config.yaml`)

Zusatzgruppen haben keine Backend-Oberfläche. Sie werden in der Site-`config.yaml` definiert:

```yaml
cleverreachGroups:
  -
    name: 'Politik & Gesellschaft'
    group: '1234567'
    doubleOptInMailId: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'
  -
    name: 'Kultur'
    group: '7654321'
    doubleOptInMailId: '123456'
```

| Schlüssel | Beschreibung |
|---|---|
| `name` | Anzeigename im Plugin-FlexForm und im Frontend-Select |
| `group` | CleverReach-Empfängerlisten-ID |
| `doubleOptInMailId` | Flow-UUID oder bisherige Formular-ID für Double Opt-In |

Das Feld `settings.newsletterGroups` im Plugin erscheint erst, wenn mindestens eine Zusatzgruppe konfiguriert ist.

## 3. Auswahl pro Plugin (FlexForm)

Jede Instanz von Anmeldeformular / Abmeldeformular kann festlegen, welche Gruppen gelten.

**Anmeldeformular**

- Ausgewählte Gruppen: das Formular bietet genau diese Gruppen an
- Leere Auswahl: es gilt die Standardgruppe aus der Site Configuration
- Mehr als eine Gruppe: im Frontend erscheint ein Pflicht-Select
- Erste Option **Alle Newsletter abonnieren**: Anmeldung in allen im Plugin gewählten Gruppen
- Eine konkrete Option: Anmeldung nur in dieser Gruppe
- Genau eine Gruppe: kein Select, die Gruppe wird automatisch verwendet

**Abmeldeformular**

- Ausgewählte Gruppen: das Formular bietet genau diese Gruppen an
- Leere Auswahl: alle konfigurierten Gruppen stehen zur Verfügung (Standardliste plus Zusatzgruppen)
- Mehr als eine Gruppe: im Frontend erscheint ein Pflicht-Select
- Erste Option **Alle Newsletter abbestellen**: Abmeldung aus allen vom Plugin angebotenen Gruppen
- Eine konkrete Option: Abmeldung nur aus dieser Gruppe
- Genau eine Gruppe: kein Select, die Abmeldung läuft gegen diese Gruppe

## Frontend-Verhalten

Das Select wird nur gerendert, wenn mehr als eine Gruppe wählbar ist. Das Feld-Label ist „Newsletter auswählen“. Die erste Option ist „Alle Newsletter abonnieren“ / „Alle Newsletter abbestellen“ (`value="all"`).

```html
<f:if condition="{newsletterGroupOptions -> f:count()} > 1">
    <label for="newsletter-form-group">
        <f:translate key="form.newsletterGroups.choose" />
    </label>
    <f:form.select property="newsletter-group"
                   options="{newsletterGroupOptions}"
                   prependOptionLabel="{f:translate(key: 'form.newsletterGroups.subscribe')}"
                   prependOptionValue="all"
                   required="true" />
</f:if>
```

Relevante Dateien:

```text
Classes/Utility/CleverReachGroupMapper.php
Classes/Backend/Form/NewsletterGroupsFieldConfiguration.php
Classes/Controller/CleverreachController.php
Configuration/FlexForms/flexform_subscribeform.xml
Configuration/FlexForms/flexform_unsubscribeform.xml
```
