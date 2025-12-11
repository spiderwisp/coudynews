<?php
/**
 * Template for the Settings admin page.
 *
 * @package AWPCP\Admin\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

settings_errors();
?>

<h2 class="nav-tab-wrapper">
<?php foreach ( $groups as $group ) : ?>
    <?php if ( count( $group['subgroups'] ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( 'g', $group['id'], $current_url ) ); ?>" class="<?php echo esc_attr( $group['id'] === $current_group['id'] ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>"><?php echo esc_html( $group['name'] ); ?></a>
    <?php endif; ?>
<?php endforeach; ?>
</h2>

<?php if ( count( $current_group['subgroups'] ) ) : ?>
    <ul class="awpcp-settings-sub-groups">
        <?php foreach ( $current_group['subgroups'] as $subgroup_id ) : ?>
        <li class="<?php echo esc_attr( $current_subgroup['id'] === $subgroup_id ? 'awpcp-current' : '' ); ?>">
            <a href="<?php echo esc_url( add_query_arg( 'sg', $subgroup_id, $current_url ) ); ?>"><?php echo esc_html( $subgroups[ $subgroup_id ]['name'] ); ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
    // TODO: DO we still need this?
    do_action( 'awpcp-admin-settings-page--' . $current_group['id'] );
?>

<form class="settings-form" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">

    <?php settings_fields( $setting_name ); ?>

    <input type="hidden" name="group" value="<?php echo esc_attr( $current_group['id'] ); ?>" />
    <input type="hidden" name="subgroup" value="<?php echo esc_attr( $current_subgroup['id'] ); ?>" />

    <?php
    $settings->load();
    ob_start();
    do_settings_sections( $current_subgroup['id'] );
    $output = ob_get_contents();
    ob_end_clean();

    if ( $output ) :
        ?>
        <p class="submit hidden">
            <input type="submit" value="<?php esc_attr_e( 'Save Changes', 'another-wordpress-classifieds-plugin' ); ?>" class="button-primary" id="submit-top" name="submit">
        </p>
        <?php
    endif;

    // A hidden submit button is necessary so that whenever the user hits enter on an input field,
    // that one is the button that is triggered, avoiding other submit buttons in the form to trigger
    // unwanted behaviours.

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $output;
    ?>

    <?php if ( $output ) : ?>
    <p class="submit">
        <input type="submit" value="<?php esc_attr_e( 'Save Changes', 'another-wordpress-classifieds-plugin' ); ?>" class="button-primary" id="submit-bottom" name="submit">
    </p>
    <?php endif; ?>
</form>
