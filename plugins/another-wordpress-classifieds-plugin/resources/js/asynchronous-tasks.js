/*global AWPCP*/

AWPCP.define( 'awpcp/asynchronous-tasks', [
    'jquery',
    'knockout',
    'moment',
    'awpcp/asynchronous-task',
    'awpcp/asynchronous-tasks-group',
    'awpcp/knockout-progress'
],
function($, ko, moment, AsynchronousTask, AsynchronousTasksGroup) {

    function AsynchronousTasks( params ) {
        this.title = ko.observable( params.title );
        this.introduction = ko.observable( params.introduction );
        this.submit = ko.observable( params.submit );
        this.templates = params.templates;

        this.group = new AsynchronousTasksGroup({
            tasks: this._getTasksGroups( params )
        });ko.observableArray([]);
    }

    $.extend(AsynchronousTasks.prototype, {
        _getTasksGroups: function( params ) {
            var self = this, groups = [];

            $.each( params.groups, function( index, group ) {
                groups.push( new AsynchronousTasksGroup( {
                    title: group.title,
                    content: group.content,
                    successTitle: group.successTitle,
                    successContent: group.successContent,
                    tasks: self._getTasks( group )
                } ) );
            } );

            return groups;
        },

        _getTasks: function( group ) {
            var self = this, tasks = [];

            $.each( group.tasks, function( index, task ) {
                tasks.push( new AsynchronousTask( {
                    name: task.name,
                    description: task.description,
                    action: task.action,
                    context: task.context,
                    recordsCount: task.recordsCount,
                    recordsLeft: task.recordsLeft,
                    templates: self.templates
                } ) );
            } );

            return tasks;
        },

        render: function(element) {
            ko.applyBindings(this, $(element).get(0));
        },

        start: function() {
            this.group.execute();
        }
    });

    return AsynchronousTasks;
});
