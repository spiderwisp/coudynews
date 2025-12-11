<?php
/**
 * @package AWPCP\Frontend\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Widget used to display ads recently added to the system.
 */
class AWPCP_LatestAdsWidget extends WP_Widget {

    protected $listing_renderer;
    protected $attachment_properties;
    protected $attachments;

    public function __construct($id=null, $name=null, $description=null) {
        $id = is_null($id) ? 'awpcp-latest-ads': $id;
        $name = is_null($name) ? __( 'AWPCP Latest Ads', 'another-wordpress-classifieds-plugin') : $name;
        $description = is_null($description) ? __( 'Displays a list of latest Ads', 'another-wordpress-classifieds-plugin') : $description;
        parent::__construct($id, $name, array('description' => $description));

        $this->listing_renderer = awpcp_listing_renderer();
        $this->attachment_properties = awpcp_attachment_properties();
        $this->attachments = awpcp_attachments_collection();
    }

    protected function defaults() {
        $translations = array(
            'hlimit' => 'limit',
            'showimages' => 'show-images',
            'showblank' => 'show-blank',
        );

        $defaults = array(
            'title' => __( 'Latest Ads', 'another-wordpress-classifieds-plugin'),
            'show-title' => 1,
            'show-excerpt' => 1,
            'show-images' => 1,
            'show-blank' => 1,
            'thumbnail-position-in-desktop' => 'left',
            'thumbnail-position-in-mobile' => 'above',
            'limit' => 10,
        );

        // TODO: get rid of the widget_awpcplatestads option in 3.1 or 3.0.1
        $options = get_option('widget_awpcplatestads');
        $options = is_array($options) ? $options : array();

        foreach ($translations as $old => $new) {
            if (isset($options[$old])) {
                $options[$new] = $options[$old];
            }
        }

        return array_intersect_key(array_merge($defaults, $options), $defaults);
    }

    /**
     * @since 3.0
     *
     * @param array $items
     * @param array $instance
     * @param string $html_class CSS class for each LI element.
     *
     * @return string
     */
    protected function render($items, $instance, $html_class='') {
        $instance = array_merge( $this->defaults(), $instance );

        if ( empty( $items ) ) {
            return $this->render_empty_widget( $html_class );
        }

        return $this->render_widget( $items, $instance, $html_class );
    }

    private function render_empty_widget( $html_class ) {
        return sprintf( '<li class="awpcp-empty-widget %s">%s</li>', $html_class, __( 'There are currently no ads to show.', 'another-wordpress-classifieds-plugin' ) );
    }

    private function render_widget( $items, $instance, $html_class ) {
        $html_class = implode( ' ', array(
            $this->get_item_thumbnail_position_css_class( $instance['thumbnail-position-in-desktop'], 'desktop' ),
            $this->get_item_thumbnail_position_css_class( $instance['thumbnail-position-in-mobile'], 'mobile' ),
            $html_class,
        ) );

        foreach ($items as $item) {
            $html[] = $this->render_item( $item, $instance, $html_class );
        }

        return join("\n", $html);
    }

    private function get_item_thumbnail_position_css_class( $thumbnail_position, $version ) {
        if ( $thumbnail_position == 'left' || $thumbnail_position == 'right' ) {
            $css_class = sprintf( 'awpcp-listings-widget-item-with-%s-thumbnail-in-%s', $thumbnail_position, $version );
        } else {
            $css_class = sprintf( 'awpcp-listings-widget-item-with-thumbnail-above-in-%s', $version );
        }

        return $css_class;
    }

    private function render_item( $item, $instance, $html_class ) {
        $listing_title = $this->listing_renderer->get_listing_title( $item );
        $item_url = $this->listing_renderer->get_view_listing_url( $item );
        $item_title = sprintf( '<a href="%s">%s</a>', $item_url, stripslashes( $listing_title ) );

        $html_title   = '';
        $html_excerpt = '';
        $read_more    = '';

        if ($instance['show-title']) {
            $html_title = sprintf( '<div class="awpcp-listing-title">%s</div>', $item_title );
        }

        if ($instance['show-excerpt']) {
            $excerpt = awpcp_do_placeholder_excerpt( $item, 'excerpt' );
            $read_more = sprintf( '<p class="awpcp-widget-read-more-container"><a class="awpcp-widget-read-more" href="%s">[%s]</a></p>', $item_url, __( 'Read more', 'another-wordpress-classifieds-plugin' ) );
            $html_excerpt = sprintf( '<div class="awpcp-listings-widget-item-excerpt">%s</div>', $excerpt );
        }

        $html_image = $this->render_item_image( $item, $instance );

        if ( ! empty( $html_image ) ) {
            $template = '<li class="awpcp-listings-widget-item %1$s"><div class="awpcplatestbox awpcp-clearfix"><div class="awpcplatestthumb awpcp-clearfix">%2$s</div><div class="awpcp-listings-widget-item--title-and-content">%3$s %4$s</div>%5$s</div></li>';
        } else {
            $html_class .= ' awpcp-listings-widget-item-without-thumbnail';

            $template = '<li class="awpcp-listings-widget-item %1$s"><div class="awpcplatestbox awpcp-clearfix"><div class="awpcp-listings-widget-item--title-and-content">%3$s %4$s</div>%5$s</div></li>';
        }

        return sprintf( $template, $html_class, $html_image, $html_title, $html_excerpt, $read_more );
    }

    protected function render_item_image( $item, $instance ) {
        global $awpcp_imagesurl;

        $show_images = $instance['show-images'] && awpcp_are_images_allowed();

        $image = $this->attachments->get_featured_image( $item->ID );

        $image_url  = '';
        $html_image = '';
        $listing_title = esc_attr( $this->listing_renderer->get_listing_title( $item ) );

        if ( ! is_null( $image ) && $show_images ) {
            $image_attributes = array(
                'alt' => $listing_title,
            );

            $html_image = sprintf(
                '<a class="awpcp-listings-widget-item-listing-link self" href="%s">%s</a>',
                $this->listing_renderer->get_view_listing_url( $item ),
                $this->attachment_properties->get_image( $image, 'featured', false, $image_attributes )
            );
        } elseif ( $instance['show-blank'] && $show_images ) {
            $image_url = "$awpcp_imagesurl/adhasnoimage.png";
            if ( get_awpcp_option( 'override-noimage-placeholder', true ) ) {
                // get saved no image placeholer url
                $image_url = get_awpcp_option( 'noimage-placeholder-url' );
            }

            $html_image = sprintf(
                '<a class="awpcp-listings-widget-item-listing-link self" href="%s">%s</a>',
                $this->listing_renderer->get_view_listing_url( $item ),
                "<img src='{$image_url}' alt='{$listing_title}' />"
            );
        }

        return apply_filters( 'awpcp-listings-widget-listing-thumbnail', $html_image, $item );
    }

    protected function query($instance) {
        return [
            'classifieds_query' => [
                'context' => [ 'public-listings', 'latest-listings-widget' ],
            ],
            'posts_per_page'    => $instance['limit'],
            'orderby'           => 'renewed-date',
        ];
    }

    public function widget($args, $instance) {
        $title = apply_filters( 'widget_title', $instance['title'] );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['before_widget'];

        // Do not show empty titles.
        if ( $title ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $args['before_title'] . $title . $args['after_title'];
        }

        echo '<ul class="awpcp-listings-widget-items-list">';
        $items = awpcp_listings_collection()->find_enabled_listings( $this->query( $instance ) );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render( $items, $instance );
        echo '</ul>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['after_widget'];
    }

    public function form($instance) {
        $instance = array_merge($this->defaults(), $instance);
        include(AWPCP_DIR . '/frontend/templates/widget-latest-ads-form.tpl.php');
        return '';
    }

    public function update($new_instance, $old_instance) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['limit'] = sanitize_text_field( $new_instance['limit'] );
        $instance['show-title'] = absint($new_instance['show-title']);
        $instance['show-excerpt'] = absint($new_instance['show-excerpt']);
        $instance['show-images'] = absint($new_instance['show-images']);
        $instance['show-blank'] = absint($new_instance['show-blank']);
        $instance['thumbnail-position-in-desktop'] = sanitize_text_field( $new_instance['thumbnail-position-in-desktop'] );
        $instance['thumbnail-position-in-mobile'] = sanitize_text_field( $new_instance['thumbnail-position-in-mobile'] );

        return $instance;
    }
}
