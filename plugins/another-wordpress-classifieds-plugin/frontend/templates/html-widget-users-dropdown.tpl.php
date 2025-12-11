<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 if ( $args['label'] ): ?>
<label for="<?php echo esc_attr( $args['id'] ); ?>"><?php
    echo esc_attr( $args['label'] );
    if ( $args['required'] ):
        ?><span class="required">*</span><?php
    endif;
    ?></label>
<?php endif; ?>
<select id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" class="<?php echo esc_attr( implode( ' ', $args['class'] ) ); ?>" dropdown-field>
    <?php if ( $args['default'] ): ?>
    <option value=""><?php echo esc_html( $args['default'] ); ?></option>
    <?php endif; ?>
    <?php foreach ( $args['users'] as $k => $user ): ?>
    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php
        if ( $args['include-full-user-information'] ):
            ?>data-user-information="<?php echo esc_attr( wp_json_encode( $user ) ); ?>" <?php
        endif;
        echo $args['selected'] == $user->ID ? 'selected="selected"' : ''; ?>>
        <?php echo esc_html( $user->public_name ); ?>
    </option>
    <?php endforeach; ?>
</select>
