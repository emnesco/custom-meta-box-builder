# Groups & Repeaters

[Back to README](../README.md) | [Field Types](field-types.md) | [Configuration Reference](configuration-reference.md)

## Repeatable Fields

Any top-level field can be made repeatable by adding `'repeat' => true`. This adds an "Add Row" button below the field and stores multiple values as separate `post_meta` rows.

```php
[
    'id'     => 'phone_numbers',
    'type'   => 'text',
    'label'  => 'Phone Numbers',
    'repeat' => true,
]
```

**How data is stored:** Each value is saved as a separate meta row using `add_post_meta()`, so `get_post_meta($post_id, 'phone_numbers')` returns an indexed array of all values.

**HTML name attribute:** Repeatable scalar fields use the `name[]` bracket syntax (e.g., `phone_numbers[]`).

### Row Limits

Control the minimum and maximum number of repeater rows:

```php
[
    'id'       => 'phone_numbers',
    'type'     => 'text',
    'label'    => 'Phone Numbers',
    'repeat'   => true,
    'min_rows' => 1,
    'max_rows' => 5,
]
```

- `min_rows` — prevents removing rows below this count
- `max_rows` — disables "Add Row" when limit is reached; enforced server-side via `array_slice()`

---

## Group Fields

A group field is a container that holds sub-fields. It renders as a collapsible panel with a header, a sortable handle (index column), and action buttons.

```php
[
    'id'     => 'team_member',
    'type'   => 'group',
    'label'  => 'Team Member',
    'fields' => [
        ['id' => 'name',  'type' => 'text',     'label' => 'Name'],
        ['id' => 'role',  'type' => 'text',     'label' => 'Role'],
        ['id' => 'bio',   'type' => 'textarea', 'label' => 'Bio'],
    ],
]
```

### Repeatable Groups

Add `'repeat' => true` to allow multiple group entries:

```php
[
    'id'     => 'team_members',
    'type'   => 'group',
    'label'  => 'Team Member',
    'repeat' => true,
    'fields' => [
        ['id' => 'name', 'type' => 'text',     'label' => 'Name'],
        ['id' => 'role', 'type' => 'text',     'label' => 'Role'],
    ],
]
```

Each group instance gets: Add Row, Remove (with confirmation dialog), and Duplicate buttons. The JavaScript handles cloning, updating input name indices, and resetting values.

### Non-Repeatable Groups

If `repeat` is omitted or `false`, the group renders a single instance. Internally, the plugin still treats top-level groups as repeatable behind the scenes (via `repeat_fake`) to preserve the nested key structure in `$_POST` data. This is transparent to the developer.

---

## Sortable Rows

Repeatable group rows are automatically sortable via drag-and-drop using jQuery UI Sortable. Drag the header or the index column to reorder. Name indices are updated automatically after sorting.

---

## Row Titles

By default, group headers show a generic index number. Use `row_title_field` to display a sub-field's value as the row title:

```php
[
    'id'              => 'team_members',
    'type'            => 'group',
    'label'           => 'Team Member',
    'repeat'          => true,
    'row_title_field' => 'name',  // Shows the "name" sub-field value in the header
    'fields'          => [
        ['id' => 'name', 'type' => 'text', 'label' => 'Name'],
        ['id' => 'role', 'type' => 'text', 'label' => 'Role'],
    ],
]
```

The title updates dynamically as the user types.

---

## Duplicate Button

Each group item has a "Duplicate" button that clones the row with its current values and appends it after the current row. Max rows limits are respected.

---

## Search/Filter

For groups with many items, add `'searchable' => true` to display a search input above the group items. Typing filters rows by their text content:

```php
[
    'id'         => 'faq_items',
    'type'       => 'group',
    'label'      => 'FAQ',
    'repeat'     => true,
    'searchable' => true,
    'fields'     => [...],
]
```

---

## Expand All / Collapse All

Repeatable groups show "Expand All" and "Collapse All" links above the group items for quick toggling.

---

## Name Attribute Resolution

The `FieldRenderer` builds correct `name` attributes for any nesting depth. The resolution logic in `getname()` and `getChildPrefix()` works as follows:

| Scenario | Name Pattern | Example |
|---|---|---|
| Simple field | `field_id` | `email` |
| Repeatable field | `field_id[]` | `phone[]` |
| Group > field | `group_id[index][field_id]` | `team[0][name]` |
| Non-repeat group > field | `group_id[field_id]` | `settings[color]` |
| Nested group | `parent[0][child][0][field]` | `team[0][socials][0][url]` |

The parent context is passed down as a string prefix, allowing unlimited nesting depth.

---

## Nested Groups (Deep Nesting)

Groups can contain other groups. There is no hard limit on nesting depth.

```php
[
    'id'     => 'sections',
    'type'   => 'group',
    'label'  => 'Page Section',
    'repeat' => true,
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'label' => 'Section Heading'],
        [
            'id'     => 'items',
            'type'   => 'group',
            'label'  => 'Item',
            'repeat' => true,
            'fields' => [
                ['id' => 'title',       'type' => 'text',     'label' => 'Title'],
                ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
            ],
        ],
    ],
]
```

This produces name attributes like:
- `sections[0][heading]`
- `sections[0][items][0][title]`
- `sections[0][items][0][description]`
- `sections[0][items][1][title]`

---

## Retrieving Group Data

Group data is stored as serialized arrays in `post_meta`:

```php
// Repeatable group
$members = get_post_meta($post_id, 'team_members');
// Returns:
// [
//     ['name' => 'Alice', 'role' => 'Developer'],
//     ['name' => 'Bob',   'role' => 'Designer'],
// ]

// Access individual values
echo $members[0]['name']; // "Alice"
```

For deeply nested groups:

```php
$sections = get_post_meta($post_id, 'sections');
echo $sections[0]['items'][1]['title']; // Second item title in first section
```

---

## JavaScript Behavior

The repeater UI is managed by `assets/cmb-script.js`. Key behaviors:

- **Add Row** — Clones the last group item, resets all input values, and increments the name index at the correct nesting level
- **Remove Row** — Shows confirmation dialog, then slides out the item; respects `min_rows`
- **Duplicate** — Clones the clicked item with its values intact; respects `max_rows`
- **Toggle** — Click or keyboard (Enter/Space) toggles the collapsible body; updates `aria-expanded`
- **Sortable** — jQuery UI Sortable on `.cmb-group-items`; updates name indices after reorder
- **Row Titles** — Listens for `input`/`change` events and updates header text for `row_title_field`
- **Search** — Filters group items by text content as the user types
- **Lazy Loading** — Groups with 20+ items auto-hide excess; "Load more" button reveals in batches
- **Empty State** — Shows "No items yet" message when all items are removed
- **Expand/Collapse All** — Toggles all group items open or closed
- **Item Count** — Displays "N items" next to the Add Row button
- **Nested awareness** — `processNestedGroups()` recursively handles cloning at any depth

---

## Next Steps

- [Configuration Reference](configuration-reference.md) — all available config keys
- [Extending](extending.md) — create your own field types
- [Advanced Features](advanced-features.md) — tabs, conditional logic, and more
