<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>

<div class="awpcp-asynchronous-tasks-container">
    <!--<div data-bind="if: message, css: { 'awpcp-updated': message, updated: message }"><p data-bind="html: message"></p></div>-->
    <!--<div data-bind="if: error, css: { 'awpcp-updated': error, updated: error, error: error }"><p data-bind="html: error"></p></div>-->

    <div data-bind="ifnot: group.completed">
        <?php
            awpcp_html_admin_second_level_heading( array(
                'attributes' => array(
                    'data-bind' => 'html: title, visible: title',
                ),
                'echo'       => true,
            ) );
        ?>
        <p data-bind="html: introduction"></p>
    </div>

    <ul data-bind="foreach: group.tasks">
        <li>
            <div data-bind="if: tasks">
                <?php
                    awpcp_html_admin_third_level_heading( array(
                        'attributes' => array(
                            'data-bind' => 'text: title, visible: title',
                        ),
                        'echo'       => true,
                    ) );
                ?>
                <div data-bind="html: content, visible: content"></div>
                <ol class="awpcp-asynchronous-tasks" data-bind="foreach: tasks">
                    <li>
                        <span data-bind="if: numberOfRecordsProcessed">
                            <span data-bind="text: name"></span> &mdash; (<span data-bind="text: percentageOfCompletionString"></span>)<span data-bind="if: numberOfRecordsProcessed"> <span data-bind="text: numberOfRecordsProcessedMessage"></span></span><span data-bind="if: !completed() && remainingTime()"> (<span data-bind="text: remainingTime"></span> <span data-bind="text: $root.templates.remainingTime"></span>)</span>.
                        </span>
                        <span data-bind="ifnot: numberOfRecordsProcessed">
                            <span data-bind="text: name"></span>.
                        </span>
                        <div data-bind="if: description">
                            <p data-bind="html: description"></p>
                        </div>
                    </li>
                </ol>
            </div>

            <div data-bind="if: completed">
                <?php
                    awpcp_html_admin_third_level_heading( array(
                        'attributes' => array(
                            'data-bind' => 'html: successTitle, visible: successTitle',
                        ),
                        'echo'       => true,
                    ) );
                ?>
                <div data-bind="html: successContent, visible: successContent"></div>
            </div>
        </li>
    </ul>

    <form class="awpcp-asynchronous-tasks-form" data-bind="submit: start, ifnot: group.completed">
        <div class="progress-bar">
            <div class="progress-bar-value" data-bind="progress: group.percentageOfCompletionString"></div>
        </div>

        <p class="submit">
            <input id="submit" type="submit" class="button-primary" name="submit" disabled="disabled" data-bind="value: submit, disable: group.running">
        </p>
    </form>
</div>
