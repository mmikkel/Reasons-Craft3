(function () {

    if (!window.Craft || !Craft.ReasonsPlugin) {
        return;
    }

    Craft.ReasonsPlugin.FieldLayoutDesigner = function ($el, conditionals) {

        if (!$el || !$el.length) {
            return;
        }

        var ConditionalsBuilder = Craft.ReasonsPlugin.ConditionalsBuilder;

        this.$el = $el;

        this.conditionals = conditionals;

        this.settings = {
            fieldSettingsSelector: 'a.settings',
            fieldSelector: '.fld-field',
            tabSelector: '.fld-tabs .fld-tab'
        };

        this.templates = {
            input: function (settings) {
                return '<input type="' + settings.type + '" name="' + (settings.name || '') + '" value="' + (settings.value || '') + '" />';
            },
            modal: function () {
                return '<div class="modal elementselectormodal reasonsModal"><div class="body" /><div class="footer"><div class="buttons rightalign first"><div class="btn close submit">Done</div></div></div></div>';
            }
        };

        this.init = function() {

            // Get available toggle field IDs
            var toggleFields = Craft.ReasonsPlugin.getToggleFields();
            this.toggleFieldIds = $.map(toggleFields, function (toggleField) {
                return parseInt(toggleField.id, 10);
            });

            // This hidden input will store our serialized conditionals
            this.$conditionalsInput = $(this.templates.input({
                name: '_reasons',
                type: 'hidden'
            }));

            // This hidden input stores the conditional's ID
            this.$conditionalsIdInput = $(this.templates.input({
                name: '_reasonsId',
                value: this.id || '',
                type: 'hidden'
            }));

            // Append the hidden input fields
            this.$el
                .append(this.$conditionalsInput)
                .append(this.$conditionalsIdInput)
                // Attach submit event listener
                .on('submit', $.proxy(this.onFormSubmit, this));

            // Defer refresh to RAF
            Garnish.requestAnimationFrame($.proxy(function () {
                this.refresh();
            }, this));

            this.$el.on('mousedown', this.settings.fieldSelector, $.proxy(this.onFieldMouseDown, this));

            // Listen for field settings HUD creations
            Garnish.on(Craft.FieldLayoutDesigner.Element, 'createSettingsHud', this.onElementSettingsHudCreation.bind(this));
        }

        this.destroy = function() {
            this.$el.off('mousedown', this.settings.fieldSelector, $.proxy(this.onFieldMouseDown, this));
        }

        this.refresh = function() {

            var self = this;
            var conditionals = {};
            var $fields;
            var $field;
            var fieldId;
            var toggleFields;

            // Loop over tabs
            this.$el.find(this.settings.tabSelector).each(function () {

                // Get all fields for this tab
                $fields = $(this).find(self.settings.fieldSelector);

                // Get all toggle fields for this tab
                toggleFields = [];
                $fields.each(function () {
                    $field = $(this);
                    fieldId = parseInt($field.data('id'));
                    if (self.toggleFieldIds.indexOf(fieldId) > -1) {
                        var toggleField = Craft.ReasonsPlugin.getToggleFieldById(fieldId);
                        if (toggleField) {
                            toggleFields.push(toggleField);
                        }
                    }
                });

                // Loop over fields
                $fields.each(function () {

                    $field = $(this);
                    fieldId = parseInt($field.data('id'));

                    if (!$field.data('_reasonsBuilder')) {

                        // Create builder
                        $field.data('_reasonsBuilder', new ConditionalsBuilder({
                            fieldId: fieldId,
                            toggleFields: toggleFields,
                            rules: self.conditionals && self.conditionals.hasOwnProperty(fieldId) ? self.conditionals[fieldId] : null
                        }));

                    } else {

                        // Refresh builder
                        $field.data('_reasonsBuilder').update({
                            toggleFields: toggleFields
                        });

                    }

                    // Get rules
                    var rules = $field.data('_reasonsBuilder').getConditionals();
                    if (rules) {
                        conditionals[fieldId] = rules;
                        $field.addClass('reasonsHasConditionals');
                    } else {
                        $field.removeClass('reasonsHasConditionals');
                    }

                });

            });

            if (Object.keys(conditionals).length === 0) {
                this.$conditionalsInput.attr('value', '');
            } else {
                this.$conditionalsInput.attr('value', JSON.stringify(conditionals));
            }
        }

        /*
        *   Event handlers
        *
        */
        this.onFieldMouseDown = function(e) {
            var self = this,
                mouseUpHandler = function (e) {
                    $('body').off('mouseup', mouseUpHandler);
                    Garnish.requestAnimationFrame(function () {
                        self.refresh();
                    });
                };

            $('body').on('mouseup', mouseUpHandler);
        }

        this.onElementSettingsHudCreation = function(e) {
            /** @type {Craft.FieldLayoutDesigner.Element} */
            let element = e.target;

            // Ignore if this isn't a custom field
            if (element.config.type !== 'craft\\fieldlayoutelements\\CustomField') {
                return;
            }

            let $btn = $('<div class="btn"></div>')
                .text(Craft.t('reasons', 'Edit conditionals'))
                .on('click', {
                    element: element,
                }, this.showModal.bind(this));

            element.hud.$footer.append(
                $('<div class="buttons left"></div>')
                    .append($btn)
            );
        };

        this.showModal = function(e) {
            /** @type {Craft.FieldLayoutDesigner.Element} */
            let element = e.data.element;
            let $field = element.$container;

            if (!$field.data('_reasonsModal')) {

                // Create modal
                var self = this,
                    builder = $field.data('_reasonsBuilder'),
                    $modal = $(this.templates.modal()),
                    modal = new Garnish.Modal($modal, {
                        resizable: true,
                        autoShow: false,
                        onShow: function () {
                            Garnish.requestAnimationFrame(function () {
                                self.refresh();
                            });
                        },
                        onHide: function () {
                            Garnish.requestAnimationFrame(function () {
                                self.refresh();
                            });
                        }
                    });

                // Add builder to modal
                builder.get().appendTo($modal.find('.body'));

                $modal.on('click', '.close', function (e) {
                    modal.hide();
                });

                $field.data('_reasonsModal', modal);

            }

            $field.data('_reasonsModal').show();
        };

        this.onFormSubmit = function() {
            this.refresh();
        }

        this.init();

    };

})();
