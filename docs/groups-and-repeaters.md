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

---

## Group Fields

A group field is a container that holds sub-fields. It renders as a collapsible panel with a header, a drag handle (index column), and a remove button.

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

Each group instance gets an "Add Row" button and a remove (`x`) button. The JavaScript handles cloning the last group item, updating input name indices, and resetting values.

### Non-Repeatable Groups

If `repeat` is omitted or `false`, the group renders a single instance. Internally, the plugin still treats top-level groups as repeatable behind the scenes (via `repeat_fake`) to preserve the nested key structure in `$_POST` data. This is transparent to the developer.

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
- **Remove Row** — Removes the clicked group item from the DOM
- **Toggle** — Clicking a group header toggles the collapsible body open/closed
- **Nested awareness** — The `processNestedGroups()` function recursively handles cloning at any depth, resetting nested groups back to a single empty item

---

## Next Steps

- [Configuration Reference](configuration-reference.md) — all available config keys
- [Extending](extending.md) — create your own field types
