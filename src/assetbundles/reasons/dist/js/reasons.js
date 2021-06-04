/** global: Craft */
/** global: Garnish */
/** global: $ */

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

        ASSET_SAVE_ACTION: 'assets/save-asset',
        VOLUME_SAVE_ACTION: 'volumes/save-volume',
        CATEGORY_SAVE_ACTION: 'categories/save-category',
        CATEGORY_GROUP_SAVE_ACTION: 'categories/save-group',
        GLOBAL_SET_CONTENT_SAVE_ACTION: 'globals/save-content',
        GLOBAL_SET_SAVE_ACTION: 'globals/save-set',
        ENTRY_SAVE_ACTION: 'entries/save-entry',
        DRAFT_SAVE_ACTION: 'entry-revisions/save-draft',
        DRAFT_PUBLISH_ACTION: 'entry-revisions/publish-draft',
        ENTRY_TYPE_SAVE_ACTION: 'sections/save-entry-type',
        USERS_SAVE_ACTION: 'users/save-user',
        USERS_FIELDS_SAVE_ACTION: 'users/save-field-layout',
        TAG_SAVE_ACTION: 'tags/save-tag',
        TAG_GROUP_SAVE_ACTION: 'tags/save-tag-group',
        FIELDS_SAVE_ACTION: 'fields/save-field',

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

                    var timestamp = new Date().getTime();
                    var $elementEditor = $('.elementeditor:last,.element-editor:last');

                    var formInitialised = false;

                    if ($elementEditor.length && $elementEditor.hasClass('elementeditor')) {
                        // Craft 3.6
                        var $hud = $elementEditor.length > 0 ? $elementEditor.closest('.hud') : false;
                        var elementEditor = $hud && $hud.length > 0 ? $hud.data('elementEditor') : false;
                        var $form = elementEditor ? elementEditor.$form : false;
                        if ($form) {
                            elementEditor['_reasonsForm'] = new this.ConditionalsRenderer($form, conditionals);
                            elementEditor.hud.on('hide', $.proxy(this.destroyElementEditorForm, this, elementEditor));
                        }
                        formInitialised = true;
                    } else {
                        // Craft 3.7 (Slideouts)
                        var elementEditor = $elementEditor.data('elementEditor');
                        if (elementEditor) {
                            elementEditor['_reasonsForm'] = new this.ConditionalsRenderer($elementEditor, conditionals);
                            elementEditor.on('hideHud', function () {
                                $.proxy(this.destroyElementEditorForm, this, elementEditor);
                            });
                        }
                        formInitialised = true;
                    }

                    if (!formInitialised && timestamp - now < 2000) {
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

                case this.ASSET_SAVE_ACTION:
                case this.VOLUME_SAVE_ACTION :
                    type = this.ASSET_SOURCE_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="volumeId"]';
                    break;

                case this.CATEGORY_SAVE_ACTION :
                case this.CATEGORY_GROUP_SAVE_ACTION :
                    type = this.CATEGORY_GROUP_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="groupId"]';
                    break;

                case this.GLOBAL_SET_CONTENT_SAVE_ACTION:
                case this.GLOBAL_SET_SAVE_ACTION :
                    type = this.GLOBAL_SET_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="setId"]';
                    break;

                case this.ENTRY_SAVE_ACTION :
                case this.DRAFT_SAVE_ACTION :
                case this.DRAFT_PUBLISH_ACTION :
                    var $entryType = $form.find('select#entryType, input[type="hidden"][name="entryTypeId"], input[type="hidden"][name="typeId"], #' + namespace + 'entryType');
                    type = $entryType.length ? this.ENTRY_TYPE_HANDLE : this.SECTION_HANDLE;
                    idInputSelector = $entryType.length ? 'select#entryType, input[type="hidden"][name="entryTypeId"], input[type="hidden"][name="typeId"], #' + namespace + 'entryType' : 'input[type="hidden"][name="sectionId"], #' + namespace + 'section';
                    break;

                case this.ENTRY_TYPE_SAVE_ACTION :
                    type = this.ENTRY_TYPE_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="entryTypeId"]';
                    break;

                case this.USERS_SAVE_ACTION :
                case this.USERS_FIELDS_SAVE_ACTION :
                    type = this.USERS_HANDLE;
                    break;

                case this.TAG_SAVE_ACTION :
                case this.TAG_GROUP_SAVE_ACTION :
                    type = this.TAG_GROUP_HANDLE;
                    idInputSelector = 'input[type="hidden"][name="tagGroupId"]';
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

                case this.ASSET_SAVE_ACTION :
                case this.GLOBAL_SET_CONTENT_SAVE_ACTION :
                case this.ENTRY_SAVE_ACTION :
                case this.DRAFT_SAVE_ACTION :
                case this.DRAFT_PUBLISH_ACTION :
                case this.CATEGORY_SAVE_ACTION :
                case this.USERS_SAVE_ACTION :
                case this.TAG_SAVE_ACTION :
                    return this.RENDER_CONTEXT;

                case this.VOLUME_SAVE_ACTION :
                case this.CATEGORY_GROUP_SAVE_ACTION :
                case this.GLOBAL_SET_SAVE_ACTION :
                case this.ENTRY_TYPE_SAVE_ACTION :
                case this.USERS_FIELDS_SAVE_ACTION :
                case this.TAG_GROUP_SAVE_ACTION :
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
