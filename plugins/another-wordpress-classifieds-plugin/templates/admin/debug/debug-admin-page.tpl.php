<?php
/**
 * @package AWPCP\Templates\Admin\Debug
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$allowed_html = [
    'strong' => [],
    'a'      => [
        'href'  => true,
        'title' => true,
    ],
];

$download_url = add_query_arg( 'download', 'debug page', awpcp_current_url() );

/* translators: %s is the URL to download the debug information */
$msg = _x( 'This information can help AWPCP developers to debug possible problems. If you are submitting a bug report please <strong><a href="%s">Download the Debug Information</a></strong> and attach it to your bug report.', 'debug page', 'another-wordpress-classifieds-plugin' );
$msg = sprintf( $msg, esc_url( $download_url ) );

?><p><?php echo wp_kses( $msg, $allowed_html ); ?></p>

<h2 class="nav-tab-wrapper">
    <?php foreach ( $sections as $slug => $label ) : ?>
    <a class="nav-tab<?php echo $current_section === $slug ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'awpcp-section', $slug, $current_url ) ); ?>">
        <?php echo esc_html( $label ); ?>
    </a>
    <?php endforeach; ?>
</h2>

<div class="awpcp-debug-section-content">
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $content;
?>
</div>

<hr>

<?php
awpcp_html_admin_second_level_heading(
    array(
        'content' => esc_html__( 'Debug & Development Tools', 'another-wordpress-classifieds-plugin' ),
        'echo'    => true,
    )
);
?>

<ul>
    <li><a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=query-monitor&TB_iframe=true&width=600&height=550' ) ); ?>">Query Monitor</a></li>
    <li><a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=ari-adminer&TB_iframe=true&width=600&height=550' ) ); ?>">ARI Adminer – WordPress Database Manager</a></li>
</ul>
