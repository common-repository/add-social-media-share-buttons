<?php
/**
 * Plugin Name: Add Social Media Share Buttons
 * Plugin URI: https://wordpress.org/plugins/add-social-media-share-buttons/
 * Description: Add Social Media Share Buttons adds social media share buttons and icons using simple WordPress shortcodes.
 * Version: 1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Harpreet Kumar
 * Author URI: https://profiles.wordpress.org/hkharpreetkumar1/
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
 
 
 if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


// Get the URL for the plugin directory
$asmsb_plugin_url = plugins_url('', __FILE__);

function asmsb_quote_share_enqueue_scripts() {
    $plugin_dir = plugin_dir_url(__FILE__);

    // Use filemtime() for versioning to avoid caching issues
    $css_version = filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css');
    $js_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/custom.js');

    wp_enqueue_style('quote_share_styles', $plugin_dir . 'assets/css/style.css', array(), $css_version); // Added $css_version
    wp_enqueue_script('quote_share_scripts', $plugin_dir . 'assets/js/custom.js', array('jquery'), $js_version, true); // Added $js_version
}


add_action('wp_enqueue_scripts', 'asmsb_quote_share_enqueue_scripts');

// Shortcode for displaying text quotes
add_shortcode('asmsb', 'asmsb_quote_shortcode_callback');
function asmsb_quote_shortcode_callback($atts, $content = null) {
    $atts = shortcode_atts(array(), $atts);
    preg_match_all('/<p>(\S.*?)<\/p>/s', $content, $match); // Get text inside p tags
    $quotes_without_empty = $match[1];
    preg_replace("/^\d+\s+\w{1}\s+/", "", $quotes_without_empty); // Remove numbering from start of string

    $quotes = asmsb_removeNumbersAndDotFromArray($quotes_without_empty);
    $output = '<div class="quote-container">';

    foreach ($quotes as $quote) {
        $quote_text = wp_kses_post($quote);
        $quote_html = asmsb_generate_quote_html($quote_text);
        $output .= $quote_html;
    }

    $output .= '</div>';
    return $output;
}

// Function to generate HTML for a quote
function asmsb_generate_quote_html($quote_text) {
    global $asmsb_plugin_url;
    $image_path = $asmsb_plugin_url . '/assets';
    $formated_text = preg_replace('/<a\b[^>]*>(.*?)<\/a>/i', '', $quote_text); // Remove links from text
    $quote_text_new = preg_replace('/^\s*\d+\.\s*/', '', $formated_text);

    $twitter_char_limit = 180;
    // If quote text is longer than character limit, truncate it and add ellipsis
    if (strlen($quote_text_new) > $twitter_char_limit) {
        $twitter_quote_text = substr($quote_text_new, 0, $twitter_char_limit - 3) . '...';
    } else {
        $twitter_quote_text = $quote_text_new;
    }

    $cleanedStrings = asmsb_removeQuotesFromArray($twitter_quote_text);
    $twitter_quote_text = $cleanedStrings;

    $whatsapp_cleanedStrings = asmsb_removeQuotesFromArray($quote_text_new);

    $facebook_share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode(get_permalink()) . '&quote=' . rawurlencode($quote_text_new);
    $twitter_share_url = 'https://twitter.com/share/?url=' . rawurlencode(get_permalink()) . '&text=' . rawurlencode(html_entity_decode($twitter_quote_text));
    $site_shared = '_*Shared via*: ' . get_permalink();
    $whatsapp_share_url = 'https://wa.me/?text=' . rawurlencode('*' . html_entity_decode($whatsapp_cleanedStrings) . '*' . "\n\n" . $site_shared);

    $html = '
    <div class="assb_container">
        <div class="share_box">
            <div class="box_one">
                <p class="phar_one" id="quotetext"><span><b>' . $quote_text . ' </b></span></p>
                <div class="sharebtns">
                    <img class="share_btn" src=' . $image_path . '/images/share.png alt="#" class="share_icons">

                    <a href="javascript:void(0);" class="fb-copy-button facebook_icon icon-fb" url="" onclick="shareOnFacebook(\'' . esc_url($facebook_share_url) . '\');">
                        <img class="facebook_btn" src=' . $image_path . '/images/facebook.png alt="#" >
                    </a>

                    <a href="javascript:void(0);" class="twitter_icon icon-twitter" url="" onclick="shareOnTwitter(\'' . esc_js($twitter_share_url) . '\');">
                        <img class="twitter_btn" src=' . $image_path . '/images/twitter.png alt="#">
                    </a>
                    <a href="javascript:void(0);" class="whatsapp_icon icon-whatsapp" url="" onclick="shareOnWhatsApp(\'' . esc_js($whatsapp_share_url) . '\');">
                        <img class="whatsapp_btn" src=' . $image_path . '/images/whatsapp.png alt="#" >
                    </a>
                </div>
            </div>
        </div>
    </div>
    ';

    return $html;
}

// Function to remove numbering from array elements
function asmsb_removeNumbersAndDotFromArray($array) {
    $pattern = '/^\d+\.\s/';
    $replacement = '';
    return preg_replace($pattern, $replacement, $array);
}

// Function to remove quotes from array elements
function asmsb_removeQuotesFromArray($array) {
    $pattern = '/“|”|"/';  // This pattern matches both types of quotes
    $replacement = '';
    return preg_replace($pattern, $replacement, $array);
}

// Function to insert Open Graph image in head section
add_action('wp_head', 'asmsb_insert_og_image_in_head', 5);
function asmsb_insert_og_image_in_head() {
    if (is_singular('page')) {
        global $post;
        $featured_image = get_the_post_thumbnail_url($post->ID);
        if ($featured_image) {
            echo '<meta property="og:image" content="' . esc_url($featured_image) . '"/>';
        } else {
            $site_logo = get_theme_mod('custom_logo');
            if ($site_logo) {
                $site_logo_url = wp_get_attachment_image_url($site_logo, 'full');
                echo '<meta property="og:image" content="' . esc_url($site_logo_url) . '"/>';
            } else {
                $site_logo_url = get_site_url() . '/wp-content/uploads/2023/07/image1.jpg';
                echo '<meta property="og:image" content="' . esc_url($site_logo_url) . '"/>';
            }
        }
    }
}



