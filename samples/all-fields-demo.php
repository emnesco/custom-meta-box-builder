<?php
/**
 * Custom Meta Box Builder — Complete Field Demo
 *
 * This file demonstrates ALL 18 field types and every major feature of the
 * Custom Meta Box Builder plugin. Include it in your theme's functions.php
 * or in a custom plugin file:
 *
 *   require_once __DIR__ . '/path/to/all-fields-demo.php';
 *
 * It registers multiple meta boxes on the 'post' post type covering:
 *
 *   Meta Box 1: "Basic Fields"         — text, textarea, number, email, url, password, hidden
 *   Meta Box 2: "Choice Fields"        — select, radio, checkbox
 *   Meta Box 3: "Rich & Media Fields"  — wysiwyg, file, color, date, datetime
 *   Meta Box 4: "Relational Fields"    — post, taxonomy, user
 *   Meta Box 5: "Group & Repeater"     — group (repeatable, sortable, row title, searchable, nested)
 *   Meta Box 6: "Tabbed Meta Box"      — tabs grouping multiple field types
 *   Meta Box 7: "Conditional Logic"    — fields that show/hide based on other field values
 *   Meta Box 8: "Repeatable Scalars"   — repeatable text, number, email, url fields
 *   Meta Box 9: "Validation & REST"    — validation rules, REST API, multilingual, sanitize_callback
 *
 * All fields use realistic labels, descriptions, defaults, and attributes.
 * Adjust IDs if they conflict with your existing meta keys.
 *
 * @package CustomMetaBoxBuilder
 */

// Prevent direct access.
defined('ABSPATH') || exit;

// ──────────────────────────────────────────────────────────────────────────────
// META BOX 1: Basic Fields
// Demonstrates: text, textarea, number, email, url, password, hidden
// Context: normal | Priority: high
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-basic',
    'Demo: Basic Fields',
    'post',
    [
        // ── Text Field ──
        // Standard single-line text input.
        // Uses sanitize_text_field() by default.
        [
            'id'          => 'demo_text',
            'type'        => 'text',
            'label'       => 'Full Name',
            'description' => 'Enter the person\'s full name. Max 120 characters.',
            'required'    => true,
            'default'     => '',
            'width'       => 'w-50',
            'attributes'  => [
                'placeholder' => 'e.g. John Doe',
                'maxlength'   => '120',
            ],
        ],

        // ── Email Field ──
        // Renders <input type="email">.
        // Uses sanitize_email() — strips invalid characters.
        [
            'id'          => 'demo_email',
            'type'        => 'email',
            'label'       => 'Email Address',
            'description' => 'A valid email address.',
            'required'    => true,
            'width'       => 'w-50',
            'attributes'  => [
                'placeholder' => 'name@example.com',
            ],
        ],

        // ── URL Field ──
        // Renders <input type="url">.
        // Uses esc_url_raw() — strips dangerous protocols.
        [
            'id'          => 'demo_url',
            'type'        => 'url',
            'label'       => 'Website URL',
            'description' => 'Full URL including https://',
            'width'       => 'w-50',
            'attributes'  => [
                'placeholder' => 'https://example.com',
            ],
        ],

        // ── Number Field ──
        // Renders <input type="number"> with min/max/step.
        // Sanitizes to intval() or floatval() based on step.
        [
            'id'          => 'demo_number',
            'type'        => 'number',
            'label'       => 'Price ($)',
            'description' => 'Product price. Supports 2 decimal places.',
            'default'     => '0.00',
            'width'       => 'w-50',
            'attributes'  => [
                'min'         => '0',
                'max'         => '99999.99',
                'step'        => '0.01',
                'placeholder' => '0.00',
            ],
        ],

        // ── Textarea Field ──
        // Multi-line text input.
        // Uses sanitize_textarea_field() — preserves newlines.
        [
            'id'          => 'demo_textarea',
            'type'        => 'textarea',
            'label'       => 'Short Bio',
            'description' => 'A brief biography (plain text, no HTML).',
            'layout'      => 'inline',
            'attributes'  => [
                'rows'        => '4',
                'placeholder' => 'Tell us about yourself...',
            ],
        ],

        // ── Password Field ──
        // Renders <input type="password">.
        // Uses sanitize_text_field().
        [
            'id'          => 'demo_password',
            'type'        => 'password',
            'label'       => 'API Secret Key',
            'description' => 'This value is masked in the editor.',
            'width'       => 'w-50',
        ],

        // ── Hidden Field ──
        // Renders <input type="hidden">. No label is displayed.
        // Useful for storing internal values (form version, tracking IDs, etc.).
        [
            'id'          => 'demo_hidden',
            'type'        => 'hidden',
            'default'     => 'v2.0',
        ],
    ],
    'normal',
    'high'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 2: Choice Fields
// Demonstrates: select, radio, checkbox
// Context: normal | Priority: default
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-choices',
    'Demo: Choice Fields',
    'post',
    [
        // ── Select Field ──
        // Renders a <select> dropdown.
        // Sanitizes by validating the value exists in the options array.
        [
            'id'          => 'demo_select',
            'type'        => 'select',
            'label'       => 'Priority Level',
            'description' => 'Choose the priority for this item.',
            'default'     => 'medium',
            'width'       => 'w-33',
            'options'     => [
                ''       => '— Select Priority —',
                'low'    => 'Low',
                'medium' => 'Medium',
                'high'   => 'High',
                'urgent' => 'Urgent',
            ],
        ],

        // ── Radio Field ──
        // Renders a <fieldset> with radio buttons.
        // Uses the same 'options' config key as select.
        // Sanitizes by validating against the options whitelist.
        [
            'id'          => 'demo_radio',
            'type'        => 'radio',
            'label'       => 'Content Visibility',
            'description' => 'Who can see this content?',
            'default'     => 'public',
            'width'       => 'w-33',
            'options'     => [
                'public'  => 'Public (everyone)',
                'members' => 'Members only',
                'admins'  => 'Admins only',
            ],
        ],

        // ── Checkbox Field ──
        // Renders an <input type="checkbox">.
        // Stores '1' when checked, '0' when unchecked.
        [
            'id'          => 'demo_checkbox',
            'type'        => 'checkbox',
            'label'       => 'Featured Post',
            'description' => 'Check to pin this post as featured on the homepage.',
            'width'       => 'w-33',
        ],
    ],
    'normal',
    'default'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 3: Rich & Media Fields
// Demonstrates: wysiwyg, file, color, date, datetime-local
// Context: normal | Priority: default
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-rich-media',
    'Demo: Rich & Media Fields',
    'post',
    [
        // ── WYSIWYG / Rich Text Editor ──
        // Integrates WordPress TinyMCE via wp_editor().
        // Uses wp_kses_post() for sanitization — allows safe HTML tags.
        [
            'id'          => 'demo_wysiwyg',
            'type'        => 'wysiwyg',
            'label'       => 'Extended Content',
            'description' => 'Rich text editor with full formatting support.',
            'layout'      => 'inline',
        ],

        // ── File / Image Upload ──
        // Opens the WordPress Media Library modal.
        // Stores the attachment ID (integer).
        // Displays an image preview or file link.
        // Uses absint() for sanitization.
        [
            'id'          => 'demo_file_image',
            'type'        => 'file',
            'label'       => 'Hero Image',
            'description' => 'Select an image from the media library. Recommended: 1200x630px.',
            'button_text' => 'Choose Image',
            'preview'     => 'image',
            'width'       => 'w-50',
        ],

        // ── File Upload (non-image) ──
        // Same field type but with file preview instead of image.
        [
            'id'          => 'demo_file_document',
            'type'        => 'file',
            'label'       => 'Downloadable PDF',
            'description' => 'Attach a PDF document.',
            'button_text' => 'Upload PDF',
            'preview'     => 'file',
            'width'       => 'w-50',
        ],

        // ── Color Picker ──
        // Renders <input type="color">.
        // Validates hex format: /^#[a-fA-F0-9]{6}$/
        [
            'id'          => 'demo_color',
            'type'        => 'color',
            'label'       => 'Accent Color',
            'description' => 'Pick a color for the post header.',
            'default'     => '#2271b1',
            'width'       => 'w-25',
        ],

        // ── Date Field ──
        // Renders <input type="date">.
        // Validates ISO 8601 date format (YYYY-MM-DD).
        [
            'id'          => 'demo_date',
            'type'        => 'date',
            'label'       => 'Publish Date',
            'description' => 'The intended publish date.',
            'width'       => 'w-25',
        ],

        // ── DateTime Field ──
        // Renders <input type="datetime-local"> when 'datetime' => true.
        // Validates ISO 8601 datetime format (YYYY-MM-DDTHH:MM).
        [
            'id'          => 'demo_datetime',
            'type'        => 'date',
            'label'       => 'Event Date & Time',
            'description' => 'Date and time for the event.',
            'datetime'    => true,
            'width'       => 'w-25',
        ],
    ],
    'normal',
    'default'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 4: Relational Fields
// Demonstrates: post, taxonomy, user
// Context: side | Priority: default
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-relational',
    'Demo: Relational Fields',
    'post',
    [
        // ── Post Object Selector ──
        // Renders a <select> populated with posts via get_posts().
        // Config key 'post_type' controls which post type to query.
        // Config key 'limit' controls max number of posts in dropdown.
        // Sanitizes with absint() + get_post() existence check.
        [
            'id'          => 'demo_post',
            'type'        => 'post',
            'label'       => 'Related Post',
            'description' => 'Link to another post.',
            'post_type'   => 'post',
            'limit'       => 50,
        ],

        // ── Taxonomy Term Selector (Checkbox List) ──
        // Renders a checkbox list of taxonomy terms via get_terms().
        // Config 'taxonomy' specifies the taxonomy slug.
        // Config 'field_style' => 'checkbox' (default) or 'select'.
        // Sanitizes term IDs with absint().
        [
            'id'          => 'demo_taxonomy_checkbox',
            'type'        => 'taxonomy',
            'label'       => 'Categories',
            'description' => 'Select one or more categories.',
            'taxonomy'    => 'category',
            'field_style' => 'checkbox',
        ],

        // ── Taxonomy Term Selector (Select Dropdown) ──
        // Same field type but with select dropdown style.
        [
            'id'          => 'demo_taxonomy_select',
            'type'        => 'taxonomy',
            'label'       => 'Primary Tag',
            'description' => 'Choose a primary tag.',
            'taxonomy'    => 'post_tag',
            'field_style' => 'select',
        ],

        // ── User Selector ──
        // Renders a <select> populated with WordPress users via get_users().
        // Config 'role' filters users by role (optional).
        // Sanitizes with absint().
        [
            'id'          => 'demo_user',
            'type'        => 'user',
            'label'       => 'Assigned Author',
            'description' => 'Assign a user to this post.',
            'role'        => '',
        ],
    ],
    'side',
    'default'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 5: Group & Repeater
// Demonstrates: group (repeatable + sortable + row title + searchable + nested)
// Context: normal | Priority: default
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-groups',
    'Demo: Groups & Repeaters',
    'post',
    [
        // ── Repeatable Group with Row Title ──
        // - 'repeat' => true: allows adding/removing/duplicating rows
        // - 'collapsed' => true: rows start collapsed
        // - 'row_title_field' => 'member_name': shows member name in header
        // - 'searchable' => true: adds a search filter above the items
        // - 'min_rows' / 'max_rows': enforces row count limits
        // - Sub-fields use width classes for side-by-side layout
        // - Contains all common sub-field types to show they work inside groups
        [
            'id'              => 'demo_team_members',
            'type'            => 'group',
            'label'           => 'Team Members',
            'description'     => 'Add team members with their details. Drag to reorder.',
            'repeat'          => true,
            'collapsed'       => true,
            'row_title_field' => 'member_name',
            'searchable'      => true,
            'min_rows'        => 1,
            'max_rows'        => 20,
            'fields'          => [
                [
                    'id'         => 'member_name',
                    'type'       => 'text',
                    'label'      => 'Name',
                    'required'   => true,
                    'width'      => 'w-50',
                    'attributes' => ['placeholder' => 'Full name'],
                ],
                [
                    'id'    => 'member_email',
                    'type'  => 'email',
                    'label' => 'Email',
                    'width' => 'w-50',
                ],
                [
                    'id'      => 'member_role',
                    'type'    => 'select',
                    'label'   => 'Role',
                    'width'   => 'w-33',
                    'options' => [
                        ''           => '— Select Role —',
                        'developer'  => 'Developer',
                        'designer'   => 'Designer',
                        'manager'    => 'Project Manager',
                        'qa'         => 'QA Engineer',
                        'devops'     => 'DevOps',
                    ],
                ],
                [
                    'id'      => 'member_status',
                    'type'    => 'radio',
                    'label'   => 'Status',
                    'width'   => 'w-33',
                    'default' => 'active',
                    'options' => [
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                    ],
                ],
                [
                    'id'    => 'member_featured',
                    'type'  => 'checkbox',
                    'label' => 'Featured',
                    'width' => 'w-33',
                ],
                [
                    'id'          => 'member_photo',
                    'type'        => 'file',
                    'label'       => 'Photo',
                    'button_text' => 'Choose Photo',
                    'preview'     => 'image',
                ],
                [
                    'id'    => 'member_bio',
                    'type'  => 'textarea',
                    'label' => 'Bio',
                    'attributes' => ['rows' => '3'],
                ],
                [
                    'id'    => 'member_website',
                    'type'  => 'url',
                    'label' => 'Website',
                ],
                [
                    'id'    => 'member_start_date',
                    'type'  => 'date',
                    'label' => 'Start Date',
                    'width' => 'w-50',
                ],
                [
                    'id'      => 'member_color',
                    'type'    => 'color',
                    'label'   => 'Profile Color',
                    'default' => '#3366cc',
                    'width'   => 'w-50',
                ],
            ],
        ],

        // ── Non-Repeatable Group ──
        // A single group instance (no repeat). Shows settings as a collapsible section.
        [
            'id'     => 'demo_seo_settings',
            'type'   => 'group',
            'label'  => 'SEO Settings',
            'fields' => [
                [
                    'id'          => 'seo_title',
                    'type'        => 'text',
                    'label'       => 'Meta Title',
                    'description' => 'Override the default page title (max 60 chars).',
                    'attributes'  => ['maxlength' => '60'],
                ],
                [
                    'id'          => 'seo_description',
                    'type'        => 'textarea',
                    'label'       => 'Meta Description',
                    'description' => 'Search engine description (max 160 chars).',
                    'attributes'  => ['maxlength' => '160', 'rows' => '3'],
                ],
                [
                    'id'    => 'seo_noindex',
                    'type'  => 'checkbox',
                    'label' => 'Noindex this post',
                ],
            ],
        ],

        // ── Nested Group (Group inside Group) ──
        // Demonstrates unlimited nesting depth.
        [
            'id'     => 'demo_faq_sections',
            'type'   => 'group',
            'label'  => 'FAQ Sections',
            'repeat' => true,
            'row_title_field' => 'section_title',
            'max_rows' => 10,
            'fields' => [
                [
                    'id'         => 'section_title',
                    'type'       => 'text',
                    'label'      => 'Section Title',
                    'required'   => true,
                    'attributes' => ['placeholder' => 'e.g. General Questions'],
                ],
                // Nested repeatable group inside the parent group
                [
                    'id'              => 'faq_items',
                    'type'            => 'group',
                    'label'           => 'FAQ Item',
                    'repeat'          => true,
                    'row_title_field' => 'question',
                    'max_rows'        => 20,
                    'fields'          => [
                        [
                            'id'       => 'question',
                            'type'     => 'text',
                            'label'    => 'Question',
                            'required' => true,
                        ],
                        [
                            'id'    => 'answer',
                            'type'  => 'textarea',
                            'label' => 'Answer',
                            'attributes' => ['rows' => '4'],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'normal',
    'default'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 6: Tabbed Meta Box
// Demonstrates: tabs with multiple field types organized into sections
// Context: normal | Priority: default
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-tabs',
    'Demo: Tabbed Fields',
    'post',
    [
        'tabs' => [
            // ── Tab 1: General ──
            'general' => [
                'label'  => 'General',
                'fields' => [
                    [
                        'id'       => 'tab_title',
                        'type'     => 'text',
                        'label'    => 'Display Title',
                        'required' => true,
                    ],
                    [
                        'id'      => 'tab_subtitle',
                        'type'    => 'text',
                        'label'   => 'Subtitle',
                    ],
                    [
                        'id'      => 'tab_status',
                        'type'    => 'select',
                        'label'   => 'Status',
                        'options' => [
                            'draft'     => 'Draft',
                            'review'    => 'In Review',
                            'published' => 'Published',
                        ],
                    ],
                    [
                        'id'    => 'tab_featured',
                        'type'  => 'checkbox',
                        'label' => 'Mark as Featured',
                    ],
                ],
            ],

            // ── Tab 2: Media ──
            'media' => [
                'label'  => 'Media',
                'fields' => [
                    [
                        'id'          => 'tab_hero_image',
                        'type'        => 'file',
                        'label'       => 'Hero Image',
                        'button_text' => 'Select Image',
                        'preview'     => 'image',
                    ],
                    [
                        'id'      => 'tab_bg_color',
                        'type'    => 'color',
                        'label'   => 'Background Color',
                        'default' => '#ffffff',
                    ],
                    [
                        'id'    => 'tab_gallery_note',
                        'type'  => 'textarea',
                        'label' => 'Gallery Notes',
                        'attributes' => ['rows' => '3'],
                    ],
                ],
            ],

            // ── Tab 3: Settings ──
            'settings' => [
                'label'  => 'Settings',
                'fields' => [
                    [
                        'id'      => 'tab_layout',
                        'type'    => 'radio',
                        'label'   => 'Page Layout',
                        'default' => 'full',
                        'options' => [
                            'full'    => 'Full Width',
                            'sidebar' => 'With Sidebar',
                            'narrow'  => 'Narrow Content',
                        ],
                    ],
                    [
                        'id'          => 'tab_css_class',
                        'type'        => 'text',
                        'label'       => 'Custom CSS Class',
                        'description' => 'Add a custom class to the page wrapper.',
                    ],
                    [
                        'id'    => 'tab_publish_date',
                        'type'  => 'date',
                        'label' => 'Schedule Date',
                    ],
                    [
                        'id'    => 'tab_assigned_user',
                        'type'  => 'user',
                        'label' => 'Assigned Editor',
                        'role'  => 'editor',
                    ],
                ],
            ],
        ],
    ],
    'normal',
    'default'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 7: Conditional Logic
// Demonstrates: fields that show/hide based on other field values
// Operators: ==, !=, contains, empty, !empty
// Context: normal | Priority: default
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-conditional',
    'Demo: Conditional Logic',
    'post',
    [
        // ── Controller Field ──
        // Other fields depend on this value.
        [
            'id'          => 'demo_payment_method',
            'type'        => 'select',
            'label'       => 'Payment Method',
            'description' => 'Select a method — related fields will appear below.',
            'options'     => [
                ''       => '— Select —',
                'cash'   => 'Cash',
                'card'   => 'Credit Card',
                'bank'   => 'Bank Transfer',
                'crypto' => 'Cryptocurrency',
            ],
        ],

        // ── Conditional: shows when payment_method == 'card' ──
        [
            'id'          => 'demo_card_number',
            'type'        => 'text',
            'label'       => 'Card Number',
            'description' => 'Only visible when "Credit Card" is selected.',
            'attributes'  => ['placeholder' => '**** **** **** ****'],
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '==',
                'value'    => 'card',
            ],
        ],
        [
            'id'          => 'demo_card_expiry',
            'type'        => 'text',
            'label'       => 'Card Expiry',
            'width'       => 'w-50',
            'attributes'  => ['placeholder' => 'MM/YY'],
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '==',
                'value'    => 'card',
            ],
        ],
        [
            'id'          => 'demo_card_cvv',
            'type'        => 'password',
            'label'       => 'CVV',
            'width'       => 'w-50',
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '==',
                'value'    => 'card',
            ],
        ],

        // ── Conditional: shows when payment_method == 'bank' ──
        [
            'id'          => 'demo_bank_name',
            'type'        => 'text',
            'label'       => 'Bank Name',
            'description' => 'Only visible when "Bank Transfer" is selected.',
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '==',
                'value'    => 'bank',
            ],
        ],
        [
            'id'          => 'demo_bank_account',
            'type'        => 'text',
            'label'       => 'Account Number',
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '==',
                'value'    => 'bank',
            ],
        ],

        // ── Conditional: shows when payment_method != '' (not empty) ──
        [
            'id'          => 'demo_payment_notes',
            'type'        => 'textarea',
            'label'       => 'Payment Notes',
            'description' => 'Visible when ANY payment method is selected (operator: !empty).',
            'attributes'  => ['rows' => '3'],
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '!empty',
                'value'    => '',
            ],
        ],

        // ── Conditional: shows when payment_method != 'cash' ──
        [
            'id'          => 'demo_receipt_email',
            'type'        => 'email',
            'label'       => 'Receipt Email',
            'description' => 'Visible for all methods except cash (operator: !=).',
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => '!=',
                'value'    => 'cash',
            ],
        ],

        // ── Conditional: shows when payment_method contains "cr" ──
        [
            'id'          => 'demo_crypto_wallet',
            'type'        => 'text',
            'label'       => 'Wallet / Card ID',
            'description' => 'Visible when value contains "cr" (matches "crypto" — operator: contains).',
            'attributes'  => ['placeholder' => '0x... or card ID'],
            'conditional' => [
                'field'    => 'demo_payment_method',
                'operator' => 'contains',
                'value'    => 'cr',
            ],
        ],
    ],
    'normal',
    'default'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 8: Repeatable Scalar Fields
// Demonstrates: repeatable text, number, email, url (non-group repeaters)
// Context: normal | Priority: low
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-repeatable',
    'Demo: Repeatable Scalar Fields',
    'post',
    [
        // ── Repeatable Text ──
        // Each value stored as a separate post_meta row.
        // Renders one input per value + "Add Row" button.
        [
            'id'          => 'demo_tags',
            'type'        => 'text',
            'label'       => 'Custom Tags',
            'description' => 'Add one or more tags. Each is stored as a separate meta row.',
            'repeat'      => true,
            'min_rows'    => 1,
            'max_rows'    => 10,
            'attributes'  => ['placeholder' => 'Enter a tag'],
        ],

        // ── Repeatable Email ──
        [
            'id'          => 'demo_extra_emails',
            'type'        => 'email',
            'label'       => 'Additional Emails',
            'description' => 'Extra notification email addresses.',
            'repeat'      => true,
            'max_rows'    => 5,
        ],

        // ── Repeatable Number ──
        [
            'id'          => 'demo_scores',
            'type'        => 'number',
            'label'       => 'Test Scores',
            'description' => 'Enter individual test scores.',
            'repeat'      => true,
            'attributes'  => ['min' => '0', 'max' => '100'],
        ],

        // ── Repeatable URL ──
        [
            'id'          => 'demo_social_links',
            'type'        => 'url',
            'label'       => 'Social Media Links',
            'description' => 'Add links to social profiles.',
            'repeat'      => true,
            'max_rows'    => 8,
            'attributes'  => ['placeholder' => 'https://...'],
        ],
    ],
    'normal',
    'low'
);


// ──────────────────────────────────────────────────────────────────────────────
// META BOX 9: Validation, REST API, Multilingual, Custom Sanitization
// Demonstrates: validate rules, show_in_rest, multilingual, sanitize_callback
// Context: normal | Priority: low
// ──────────────────────────────────────────────────────────────────────────────
add_custom_meta_box(
    'cmb-demo-advanced',
    'Demo: Validation & Advanced Config',
    'post',
    [
        // ── Field with Validation Rules ──
        // 'validate' accepts an array of rule strings.
        // Available rules: required, email, url, min:N, max:N, numeric, pattern:REGEX
        // Errors are displayed via WordPress admin_notices.
        [
            'id'          => 'demo_validated_username',
            'type'        => 'text',
            'label'       => 'Username',
            'description' => 'Required, min 3 chars, max 30 chars, alphanumeric only.',
            'required'    => true,
            'validate'    => ['required', 'min:3', 'max:30', 'pattern:^[a-zA-Z0-9_]+$'],
            'width'       => 'w-50',
        ],

        // ── Email Validation ──
        [
            'id'          => 'demo_validated_email',
            'type'        => 'email',
            'label'       => 'Contact Email',
            'description' => 'Validated as a proper email format.',
            'required'    => true,
            'validate'    => ['required', 'email'],
            'width'       => 'w-50',
        ],

        // ── URL Validation ──
        [
            'id'          => 'demo_validated_url',
            'type'        => 'url',
            'label'       => 'Portfolio URL',
            'description' => 'Must be a valid URL.',
            'validate'    => ['url'],
            'width'       => 'w-50',
        ],

        // ── Numeric Validation ──
        [
            'id'          => 'demo_validated_age',
            'type'        => 'number',
            'label'       => 'Age',
            'description' => 'Must be numeric, between 18 and 120.',
            'validate'    => ['required', 'numeric', 'min:18', 'max:120'],
            'width'       => 'w-50',
        ],

        // ── REST API Exposure ──
        // 'show_in_rest' => true makes this field available via the WP REST API.
        // The value appears in the post's meta object: GET /wp-json/wp/v2/posts/{id}
        [
            'id'           => 'demo_rest_subtitle',
            'type'         => 'text',
            'label'        => 'Subtitle (REST)',
            'description'  => 'This field is exposed via the WordPress REST API.',
            'show_in_rest' => true,
        ],

        // ── Custom Sanitize Callback ──
        // 'sanitize_callback' overrides the default field sanitizer.
        // Useful for custom logic without creating a new field type.
        [
            'id'                => 'demo_custom_sanitized',
            'type'              => 'text',
            'label'             => 'Slug Field',
            'description'       => 'Auto-converted to lowercase slug via custom sanitize_callback.',
            'sanitize_callback' => function ($value) {
                return sanitize_title($value);
            },
        ],

        // ── Multilingual Field ──
        // 'multilingual' => true enables per-locale values with language tabs.
        // Each locale gets its own meta key: {field_id}_{locale}
        // e.g. demo_ml_title_en, demo_ml_title_fr, demo_ml_title_es
        [
            'id'           => 'demo_ml_title',
            'type'         => 'text',
            'label'        => 'Translated Title',
            'description'  => 'Enter a title for each language. Stored as separate meta keys per locale.',
            'multilingual' => true,
            'locales'      => ['en', 'fr', 'es', 'de'],
        ],

        // ── Multilingual Textarea ──
        [
            'id'           => 'demo_ml_description',
            'type'         => 'textarea',
            'label'        => 'Translated Description',
            'description'  => 'Multi-line text with per-locale values.',
            'multilingual' => true,
            'locales'      => ['en', 'fr', 'es'],
            'attributes'   => ['rows' => '3'],
        ],
    ],
    'normal',
    'low'
);


// ──────────────────────────────────────────────────────────────────────────────
// TAXONOMY TERM META
// Demonstrates: add_custom_taxonomy_meta() for category and post_tag
// ──────────────────────────────────────────────────────────────────────────────
add_custom_taxonomy_meta('category', [
    [
        'id'          => 'demo_cat_icon',
        'type'        => 'file',
        'label'       => 'Category Icon',
        'description' => 'Upload an icon for this category.',
        'button_text' => 'Choose Icon',
        'preview'     => 'image',
    ],
    [
        'id'          => 'demo_cat_color',
        'type'        => 'color',
        'label'       => 'Category Color',
        'description' => 'Assign a brand color to this category.',
        'default'     => '#333333',
    ],
    [
        'id'          => 'demo_cat_order',
        'type'        => 'number',
        'label'       => 'Display Order',
        'description' => 'Lower numbers appear first.',
        'default'     => '0',
        'attributes'  => ['min' => '0'],
    ],
]);

add_custom_taxonomy_meta('post_tag', [
    [
        'id'          => 'demo_tag_description',
        'type'        => 'textarea',
        'label'       => 'Extended Description',
        'description' => 'A longer description for this tag (shown on tag archive).',
    ],
]);


// ──────────────────────────────────────────────────────────────────────────────
// USER PROFILE META
// Demonstrates: add_custom_user_meta()
// ──────────────────────────────────────────────────────────────────────────────
add_custom_user_meta([
    [
        'id'          => 'demo_user_twitter',
        'type'        => 'url',
        'label'       => 'Twitter/X Profile',
        'description' => 'Full URL to your Twitter/X profile.',
        'attributes'  => ['placeholder' => 'https://x.com/username'],
    ],
    [
        'id'          => 'demo_user_linkedin',
        'type'        => 'url',
        'label'       => 'LinkedIn Profile',
        'attributes'  => ['placeholder' => 'https://linkedin.com/in/username'],
    ],
    [
        'id'          => 'demo_user_phone',
        'type'        => 'text',
        'label'       => 'Phone Number',
        'attributes'  => ['placeholder' => '+1 (555) 123-4567'],
    ],
    [
        'id'          => 'demo_user_avatar',
        'type'        => 'file',
        'label'       => 'Custom Avatar',
        'button_text' => 'Upload Avatar',
        'preview'     => 'image',
    ],
    [
        'id'          => 'demo_user_department',
        'type'        => 'select',
        'label'       => 'Department',
        'options'     => [
            ''            => '— Select —',
            'engineering' => 'Engineering',
            'design'      => 'Design',
            'marketing'   => 'Marketing',
            'sales'       => 'Sales',
            'support'     => 'Support',
        ],
    ],
]);


// ──────────────────────────────────────────────────────────────────────────────
// OPTIONS PAGE
// Demonstrates: add_custom_options_page() for global site settings
// ──────────────────────────────────────────────────────────────────────────────
add_custom_options_page(
    'cmb-demo-settings',
    'Demo Plugin Settings',
    'Demo Settings',
    [
        [
            'id'          => 'demo_site_logo',
            'type'        => 'file',
            'label'       => 'Site Logo',
            'description' => 'Upload your site logo.',
            'button_text' => 'Choose Logo',
            'preview'     => 'image',
        ],
        [
            'id'          => 'demo_footer_text',
            'type'        => 'textarea',
            'label'       => 'Footer Text',
            'description' => 'Text displayed in the site footer.',
            'default'     => '© ' . date('Y') . ' My Site. All rights reserved.',
        ],
        [
            'id'          => 'demo_primary_color',
            'type'        => 'color',
            'label'       => 'Primary Color',
            'default'     => '#0073aa',
        ],
        [
            'id'          => 'demo_analytics_id',
            'type'        => 'text',
            'label'       => 'Analytics ID',
            'description' => 'Google Analytics measurement ID (e.g. G-XXXXXXXXXX).',
            'attributes'  => ['placeholder' => 'G-XXXXXXXXXX'],
        ],
        [
            'id'      => 'demo_maintenance_mode',
            'type'    => 'checkbox',
            'label'   => 'Maintenance Mode',
        ],
    ],
    'manage_options'
);

// Sub-menu page under the demo settings
add_custom_options_page(
    'cmb-demo-advanced-settings',
    'Advanced Settings',
    'Advanced',
    [
        [
            'id'          => 'demo_api_endpoint',
            'type'        => 'url',
            'label'       => 'API Endpoint',
            'description' => 'External API base URL.',
        ],
        [
            'id'    => 'demo_api_key',
            'type'  => 'password',
            'label' => 'API Key',
        ],
        [
            'id'          => 'demo_cache_ttl',
            'type'        => 'number',
            'label'       => 'Cache TTL (seconds)',
            'default'     => '3600',
            'attributes'  => ['min' => '0', 'step' => '1'],
        ],
    ],
    'manage_options',
    'cmb-demo-settings'  // Parent slug — makes this a sub-page
);
