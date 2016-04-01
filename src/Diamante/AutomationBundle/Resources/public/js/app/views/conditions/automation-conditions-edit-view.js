define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'tpl!diamanteautomation/js/app/templates/conditions/automation-conditions-edit-template.ejs',
    'diamanteautomation/js/app/views/abstract/view'
],function ($, _, mediator, AutomationConditionsEditTemplate, AbstractView) {
    'use strict';

    var AutomationConditionsEditView = AbstractView.extend({
        autoRender: true,
        template : AutomationConditionsEditTemplate,

        listen: {
            'addedToParent' : 'onAdd',
            'change:entity model': 'entityChanged',
            'change model': 'render'
        },

        events: {
            'click button[data-action="delete"]': 'removeItem',
            'change :input' : 'change'
        },

        render: function () {
            AbstractView.prototype.render.apply(this, arguments);
            this.$(':input:not(button)').trigger('change');
            if(this.model.collection.length == 1){
                this.$('button[data-action="delete"]').hide();
            }
            this.onAdd();
            return this;
        },

        change: function (e) {
            var input = $(e.target),
                model = this.model,
                relAttr = input.data('rel-attr');
            if(model.get(input.data('attr')) != input.val() && relAttr ){
                _.each(relAttr.split(','), function(attr){
                    model.unset(attr, {silent: true});
                });
            }
            model.set( input.data('attr'), input.val() );
        },

        entityChanged: function(model, attr){
            this.options.target = attr;
            this.model.unset('value', {silent: true});
        },

        removeItem: function(){
            var success = this.model.destroy.bind(this.model);
            this.$el.animate({ opacity: 0 }, 500, success);
        },

        onAdd: function(){
            if(this.$el.is(':visible')){
                mediator.execute('layout:init', this.$el)
            }
        }

    });

    return AutomationConditionsEditView;
});