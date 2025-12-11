/*global AWPCP*/

AWPCP.define('awpcp/asynchronous-tasks-group', [
    'jquery',
    'knockout',
    'moment',
    'awpcp/asynchronous-task'
],
function($, ko, moment, AsynchronousTask) {

    var AsynchronousTasksGroup = function( params ) {
        this.title = ko.observable( params.title );
        this.content = ko.observable( params.content );

        this.successTitle = ko.observable( params.successTitle );
        this.successContent = ko.observable( params.successContent );

        this.tasks = ko.observableArray( params.tasks );

        this.startTime = ko.observable( null );
        this.lastUpdatedTime = ko.observable( null );

        this.tasksCount = this.tasks().length;
        this.currentTaskIndex = ko.observable( 0 );
        this.tasksCompleted = ko.observable( 0 );

        this.tasksLeft = ko.computed( function() {
            return this.tasksCount - this.tasksCompleted();
        }, this );

        this.running = ko.observable( false );
        this.completed = ko.computed( function() {
            return this.tasksLeft() === 0;
        }, this );

        this.percentageOfCompletion = ko.computed(function() {
            var tasks = this.tasks(),
                totalPoints = 0,
                completedPoints = 0;

            $.each( tasks, function( index, task ) {
                totalPoints += task.getWeight();

                if ( task.isCompleted() ) {
                    completedPoints += task.getWeight();
                } else {
                    completedPoints += task.getWeight() * task.getPercentageOfCompletion() / 100;
                }
            } );

            return Math.round( ( completedPoints / totalPoints ) * 10000 ) / 100;
        }, this).extend({ throttle: 1 });

        this.percentageOfCompletionString = ko.computed(function() {
            return Math.round( this.percentageOfCompletion() ) + '%';
        }, this);

        this.updateCurrentTaskIndexAndCompletedTasksCount();
    };

    $.extend( AsynchronousTasksGroup.prototype, AsynchronousTask.prototype, {
        updateCurrentTaskIndexAndCompletedTasksCount: function() {
            var currentTaskIndex = this.currentTaskIndex(),
                tasksCompleted = this.tasksCompleted();

            $.each( this.tasks(), function( index, task ) {
                if ( task.completed() ) {
                    currentTaskIndex = currentTaskIndex + 1;
                    tasksCompleted = tasksCompleted + 1;
                }
            } );

            this.tasks.sort(function(a, b) {
                if (a.completed() < b.completed())  {
                    return 1;
                }
                if (a.completed() == b.completed()) {
                    return 0;
                }
                return -1;
            });

            this.currentTaskIndex( currentTaskIndex );
            this.tasksCompleted( tasksCompleted );
        },

        getRemainingTime: function() {
            return this.remainingTime;
        },

        getPercentageOfCompletion: function() {
            return this.percentageOfCompletion();
        },

        getWeight: function() {
            return this.tasksCount;
        },

        execute: function( done ) {
            var group = this, index = group.currentTaskIndex();

            if ( index >= group.tasksCount ) {
                group.running( false );

                if ( $.isFunction( done ) ) {
                    return done();
                }
            } else {
                group.tasks()[ index ].execute( function() {
                    group.currentTaskIndex( group.currentTaskIndex() + 1 );
                    group.tasksCompleted( group.tasksCompleted() + 1 );

                    setTimeout( function() {
                        group.execute( done );
                    }, 1 );
                } );
            }

            group.running( true );
        }
    } );

    return AsynchronousTasksGroup;
});
