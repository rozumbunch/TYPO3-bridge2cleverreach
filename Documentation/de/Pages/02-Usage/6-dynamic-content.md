# Dynamic Content ("My Content" für CleverReach)

Der CleverReach-Editor hat einen **My Content**-Picker, mit dem eine Redakteurin dynamische Blöcke (News, Inhaltselemente, eigene Kategorien) in einen Newsletter einfügen kann. Diese Extension beantwortet die Anfragen dieses Pickers.

## Zusammenspiel

- `plugin.tx_bridge2cleverreach.dynamicContents` (TypoScript) registriert pro Kategorie einen Eintrag: eine `type`-Kennung und einen `dataProcessor`-Callable.
- Mitgeliefert sind zwei Kategorien:
  - Index `0`, `type = content-elements`, immer aktiv. Die erlaubten CTypes kommen aus der Site-Einstellung `bridge2cleverreach.allowedCTypesForDynamicContents`.
  - Index `1`, `type = news`, nur wenn die Site-Einstellung `bridge2cleverreach.dynamicContentsAsNews` aktiv ist. Die Detailseite steht in `bridge2cleverreach.dynamicContentsNewsDetailPid`.
- `CleverreachController::getDynamicContent()` beantwortet die Anfragen auf der Endpoint-Seite (`typeNum = 1705405176`, siehe `Configuration/TypoScript/Endpoints/`). CleverReach sendet **POST**, Body `application/x-www-form-urlencoded`, an `/?type=1705405176` der jeweiligen Site:
  - `get=filter` liefert die Kategorien für das **Category**-Dropdown.
  - `get=search` und `category=<type>` ruft den `dataProcessor` des passenden Eintrags auf, der `$params['items']` befüllt.
  - Fehlt `get`, antwortet der Endpunkt mit der HTML-Tabelle `This is CleverReach default content`. Ein Aufruf im Browser ohne POST-Body ist deshalb kein JSON.
- Die Site ergibt sich aus dem Host der Anfrage. Die Auswahl im Backend gilt für die Root-Page dieser Site.

`get=filter` liest `tx_bridge2cleverreach_domain_model_dynamic_content_settings` der Root-Page. Eine Kategorie erscheint erst, nachdem im passenden Untermodul etwas gespeichert wurde. Der Typ `content-elements-source-page` ist keine eigene Kategorie. Der Typ `custom` wird durch die darin gespeicherten Typ-Kennungen ersetzt (`DynamicContentService::getDynamicContentFilters()`).

## Backend-Modul

**Web > CleverReach** zeigt eine Übersicht. Eine Seite im Seitenbaum muss ausgewählt sein; die Site wird daraus ermittelt. Die Auswahl liegt in `tx_bridge2cleverreach_domain_model_dynamic_content_settings`, `pid` ist die Root-Page-ID der Site.

| Untermodul | Verwaltet | Gespeichert unter `type` |
|---|---|---|
| Inhaltselemente | Quellseiten und einzelne `tt_content`-Datensätze | `content-elements-source-page` / `content-elements` |
| News | `georgringer/news`-Datensätze des Seitenbaums dieser Site | `news` |
| Eigene Inhalte | welche registrierten `dynamicContents`-Typen an CleverReach ausgespielt werden | `custom` |

News und Eigene Inhalte bleiben in der Übersicht sichtbar. Der Button ist deaktiviert, solange die passende Site-Einstellung aus ist: `bridge2cleverreach.dynamicContentsAsNews` beziehungsweise `bridge2cleverreach.dynamicContentsCustom`. Das News-Untermodul wird nur registriert, wenn die Extension `news` geladen ist.

### Inhaltselemente

Zwei Bereiche:

1. **Inhaltselemente aus bestimmte Seite.** Gespeichert werden Seiten-UIDs der aktuellen Site (`content-elements-source-page`). Die Suche `category=content-elements` rendert davon jedes `tt_content`, dessen CType in `bridge2cleverreach.allowedCTypesForDynamicContents` steht.
2. **Einzelne Inhaltselemente.** Gespeichert werden `tt_content`-UIDs (`content-elements`), aber nur wenn der Datensatz auf einer gespeicherten Quellseite liegt und der CType erlaubt ist. Ein nicht erlaubtes Element wird nicht hinzugefügt.

Die Suche liefert die einzeln gespeicherten Datensätze und zusätzlich alle erlaubten Inhaltselemente der Quellseiten. Dieselbe UID erscheint nur einmal. Pro Eintrag: `title` und `display_info` aus `header`, `teaser` aus `subheader`, `description` aus `bodytext`, `image` aus der ersten FAL-Relation `assets` oder `image`, `url` ist die Seite des Inhaltselements.

### News

Auswählbar sind nur News-Datensätze, deren Seite im Seitenbaum der gewählten Site liegt. Die URL zeigt auf `bridge2cleverreach.dynamicContentsNewsDetailPid`.

## Das Untermodul "Eigene Inhalte"

Anders als News und Inhaltselemente verwaltet **Eigene Inhalte** keine einzelnen Datensätze, sondern welche Typen für diese Site aktiviert sind. Jeder `dynamicContents`-Eintrag außer `news` und `content-elements` erscheint in der verfügbaren Liste. Erst das Verschieben eines Typs in die Auswahl und Speichern sorgt dafür, dass er im Category-Dropdown von CleverReach für diese Site erscheint. Dafür muss `bridge2cleverreach.dynamicContentsCustom` aktiv sein, sonst ist der Button in der Übersicht deaktiviert.

Ein neuer eigener Typ benötigt daher:

1. Einen TypoScript-Eintrag mit `dataProcessor`.
2. Die Site-Einstellung `bridge2cleverreach.dynamicContentsCustom`.
3. Einmalige Aktivierung über das Untermodul **Eigene Inhalte**.

Der `dataProcessor` entscheidet selbst, was "ausgewählt" für den eigenen Typ bedeutet. Er kann eine eigene Tabelle abfragen, eine externe API aufrufen oder statische Daten liefern. Eine Auswahl-UI wie bei News oder Inhaltselementen ist nur nötig, wenn Redakteurinnen einzelne Datensätze von Hand kuratieren sollen.

## Eigenen Typ hinzufügen

**1. In TypoScript registrieren** (z. B. im Sitepackage, importiert nach dem `setup.typoscript` der Extension):

```typoscript
plugin.tx_bridge2cleverreach {
    dynamicContents {
        2 {
            type = events
            dataProcessor = Vendor\MySitepackage\DataProcessing\EventsDynamicContentDataProcessor->getItems
            # weitere Keys sind frei wählbar und liegen ganz beim eigenen Processor, z. B.:
            detailPid = 123
        }
    }
}
```

Den nächsten freien numerischen Index verwenden. `0` ist `content-elements`, `1` ist `news`, sobald die News-Einstellung aktiv ist. `type` darf noch nicht verwendet sein.

**2. DataProcessor schreiben.** Die Klasse braucht nur eine öffentliche Methode passend zum `dataProcessor`-String (`Class->method`); sie wird über `GeneralUtility::callUserFunction()` aufgerufen:

```php
<?php

namespace Vendor\MySitepackage\DataProcessing;

use Rozumbunch\Bridge2Cleverreach\Controller\CleverreachController;

class EventsDynamicContentDataProcessor
{
    public function getItems(array &$params, CleverreachController $obj): void
    {
        foreach ($this->getUpcomingEvents() as $event) {
            $params['items'][] = [
                'title' => $event['title'],
                'teaser' => $event['teaser'] ?? '',
                'display_info' => $event['date'],
                'description' => $event['description'] ?? '',
                'image' => $event['imageUrl'] ?? '',
                'url' => (string)$params['site']->getRouter()->generateUri((int)$event['detailPid']),
            ];
        }
    }

    private function getUpcomingEvents(): array
    {
        // Eigene Query, API-Aufruf, Repository, ... - was auch immer "events" im eigenen Projekt bedeutet.
        return [];
    }
}
```

`$params` enthält immer `site` (die aktuelle `Site`) und `request`. Setzt der TypoScript-Eintrag ein `detailPid`, wird es vor dem Aufruf nach `$params['detailPid']` kopiert. Alle sechs Item-Keys (`title`, `teaser`, `display_info`, `description`, `image`, `url`) werden vom My-Content-Vertrag von CleverReach erwartet. `NewsDynamicContentDataProcessor` und `ContentElementsDynamicContentDataProcessor` sind die beiden mitgelieferten Beispiele, inklusive Bild-Auflösung über FAL.

**3. Typ aktivieren.** Im Backend **Web > CleverReach > Eigene Inhalte** öffnen, den neuen Typ (`events`) in die Auswahl verschieben und speichern.

**4. Caches leeren.** Änderungen an TypoScript benötigen den üblichen Cache-Flush, bevor der neue Eintrag wirksam wird.

Der Typ erscheint danach als Kategorie im My-Content-Picker von CleverReach, und die Auswahl dort ruft den eigenen `dataProcessor` auf.

## Auswahl-UI für Redakteurinnen

Sollen Redakteurinnen einzelne Datensätze für den eigenen Typ von Hand auswählen, folgt man demselben Muster wie News und Inhaltselemente:

- Eine Repository-Klasse mit `getSelected...()` / `setSelected...()`, die die Auswahl in `tx_bridge2cleverreach_domain_model_dynamic_content_settings` unter einem eigenen `type`-Wert speichert (siehe `ContentElementsRepository`).
- Eine neue Action auf `BackendCleverReachDynamicContentController` und ein Fluid-Template unter `Resources/Private/Templates/Backend/DynamicContent/`.
- Ein neuer Eintrag in `Configuration/Backend/Modules.php` (ein `web_dynamicContent...`-Untermodul, `parent: web_dynamicContent`), der auf diese Action geroutet wird.
- Der `dataProcessor` liest die Auswahl über das eigene Repository.

Das ist nur nötig, wenn einzelne Elemente kuratiert werden müssen.
