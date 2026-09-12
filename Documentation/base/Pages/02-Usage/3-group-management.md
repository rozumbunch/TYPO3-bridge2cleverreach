# Flexible Group Management

Groups are configured at site level and then assigned per plugin instance.

## 1. Default group (Site Configuration)

The default list is maintained in the backend tab **Cleverreach**:

- Default List ID
- Label for default list ID (optional)
- Default Form ID (Flow UUID or Form ID)

If a plugin does not select any additional groups, this default group is used for subscription.

## 2. Additional groups (site `config.yaml`)

Additional groups have no backend UI. They are defined in the site `config.yaml`:

```yaml
cleverreachGroups:
  -
    name: 'Politics & Society'
    group: '1234567'
    doubleOptInMailId: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'
  -
    name: 'Culture'
    group: '7654321'
    doubleOptInMailId: '123456'
```

| Key | Description |
|---|---|
| `name` | Display name in the plugin FlexForm and in the frontend select |
| `group` | CleverReach recipient list ID |
| `doubleOptInMailId` | Flow UUID or legacy Form ID for Double Opt-In |

The field `settings.newsletterGroups` in the plugin is only shown when at least one additional group is configured.

## 3. Per-plugin selection (FlexForm)

Each Subscribeform / Unsubscribeform instance can select which groups it applies to.

**Subscribeform**

- Selected groups: the form offers exactly these groups
- Empty selection: the default group from the Site Configuration is used
- More than one group: the frontend shows a required select
- First option **Subscribe to all newsletters**: subscribes to every group selected in this plugin
- A specific option: subscribes only to that group
- Exactly one group: no select is rendered; the group is used automatically

**Unsubscribeform**

- Selected groups: the form offers exactly these groups
- Empty selection: all configured groups are offered (default list plus additional groups)
- More than one group: the frontend shows a required select
- First option **Unsubscribe from all newsletters**: removes the visitor from every group offered by this plugin
- A specific option: unsubscribes only from that group
- Exactly one group: no select is rendered; unsubscription runs against that group

## Frontend behaviour

The select is rendered only when more than one group is selectable. The field label is “Select newsletter”. The first option is “Subscribe to all newsletters” / “Unsubscribe from all newsletters” (`value="all"`).

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

Relevant files:

```text
Classes/Utility/CleverReachGroupMapper.php
Classes/Backend/Form/NewsletterGroupsFieldConfiguration.php
Classes/Controller/CleverreachController.php
Configuration/FlexForms/flexform_subscribeform.xml
Configuration/FlexForms/flexform_unsubscribeform.xml
```
