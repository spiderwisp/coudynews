<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_image_placeholders() {
    $container = awpcp()->container;

    return new AWPCP_Image_Placeholders(
        awpcp_attachment_properties(),
        awpcp_attachments_collection(),
        $container['ImageRenderer'],
        awpcp_listing_renderer(),
        $container['Settings']
    );
}

class AWPCP_Image_Placeholders {

    private $attachment_properties;
    private $attachments;

    /**
     * @var AWPCP_ImageRenderer
     */
    private $image_renderer;

    private $listing_renderer;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    private $cache;

    public function __construct( $attachment_properties, $attachments, $image_renderer, $listing_renderer, $settings ) {
        $this->attachment_properties = $attachment_properties;
        $this->attachments = $attachments;
        $this->image_renderer        = $image_renderer;
        $this->listing_renderer = $listing_renderer;
        $this->settings              = $settings;
    }

    public function do_image_placeholders( $ad, $placeholder ) {
        if ( ! isset( $this->cache[ $ad->ID ] ) ) {
            $placeholders = $this->render_image_placeholders( $ad, $placeholder );
            $this->cache[ $ad->ID ] = apply_filters( 'awpcp-image-placeholders', $placeholders, $ad );
        }

        return $this->cache[ $ad->ID ][ $placeholder ];
    }

    private function render_image_placeholders( $ad, $placeholder ) {
        global $awpcp_imagesurl;

        $placeholders = array(
            'featureimg' => '',
            'awpcpshowadotherimages' => '',
            'images' => '',
            'awpcp_image_name_srccode' => '',
        );

        $url = $this->listing_renderer->get_view_listing_url( $ad );
        $thumbnail_width = get_awpcp_option('displayadthumbwidth');

        if ( awpcp_are_images_allowed() ) {
            $primary_image = $this->attachments->get_featured_image( $ad->ID );
            $gallery_name  = 'awpcp-gallery-' . $ad->ID;

            if ($primary_image) {
                $large_image = $this->attachment_properties->get_image_url( $primary_image, 'large' );

                if (get_awpcp_option('show-click-to-enlarge-link', 1)) {
                    $link = '<a class="thickbox enlarge" href="%s">%s</a>';
                    $link = sprintf($link, $large_image, __( 'Click to enlarge image.', 'another-wordpress-classifieds-plugin'));
                } else {
                    $link = '';
                }

                $link_attributes = array(
                    'class' => 'awpcp-listing-primary-image-thickbox-link thickbox thumbnail',
                    'href' => esc_url( $large_image ),
                    'rel' => esc_attr( $gallery_name ),
                    'data-awpcp-gallery' => esc_attr( $gallery_name ),
                );

                $image_attributes = array(
                    'class' => 'thumbshow',
                    'alt'   => __( "Thumbnail for the listing's main image", 'another-wordpress-classifieds-plugin' ),
                );

                $content = '<div class="awpcp-ad-primary-image">';
                $content.= '<a ' . awpcp_html_attributes( $link_attributes ) . '>';
                $content.= wp_get_attachment_image( $primary_image->ID, 'awpcp-featured', false, $image_attributes );
                $content.= '</a>' . $link;
                $content.= '</div>';

                $placeholders['featureimg'] = $content;

                $image_attributes = array(
                    'class' => 'awpcp-listing-primary-image-thumbnail',
                    'alt'   => awpcp_esc_attr( $this->listing_renderer->get_listing_title( $ad ) ),
                    // This was added after WordPress 6.7
                    // See: https://github.com/Strategy11/awpcp/issues/3178
                    'style' => 'width: ' . $thumbnail_width . 'px;',
                );

                // TODO: Can we regeneate thumbnails every time the user changes
                // the dimensions for featured images on lists?
                $featured_image_on_lists = wp_get_attachment_image(
                    $primary_image->ID,
                    'awpcp-featured-on-lists',
                    false,
                    $image_attributes
                );

                $template = '<a class="awpcp-listing-primary-image-listing-link" href="%s">%s</a>';

                $placeholders['awpcp_image_name_srccode'] = sprintf( $template, esc_url( $url ), $featured_image_on_lists );
            }

            $images_uploaded = $this->attachments->count_attachments_of_type( 'image', array( 'post_parent' => $ad->ID ) );

            if ($images_uploaded >= 1) {
                $results = $this->attachments->find_visible_attachments( array( 'post_parent' => $ad->ID ) );

                $columns = get_awpcp_option('display-thumbnails-in-columns', 0);
                $rows = $columns > 0 ? ceil(count($results) / $columns) : 0;
                $shown = 0;

                $images = array();
                foreach ($results as $image) {
                    if ( $primary_image && $primary_image->ID == $image->ID ) {
                        continue;
                    }

                    if ( ! $this->attachment_properties->is_image( $image ) ) {
                        continue;
                    }

                    $large_image = $this->attachment_properties->get_image_url( $image, 'large' );
                    $li_classes  = [ 'awpcp-attachments-list-item', 'awpcp-attachment-type-image' ];

                    if ($columns > 0) {
                        $li_classes[] = "awpcp-attchment-column-width-1-{$columns}";
                        $li_classes = awpcp_get_grid_item_css_class( $li_classes, $shown, $columns, $rows );
                    }

                    $link_attributes = array(
                        'class' => 'awpcp-attachment-thumbnail-container thickbox',
                        'href' => esc_url( $large_image ),
                        'rel' => esc_attr( $gallery_name ),
                        'data-awpcp-gallery' => esc_attr( $gallery_name ),
                    );

                    $content = '<li ' . awpcp_html_attributes( [ 'class' => $li_classes ] ) . '>';
                    $content.= '<a ' . awpcp_html_attributes( $link_attributes ) . '>';
                    $content.= $this->image_renderer->render_attachment_thumbnail( $image->ID, [ 'class' => 'thumbshow' ] );
                    $content.= '</a>';
                    $content.= '</li>';

                    $images[] = $content;

                    ++$shown;
                }

                $placeholders['awpcpshowadotherimages'] = join('', $images);

                $content = '<ul class="awpcp-single-ad-images">%s</ul>';
                $placeholders['images'] = sprintf($content, $placeholders['awpcpshowadotherimages']);
            }
        }

        // fallback thumbnail
        if ( awpcp_are_images_allowed() && empty( $placeholders['awpcp_image_name_srccode'] ) && ! get_awpcp_option( 'hide-noimage-placeholder', 1 ) ) {

            // check if user has enabled override for no image placeholder
            if ( get_awpcp_option( 'override-noimage-placeholder', true ) ) {
                // get saved no image placeholer url
                $thumbnail = get_awpcp_option( 'noimage-placeholder-url' );

            }else {
                $thumbnail = sprintf( '%s/adhasnoimage.png', $awpcp_imagesurl );
            }

            $image_attributes = array(
                'attributes' => array(
                    'class' => 'awpcp-listing-primary-image-thumbnail awpcp-noimage-placeholder',
                    'alt'   => awpcp_esc_attr( $this->listing_renderer->get_listing_title( $ad ) ),
                    'src'   => esc_attr( $thumbnail ),
                    'width' => esc_attr( $thumbnail_width ),
                ),
            );

            if ( $this->settings->get_option( 'crop-featured-image-on-lists' ) ) {
                $image_attributes['attributes']['height'] = $this->settings->get_option( 'featured-image-height-on-lists' );
            }

            $content = '<a class="awpcp-listing-primary-image-listing-link" href="%s">%s</a>';
            $content = sprintf($content, $url, awpcp_html_image( $image_attributes ) );

            $placeholders['awpcp_image_name_srccode'] = $content;
        }

        $placeholders['featureimg'] = apply_filters( 'awpcp-featured-image-placeholder', $placeholders['featureimg'], 'single', $ad );
        $placeholders['awpcp_image_name_srccode'] = apply_filters( 'awpcp-featured-image-placeholder', $placeholders['awpcp_image_name_srccode'], 'listings', $ad );

        $placeholders['featured_image'] = $placeholders['featureimg'];
        $placeholders['imgblockwidth'] = "{$thumbnail_width}px";
        $placeholders['thumbnail_width'] = "{$thumbnail_width}px";

        return $placeholders;
    }
}
