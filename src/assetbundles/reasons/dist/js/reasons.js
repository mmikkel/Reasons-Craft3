(function () {

    if (!window.Craft) {
        return;
    }

    Craft.ReasonsPlugin = {

        ASSET_SOURCE_HANDLE: 'assetSource',
        CATEGORY_GROUP_HANDLE: 'categoryGroup',
        TAG_GROUP_HANDLE: 'tagGroup',
        GLOBAL_SET_HANDLE: 'globalSet',
        ENTRY_TYPE_HANDLE: 'entryType',
        SECTION_HANDLE: 'section',
        USERS_HANDLE: 'users',
        FIELDS_HANDLE: 'field',

        ASSET_ACTION: 'assets/save-asset',
        ASSET_SOURCE_ACTION: 'volumes/save-volume',
        CATEGORY_ACTION: 'categories/save-category',
        CATEGORY_GROUP_ACTION: 'categories/save-group',
        GLOBAL_SET_CONTENT_ACTION: 'globals/save-content',
        GLOBAL_SET_ACTION: 'globals/save-set',
        ENTRY_ACTION: 'entries/save-entry',
        ENTRY_REVISION_ACTION: 'entry-revisions/save-draft',
        ENTRY_TYPE_ACTION: 'sections/save-entry-type',
        USERS_ACTION: 'users/save-user',
        USERS_FIELDS_ACTION: 'users/save-field-layout',
        FIELDS_ACTION: 'fields/save-field',

        RENDER_CONTEXT: 'render',
        LAYOUT_DESIGNER_CONTEXT: 'fld',


        /*
        *   Initialize Reasons
        *
        */
        init: function (data) {
            this.data = data;
            this.initPrimaryForm();
        },

        /*
        *   Init the primary form. This can be an FLD form, a field designer form or an element editing form
        *
        */
        initPrimaryForm: function () {
            this.destroyPrimaryForm();
            Garnish.requestAnimationFrame((function () {
                var $form = (Craft.cp.$primaryForm && Craft.cp.$primaryForm.length) ? Craft.cp.$primaryForm : $('#content form:first');
                var primaryForm = this.initForm($form);
                if (!primaryForm && this.data.renderContext) {
                    // Probably a revision running in the new DraftEditor, in which case there's not actually a <form> in the DOM
                    // There's an ugly piece of PHP in the ReasonsAssetBundle that accounts for this and figures out what the context is though! yolo
                    var conditionals = this.getConditionals(this.data.renderContext);
                    if (conditionals) {
                        primaryForm = new Craft.ReasonsPlugin.ConditionalsRenderer($('body'), conditionals);
                    }
                }
                this.primaryForm = primaryForm;
            }).bind(this));
        },

        destroyPrimaryForm: function () {
            if (this.primaryForm) {
                this.primaryForm.destroy();
                delete this.primaryForm;
            }
        },

        /*
        *   Element editor
        *
        */
        initElementEditor: function (conditionalsKey) {

            var conditionals = this.getConditionals(conditionalsKey);

            if (!conditionals) {
                return false;
            }

            var now = new Date().getTime(),
                doInitElementEditor = (function () {

                    var timestamp = new Date().getTime(),
                        $elementEditor = $('.elementeditor:last'),
                        $hud = $elementEditor.length > 0 ? $elementEditor.closest('.hud') : false,
                        elementEditor = $hud && $hud.length > 0 ? $hud.data('elementEditor') : false,
                        $form = elementEditor ? elementEditor.$form : false;

                    if ($form) {
                        elementEditor['_reasonsForm'] = new this.ConditionalsRenderer($form, conditionals);
                        elementEditor.hud.on('hide', $.proxy(this.destroyElementEditorForm, this, elementEditor));
                    } else if (timestamp - now < 2000) { // Poll for 2 secs
                        Garnish.requestAnimationFrame(doInitElementEditor);
                    }

                }).bind(this);

            doInitElementEditor();

        },

        destroyElementEditorForm: function (elementEditor) {
            var form = elementEditor._reasonsForm || null;
            if (form) {
                form.destroy();
                delete elementEditor._reasonsForm;
            }
        },

        /*
        *   Init form
        *
        */
        initForm: function ($form) {

            if (!$form || !$form.length) {
                return null;
            }

            var formData = this.getElementSourceFromForm($form);
            if (!formData) {
                return null;
            }

            var context = this.getFormContext($form);
            if (!context) {
                return null;
            }

            var conditionals = this.getConditionals(formData.type + (formData.id ? ':' + formData.id : ''));

            if (context === this.LAYOUT_DESIGNER_CONTEXT) {
                return new this.FieldLayoutDesigner($form, conditionals);
            } else if (context === this.RENDER_CONTEXT) {
                if (!conditionals) {
                    return null;
                }
                return new Craft.ReasonsPlugin.ConditionalsRenderer($form, conditionals);
            }

            return null;

        },

        /*
        *   Core methods
        *
        */
        getConditionals: function (key) {
            return key ? (this.data.conditionals && this.data.conditionals.hasOwnProperty(key) ? this.data.conditionals[key] : null) : (this.data.conditionals || {});
        },

        getToggleFields: function () {
            return this.data.toggleFields ? this.data.toggleFields : [];
        },

        getToggleFieldById: function (fieldId) {
            fieldId = parseInt(fieldId);
            var toggleFields = this.getToggleFields(),
                numToggleFields = toggleFields.length;
            for (var i = 0; i < numToggleFields; ++i) {
                if (parseInt(toggleFields[i].id) === fieldId) {
                    return toggleFields[i];
                }
            }
            return false;
        },

        getFieldIds: function () {
            return this.data.fieldIds ? this.data.fieldIds : {};
        },

        getFieldIdByHandle: function (fieldHandle) {
            var fieldIds = this.getFieldIds();
            return fieldIds && fieldIds.hasOwnProperty(fieldHandle) ? fieldIds[fieldHandle] : false;
        },

        getToggleFieldTypes: function () {
            return this.data.toggleFieldTypes ? this.data.toggleFieldTypes : [];
        },

        getElementSourceFromForm: function ($form) {

            if ($form.data('elementEditor')) {
                return false;
            }

            // Get namespace
            var namespace = $form.find('input[type="hidden"][name="namespace"]').val();
            if (namespace) {
                namespace += '-';
            }

            var action = $form.find('input[type="hidden"][name="action"]').val(),
                type,
                idInputSelector;

            // If this is the new DraftEditor, there might not actually be a <input name="action"/> in the markup
            if (!action && !!window.draftEditor) {
                action = window.draftEditor.settings.saveDraftAction;
            }

            switch (action) {

                case this.ASSET_ACTION:
                case this.ASSET_SOURCE_ACTION :
                    type = this.ASSET_SOURCE_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="volumeId"]';
                    break;

                case this.CATEGORY_ACTION :
                case this.CATEGORY_GROUP_ACTION :
                    type = this.CATEGORY_GROUP_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="groupId"]';
                    break;

                case this.GLOBAL_SET_CONTENT_ACTION:
                case this.GLOBAL_SET_ACTION :
                    type = this.GLOBAL_SET_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="setId"]';
                    break;

                case this.ENTRY_ACTION :
                case this.ENTRY_REVISION_ACTION :
                    var $entryType = $form.find('select#entryType, input[type="hidden"][name="entryTypeId"], input[type="hidden"][name="typeId"], #' + namespace + 'entryType');
                    type = $entryType.length ? this.ENTRY_TYPE_HANDLE : this.SECTION_HANDLE;
                    idInputSelector = $entryType.length ? 'select#entryType, input[type="hidden"][name="entryTypeId"], input[type="hidden"][name="typeId"], #' + namespace + 'entryType' : 'input[type="hidden"][name="sectionId"], #' + namespace + 'section';
                    break;

                case this.ENTRY_TYPE_ACTION :
                    type = this.ENTRY_TYPE_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="entryTypeId"]';
                    break;

                case this.USERS_ACTION :
                case this.USERS_FIELDS_ACTION :
                    type = this.USERS_HANDLE;
                    break;

            }

            if (!type) {
                return false;
            }

            return {
                type: type,
                id: idInputSelector ? ($form.find(idInputSelector).val() | 0) : false
            };

        },

        getFormContext: function ($form) {

            if ($form.data('elementEditor')) {
                return false;
            }

            var action = $form.find('input[type="hidden"][name="action"]').val();

            if (!action && !!window.draftEditor) {
                action = window.draftEditor.settings.saveDraftAction;
            }

            switch (action) {

                case this.ASSET_ACTION :
                case this.GLOBAL_SET_CONTENT_ACTION :
                case this.ENTRY_ACTION :
                case this.ENTRY_REVISION_ACTION :
                case this.CATEGORY_ACTION :
                case this.USERS_ACTION :
                    return this.RENDER_CONTEXT;

                case this.ASSET_SOURCE_ACTION :
                case this.CATEGORY_GROUP_ACTION :
                case this.GLOBAL_SET_ACTION :
                case this.ENTRY_TYPE_ACTION :
                case this.USERS_FIELDS_ACTION :
                    return this.LAYOUT_DESIGNER_CONTEXT;

            }

            return false;

        }

    };

})();

if (window.jQuery) {
    /*!
     * jQuery.fn.hasAttr()
     *
     * Copyright 2011, Rick Waldron
     * Licensed under MIT license.
     *
     */
    (function (jQuery) {
        jQuery.fn.hasAttr = function (name) {
            for (var i = 0, l = this.length; i < l; i++) {
                if (!!(this.attr(name) !== undefined)) {
                    return true;
                }
            }
            return false;
        };
    })(jQuery);
}
