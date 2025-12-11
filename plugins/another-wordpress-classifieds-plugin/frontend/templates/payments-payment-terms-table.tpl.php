<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


$columns = $this->get_columns();
$group   = '';
?>

<table class="awpcp-payment-terms-table awpcp-table">
    <thead>
        <tr>
        <?php foreach ($columns as $column => $name): ?>
            <th class="<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $name ); ?></th>
        <?php endforeach ?>
        </tr>
    </thead>

    <tbody>
    <?php
    foreach ( $this->get_items() as $item ):
        $_group = $this->item_group( $item );
        if ( $_group != $group ):
            ?>
        <tr class="awpcp-group-header">
            <th colspan="<?php echo count( $columns ); ?>" scope="row"><?php echo esc_html( $this->item_group_name( $item ) ); ?></th>
        </tr>
        <?php endif ?>

        <tr <?php $this->show_item_attributes( $item ); ?>>
            <?php foreach ($columns as $column => $name): ?>
            <td data-title="<?php echo esc_attr( $name ); ?>">
                <?php $this->show_item_column( $item, $column ); ?>
            </td>
            <?php endforeach ?>
        </tr>

        <?php $group = $_group ?>

    <?php endforeach ?>
    </tbody>
</table>
