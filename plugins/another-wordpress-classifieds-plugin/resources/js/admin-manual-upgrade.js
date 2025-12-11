AWPCP.run('awpcp/init-asynchronous-tasks', ['jquery', 'awpcp/settings', 'awpcp/asynchronous-tasks'],
function($, settings, AsynchronousTasks) {
    $(function(){
        var element = $('.awpcp-asynchronous-tasks-container');
        if (element.length) {
            var params = settings.get('asynchronous-tasks-params'),
                widget = new AsynchronousTasks( params );

            widget.render(element);
        }
    });
});
