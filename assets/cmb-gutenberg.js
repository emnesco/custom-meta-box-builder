(function() {
    'use strict';

    var el = wp.element.createElement;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var registerPlugin = wp.plugins.registerPlugin;

    if (typeof cmbGutenbergPanels === 'undefined' || !cmbGutenbergPanels.length) {
        return;
    }

    function CmbField(props) {
        var field = props.field;
        var postId = useSelect(function(select) {
            return select('core/editor').getCurrentPostId();
        });

        var meta = useSelect(function(select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        });

        var editPost = useDispatch('core/editor').editPost;

        var value = meta[field.id] !== undefined ? meta[field.id] : (field.default || '');

        function onChange(newValue) {
            var newMeta = {};
            newMeta[field.id] = newValue;
            editPost({ meta: newMeta });
        }

        switch (field.type) {
            case 'textarea':
                return el(TextareaControl, {
                    label: field.label,
                    help: field.description,
                    value: value,
                    onChange: onChange
                });

            case 'select':
                var options = [{ label: '— Select —', value: '' }];
                if (field.options) {
                    Object.keys(field.options).forEach(function(key) {
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

            case 'checkbox':
                return el(ToggleControl, {
                    label: field.label,
                    help: field.description,
                    checked: !!value,
                    onChange: function(checked) { onChange(checked ? '1' : ''); }
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

            default:
                return el(TextControl, {
                    label: field.label,
                    help: field.description,
                    value: value,
                    onChange: onChange
                });
        }
    }

    cmbGutenbergPanels.forEach(function(panel) {
        var pluginName = 'cmb-panel-' + panel.id;

        registerPlugin(pluginName, {
            render: function() {
                return el(
                    PluginDocumentSettingPanel,
                    {
                        name: pluginName,
                        title: panel.title,
                        className: 'cmb-gutenberg-panel'
                    },
                    panel.fields.map(function(field) {
                        return el(CmbField, { key: field.id, field: field });
                    })
                );
            }
        });
    });
})();
