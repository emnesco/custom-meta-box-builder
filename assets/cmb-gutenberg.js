(function() {
    'use strict';

    const el = wp.element.createElement;
    const PluginDocumentSettingPanel = (wp.editor && wp.editor.PluginDocumentSettingPanel)
        || wp.editPost.PluginDocumentSettingPanel;
    const { TextControl, TextareaControl, SelectControl, ToggleControl, RadioControl, ColorPicker, DatePicker, Button, BaseControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { registerPlugin } = wp.plugins;

    if (typeof cmbGutenbergPanels === 'undefined' || !cmbGutenbergPanels.length) {
        return;
    }

    function CmbField(props) {
        const { field } = props;

        const meta = useSelect((select) => {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        });

        const { editPost } = useDispatch('core/editor');

        const value = meta[field.id] !== undefined ? meta[field.id] : (field.default || '');

        const onChange = (newValue) => {
            editPost({ meta: { [field.id]: newValue } });
        };

        switch (field.type) {
            case 'textarea':
                return el(TextareaControl, {
                    label: field.label,
                    help: field.description,
                    value: value,
                    onChange: onChange
                });

            case 'select': {
                const options = [{ label: '\u2014 Select \u2014', value: '' }];
                if (field.options) {
                    Object.keys(field.options).forEach((key) => {
                        options.push({ label: field.options[key], value: key });
                    });
                }
                return el(SelectControl, {
                    label: field.label,
                    help: field.description,
                    value: value,
                    options: options,
                    onChange: onChange
                });
            }

            case 'checkbox':
                return el(ToggleControl, {
                    label: field.label,
                    help: field.description,
                    checked: !!value,
                    onChange: (checked) => onChange(checked ? '1' : '')
                });

            case 'number':
                return el(TextControl, {
                    label: field.label,
                    help: field.description,
                    type: 'number',
                    value: value,
                    onChange: onChange
                });

            case 'email':
                return el(TextControl, {
                    label: field.label,
                    help: field.description,
                    type: 'email',
                    value: value,
                    onChange: onChange
                });

            case 'url':
                return el(TextControl, {
                    label: field.label,
                    help: field.description,
                    type: 'url',
                    value: value,
                    onChange: onChange
                });

            case 'radio': {
                const radioOptions = [];
                if (field.options) {
                    Object.keys(field.options).forEach((key) => {
                        radioOptions.push({ label: field.options[key], value: key });
                    });
                }
                return el(RadioControl, {
                    label: field.label,
                    help: field.description,
                    selected: value,
                    options: radioOptions,
                    onChange: onChange
                });
            }

            case 'color':
                return el(BaseControl, { label: field.label, help: field.description },
                    el(ColorPicker, {
                        color: value || '#000000',
                        onChangeComplete: (color) => onChange(color.hex),
                        disableAlpha: !field.alpha
                    })
                );

            case 'date':
                return el(BaseControl, { label: field.label, help: field.description },
                    el(DatePicker, {
                        currentDate: value || undefined,
                        onChange: (date) => onChange(date ? date.split('T')[0] : '')
                    })
                );

            case 'toggle':
                return el(ToggleControl, {
                    label: field.label,
                    help: field.description,
                    checked: !!value && value !== '0',
                    onChange: (checked) => onChange(checked ? '1' : '0')
                });

            case 'file':
            case 'image':
                return el(BaseControl, { label: field.label, help: field.description },
                    el(Button, {
                        variant: 'secondary',
                        onClick: () => {
                            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') return;
                            const frame = wp.media({ title: 'Select File', multiple: false });
                            frame.on('select', () => {
                                const attachment = frame.state().get('selection').first().toJSON();
                                onChange(String(attachment.id));
                            });
                            frame.open();
                        }
                    }, value ? 'Change (ID: ' + value + ')' : 'Select File')
                );

            default:
                return el(TextControl, {
                    label: field.label,
                    help: field.description,
                    value: value,
                    onChange: onChange
                });
        }
    }

    cmbGutenbergPanels.forEach((panel) => {
        const pluginName = 'cmb-panel-' + panel.id;

        registerPlugin(pluginName, {
            render: () => {
                return el(
                    PluginDocumentSettingPanel,
                    {
                        name: pluginName,
                        title: panel.title,
                        className: 'cmb-gutenberg-panel'
                    },
                    panel.fields.map((field) => {
                        return el(CmbField, { key: field.id, field: field });
                    })
                );
            }
        });
    });
})();
