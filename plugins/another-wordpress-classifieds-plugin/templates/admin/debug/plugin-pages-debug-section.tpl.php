<?php
/**
 * @package AWPCP\Templates\Admin\Debug
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

awpcp_html_admin_second_level_heading(
    array(
        'content' => esc_html__( 'Are you seeing 404 Not Found errors?', 'another-wordpress-classifieds-plugin' ),
        'echo'    => true,
    )
);

$allowed_html = array(
    'a' => array(
        'href',
        'title',
    ),
);

$message = __( 'If you are seeing multiple 404 Not Found errors in your website, it is possible that some Rewrite Rules are missing or corrupted. Please click the button bellow to navigate to the <permalinks-settings-link>Permalinks Settings</a> page.', 'another-wordpress-classifieds-plugin' );
$message = str_replace( '<permalinks-settings-link>', '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">', $message );
?>

<p><?php echo wp_kses( $message, $allowed_html ); ?></p>

<?php
$message = __( "Opening that page in your browser will flush the Rewrite Rules in your site. WordPress will then ask all installed and active plugins to register their rules and those 404 Not Found errors should be gone. If that's not the case, please contact <support-link>customer support</a>.", 'another-wordpress-classifieds-plugin' );
$message = str_replace( '<support-link>', '<a href="https://awpcp.com/contact/">', $message );
?>

<p><?php echo wp_kses( $message, $allowed_html ); ?></p>

<p>
    <a class="button-primary" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php echo esc_html_x( 'Flush Rewrite Rules', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></a>
</p>

<?php
awpcp_html_admin_second_level_heading(
    array(
        'content' => esc_html__( 'Plugin Pages', 'another-wordpress-classifieds-plugin' ),
        'echo'    => true,
    )
);
?>

<table class="widefat striped">
    <thead>
        <tr>
            <th><?php echo esc_html_x( 'Reference', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></th>
            <th><?php echo esc_html_x( 'Page Title', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></th>
            <th><?php echo esc_html_x( 'Stored ID', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $plugin_pages as $page ) : ?>
        <tr>
            <td><?php echo esc_html( $page['reference'] ); ?></td>
            <td>
                <?php if ( $page['page_url'] ) : ?>
                    <a href="<?php echo esc_url( $page['page_url'] ); ?>"><?php echo esc_html( $page['page_title'] ); ?></a>
                <?php else : ?>
                    <?php echo $page['page_title'] ? esc_html( $page['page_title'] ) : '&mdash;'; ?>
                <?php endif; ?>
            </td>
            <td><?php echo $page['page_id'] ? esc_html( $page['page_id'] ) : '&mdash;'; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
