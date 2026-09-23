# Dynamic Content ("My Content" for CleverReach)

CleverReach's editor has a **My Content** picker that lets an editor insert dynamic blocks (news, content elements, custom categories) into a newsletter. This extension answers that picker's requests.

## How it fits together

- `plugin.tx_bridge2cleverreach.dynamicContents` (TypoScript) registers one entry per category: a `type` identifier and a `dataProcessor` callable.
- Two categories ship with the extension:
  - Index `0`, `type = content-elements`, always active. Allowed CTypes come from the site setting `bridge2cleverreach.allowedCTypesForDynamicContents`.
  - Index `1`, `type = news`, only when the site setting `bridge2cleverreach.dynamicContentsAsNews` is enabled. The detail page is `bridge2cleverreach.dynamicContentsNewsDetailPid`.
- `CleverreachController::getDynamicContent()` answers requests on the endpoint page (`typeNum = 1705405176`, see `Configuration/TypoScript/Endpoints/`). CleverReach sends **POST**, body `application/x-www-form-urlencoded`, to `/?type=1705405176` of the site:
  - `get=filter` returns the categories for the **Category** dropdown.
  - `get=search` together with `category=<type>` calls the matching entry's `dataProcessor`, which fills `$params['items']`.
  - Without `get`, the endpoint returns the HTML table `This is CleverReach default content`. Opening the URL in a browser without a POST body is therefore not JSON.
- The site is resolved from the request host. Backend selections apply to that site's root page.

`get=filter` reads `tx_bridge2cleverreach_domain_model_dynamic_content_settings` for the root page. A category appears only after something was saved in the matching sub-module. The type `content-elements-source-page` is not its own category. The type `custom` is replaced by the type identifiers stored in its data column (`DynamicContentService::getDynamicContentFilters()`).

## Backend module

**Web > CleverReach** shows an overview. A page must be selected in the page tree; the site is taken from that page. Selections are stored in `tx_bridge2cleverreach_domain_model_dynamic_content_settings`, with `pid` set to the site's root page id.

| Sub-module | Manages | Stored under `type` |
|---|---|---|
| Content elements | source pages and individual `tt_content` records | `content-elements-source-page` / `content-elements` |
| News | `georgringer/news` records from this site's page tree | `news` |
| Custom content | which registered `dynamicContents` types are exposed to CleverReach | `custom` |

News and Custom content stay visible on the overview. The button is disabled while the matching site setting is off: `bridge2cleverreach.dynamicContentsAsNews` or `bridge2cleverreach.dynamicContentsCustom`. The News sub-module is registered only when the `news` extension is loaded.

### Content elements

Two sections:

1. **Content elements from a specific page.** Stored values are page uids of the current site (`content-elements-source-page`). A search with `category=content-elements` renders every `tt_content` on those pages whose CType is listed in `bridge2cleverreach.allowedCTypesForDynamicContents`.
2. **Individual content elements.** Stored values are `tt_content` uids (`content-elements`), and only when the record lies on a stored source page and its CType is allowed. An element that is not allowed is not added.

The search returns the individually stored records plus every allowed content element of the source pages. The same uid appears only once. Per item: `title` and `display_info` from `header`, `teaser` from `subheader`, `description` from `bodytext`, `image` from the first FAL relation `assets` or `image`, `url` is the page of the content element.

### News

Only news records whose page belongs to the selected site's page tree can be chosen. The URL points at `bridge2cleverreach.dynamicContentsNewsDetailPid`.

## The "Custom content" sub-module

Unlike News and Content elements, **Custom content** does not manage individual records. It manages which types are turned on for this site. Every `dynamicContents` entry except `news` and `content-elements` appears in the available list. Moving a type into the selection and saving is what makes it show up in CleverReach's Category dropdown for that site. `bridge2cleverreach.dynamicContentsCustom` must be enabled, otherwise the overview button stays disabled.

A new custom type therefore needs:

1. A TypoScript entry with a `dataProcessor`.
2. The site setting `bridge2cleverreach.dynamicContentsCustom`.
3. Being enabled once via the **Custom content** sub-module.

The `dataProcessor` decides what "selected" means for that type. It can query its own table, call an external API, or return static data. A picker like News or Content elements is only needed when editors hand-pick individual records.

## Adding your own type

**1. Register it in TypoScript** (for example in the site package, imported after the extension's `setup.typoscript`):

```typoscript
plugin.tx_bridge2cleverreach {
    dynamicContents {
        2 {
            type = events
            dataProcessor = Vendor\MySitepackage\DataProcessing\EventsDynamicContentDataProcessor->getItems
            # further keys are free and belong to your processor, for example:
            detailPid = 123
        }
    }
}
```

Use the next free numeric index. `0` is `content-elements`, `1` is `news` once the news setting is enabled. `type` must not already be in use.

**2. Write the data processor.** The class only needs a public method matching the `dataProcessor` string (`Class->method`). It is called via `GeneralUtility::callUserFunction()`:

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
        // Own query, API call, repository, ... - whatever "events" means in this project.
        return [];
    }
}
```

`$params` always contains `site` (the current `Site`) and `request`. If the TypoScript entry sets `detailPid`, it is copied to `$params['detailPid']` before the call. All six item keys (`title`, `teaser`, `display_info`, `description`, `image`, `url`) are part of CleverReach's My Content contract. `NewsDynamicContentDataProcessor` and `ContentElementsDynamicContentDataProcessor` are the two built-in examples, including image resolution through FAL.

**3. Enable the type.** In the backend open **Web > CleverReach > Custom content**, move the new type (`events`) into the selection and save.

**4. Flush caches.** TypoScript changes need the usual cache flush before the new entry takes effect.

The type then appears as a category in CleverReach's My Content picker, and selecting it calls the custom `dataProcessor`.

## A picker UI for editors

When editors should hand-pick individual records for a custom type, follow the same pattern as News and Content elements:

- A repository class with `getSelected...()` / `setSelected...()` that stores the selection in `tx_bridge2cleverreach_domain_model_dynamic_content_settings` under its own `type` value (see `ContentElementsRepository`).
- A new action on `BackendCleverReachDynamicContentController` and a Fluid template under `Resources/Private/Templates/Backend/DynamicContent/`.
- A new entry in `Configuration/Backend/Modules.php` (a `web_dynamicContent...` sub-module, `parent: web_dynamicContent`) routed to that action.
- The `dataProcessor` reads the selection through that repository.

That is only needed when individual records must be curated.
