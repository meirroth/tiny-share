<?php
/**
* @wordpress-plugin
* Plugin Name:       Tiny Share
* Plugin URI:        https://github.com/meirroth/tiny-share
* Description:       This plugin creates a shortcode that can be used anywhere on the website to show social share links. Use shortcode: [TinyShare]
* Version:           1.0.4
* Author:            Meir Roth
* Author URI:        https://github.com/meirroth
*/

// If this file is called firectily, abort!!
defined( 'ABSPATH' ) or ( 'Hey, What are you doing here? you silly human!' );

define( 'TINYSHARE_URL', trailingslashit( plugins_url('/', __FILE__) ) );
define( 'TINYSHARE_PATH', trailingslashit( plugin_dir_path(__FILE__) ) );
define( 'TINYSHARE_VERSION', '1.0.4' );


/**
 * Return camelCase string
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'camelCase' ) ){
  function camelCase($str, array $noStrip = []) {
    // non-alpha and non-numeric characters become spaces
    $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
    $str = trim($str);
    // uppercase the first character of each word
    $str = ucwords($str);
    $str = str_replace(" ", "", $str);

    return $str;
  }
}

/**
 * Return gtag event function
 *
 * @since    1.0.3
 */
if ( ! function_exists( 'gtag_event' ) ) {
  function gtag_event( $gtag = false, $type = NULL, $ID = NULL, $method = NULL, $onClick = true) {
    if ( $gtag && !is_user_logged_in() ) {
      $out = 'gtag(\'event\', \'share\', {\'content_type\': \'' . $type . '\', \'item_id\': \'' . $ID . '\', method: \'' . $method . '\'});';
      if ($onClick) {$out = 'onclick="' . $out . '"';}
      return $out;
    }
    return;
  }
}

/**
 * Return class for umami event tracking
 *
 * @since    1.0.4
 */
if ( ! function_exists( 'umami_class' ) ) {
  function umami_class( $umami = false, $name = NULL) {
    if ( $umami && isset($name) && !is_user_logged_in() ) {
      return ' umami--click--share-' . $name;
    }
    return;
  }
}

/**
 * Sanitize post title
 *
 * @since    1.0.4
 */
function sanitize_page_title( $page_title ) {
  $page_title = html_entity_decode( $page_title, ENT_QUOTES, 'UTF-8' );
  $page_title = rawurlencode( $page_title );
  $page_title = str_replace( '#', '%23', $page_title );
  $page_title = esc_html( $page_title );

  return $page_title;
}

if ( ! function_exists( 'print_tiny_share' ) ){
  function print_tiny_share( $atts ) {

    static $loaded_styles = false;

    global $wp, $post;

    if ( ! is_object( $post ) ) {
      return '';
    }

    if ( ! is_singular() || is_front_page() ) {
			$page_title = get_bloginfo( 'name' ) . " - " . get_bloginfo( 'description' );
			if ( is_category() ) {
				$page_title = esc_attr( wp_strip_all_tags( stripslashes( single_cat_title( '', false ) ), true ) ) . " - " . get_bloginfo( 'name' );
			} elseif ( is_tag() ) {
				$page_title = esc_attr( wp_strip_all_tags( stripslashes( single_tag_title( '', false ) ), true ) ) . " - " . get_bloginfo( 'name' );
			} elseif ( is_tax() ) {
				$page_title = esc_attr( wp_strip_all_tags( stripslashes( single_term_title( '', false ) ), true ) ) . " - " . get_bloginfo( 'name' );
			} elseif ( is_search() ) {
				$page_title = esc_attr( wp_strip_all_tags( stripslashes( __( 'Search for' ) .' "' .get_search_query() .'" on '.get_bloginfo( 'name' ) ), true ) );
			} elseif ( is_author() ) {
				$page_title = esc_attr( wp_strip_all_tags( stripslashes( get_the_author_meta( 'display_name', get_query_var( 'author' ) ) ), true ) );
			} elseif ( is_archive() ) {
				if ( is_day() ) {
					$page_title = esc_attr( wp_strip_all_tags( stripslashes( get_query_var( 'day' ) . ' ' .single_month_title( ' ', false ) . ' ' . __( 'Archives' ) ), true ) ) . " - " . get_bloginfo( 'name' );
				} elseif ( is_month() ) {
					$page_title = esc_attr( wp_strip_all_tags( stripslashes( single_month_title( ' ', false ) . ' ' . __( 'Archives' ) ), true ) ) . " - " . get_bloginfo( 'name' );
				} elseif ( is_year() ) {
					$page_title = esc_attr( wp_strip_all_tags( stripslashes( get_query_var( 'year' ) . ' ' . __( 'Archives' ) ), true ) ) . " - " . get_bloginfo( 'name' );
				}
			}
		} else {
			$page_title = $post->post_title . " - " . get_bloginfo( 'name' );
		}

    $atts = shortcode_atts(
      array(
        'cta' => 'Share this page: ',
        'url' => trailingslashit( home_url( add_query_arg( array(), $wp->request ) ) ),
        'title' => $page_title,
        'facebook' => true,
        'twitter' => true,
        'linkedin' => true,
        'whatsapp' => true,
        'email' => true,
        'link' => true,
        'print' => true,
        'twitter_username' => '',
        'twitter_hashtags' => '',
        'color' => '#000',
        'color_hover' => '#02a9ea',
        'stroke_width' => '1.5',
        'gtag' => false,
        'umami' => false,
        'size' => 20,
      ),
      $atts,
      'TinyShare'
    );
    $size = $atts['size'];
    $atts['url'] = urlencode( $atts['url'] );
    $atts['title'] = sanitize_page_title( $atts['title'] );
    $atts['twitter_hashtags'] = esc_html( str_replace([', ', ' ', '#'], [',', ',', ''], $atts['twitter_hashtags']) );
    $atts['twitter_username'] = esc_html( camelCase( $atts['twitter_username'] ) );
    $page_type = get_post_type( $post );
    $page_clean_url = esc_url( urldecode( $atts['url'] ));

		// Construct sharing URL
    $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u=' . $atts['url'];
    // https://developer.twitter.com/en/docs/twitter-for-websites/tweet-button/guides/web-intent
		$twitterURL = 'https://twitter.com/intent/tweet?url=' . $atts['url'] . '&text=' . $atts['title'];
      if ($atts['twitter_hashtags']) { $twitterURL .= '&hashtags=' . $atts['twitter_hashtags']; }
      if ($atts['twitter_username']) { $twitterURL .= '&via=' . $atts['twitter_username']; }
		$linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $atts['url'] . '&amp;title=' . $atts['title'];
    $whatsAppURL = 'https://api.whatsapp.com/send?text=' . $atts['title'] . ' ' . $atts['url'];
    $emailURL = 'mailto:?Subject=' . urldecode( $atts['title'] ) . '&body=' . $atts['url'];

    $facebookSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-brand-facebook"><path stroke="none" d="M0 0h24v24H0z"/><path d="M7 10v4h3v7h4v-7h3l1-4h-4V8a1 1 0 011-1h3V3h-3a5 5 0 00-5 5v2H7"/></svg>';
    $twitterSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-brand-twitter"><path stroke="none" d="M0 0h24v24H0z"/><path d="M22 4.01c-1 .49-1.98.689-3 .99-1.121-1.265-2.783-1.335-4.38-.737S11.977 6.323 12 8v1c-3.245.083-6.135-1.395-8-4 0 0-4.182 7.433 4 11-1.872 1.247-3.739 2.088-6 2 3.308 1.803 6.913 2.423 10.034 1.517 3.58-1.04 6.522-3.723 7.651-7.742a13.84 13.84 0 00.497-3.753C20.18 7.773 21.692 5.25 22 4.009z"/></svg>';
    $linkedInSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-brand-linkedin"><path stroke="none" d="M0 0h24v24H0z"/><rect width="16" height="16" x="4" y="4" rx="2"/><path d="M8 11v5M8 8v.01M12 16v-5M16 16v-3a2 2 0 00-4 0"/></svg>';
    $whatsAppSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-brand-whatsapp"><path stroke="none" d="M0 0h24v24H0z"/><path d="M3 21l1.65-3.8a9 9 0 113.4 2.9L3 21"/><path d="M9 10a.5.5 0 001 0V9a.5.5 0 00-1 0v1a5 5 0 005 5h1a.5.5 0 000-1h-1a.5.5 0 000 1"/></svg>';
    $emailSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z"/><rect width="18" height="14" x="3" y="5" rx="2"/><path d="M3 7l9 6 9-6"/></svg>';
    $printSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-printer"><path stroke="none" d="M0 0h24v24H0z"/><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2M17 9V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4"/><rect width="10" height="8" x="7" y="13" rx="2"/></svg>';
    $linkSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="' . $atts['color'] . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $atts['stroke_width'] . '" class="icon-tabler icon-tabler-link"><path stroke="none" d="M0 0h24v24H0z"/><path d="M10 14a3.5 3.5 0 005 0l4-4a3.5 3.5 0 00-5-5l-.5.5"/><path d="M14 10a3.5 3.5 0 00-5 0l-4 4a3.5 3.5 0 005 5l.5-.5"/></svg>';


    $TinyShareFacebook = '<a class="tiny-share-btn-facebook hint--top' . umami_class( $atts['umami'], 'facebook') . '" href="' . esc_url( $facebookURL ) . '" aria-label="On Facebook" target="_blank" rel="noopener" ' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "Facebook") . '>' . $facebookSVG . '</a>';
    $TinyShareTwitter = '<a class="tiny-share-btn-twitter hint--top' . umami_class( $atts['umami'], 'twitter') . '" href="' . esc_url( $twitterURL ) . '" aria-label="On Twitter" target="_blank" rel="noopener" ' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "Twitter") . '>' . $twitterSVG . '</a>';
    $TinyShareLinkedin = '<a class="tiny-share-btn-linkedin hint--top' . umami_class( $atts['umami'], 'linkedin') . '" href="' . esc_url( $linkedInURL ) . '" aria-label="On LinkedIn" target="_blank" rel="noopener" ' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "LinkedIn") . '>' . $linkedInSVG . '</a>';
    $TinyShareWhatsApp = '<a class="tiny-share-btn-whatsapp hint--top' . umami_class( $atts['umami'], 'whatsapp') . '" href="' . esc_url( $whatsAppURL ) . '" aria-label="On WhatsApp" target="_blank" rel="noopener" ' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "WhatsApp") . '>' . $whatsAppSVG . '</a>';
    $TinyShareEmail = '<a class="tiny-share-btn-mailto hint--top' . umami_class( $atts['umami'], 'email') . '" href="' . esc_url( $emailURL ) . '" aria-label="Email" target="_blank" ' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "Email") . '>' . $emailSVG . '</a>';
    $TinyShareLink = '<button class="tiny-share-btn-link hint--top' . umami_class( $atts['umami'], 'link') . '" aria-label="Copy URL" ' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "Copy URL") . '>' . $linkSVG . '</button>';
    $TinySharePrint = '<button class="tiny-share-btn-print hint--top' . umami_class( $atts['umami'], 'print') . '" aria-label="Print" onclick="window.print();' . gtag_event( $atts['gtag'], $page_type, $page_clean_url, "Print", false) . '">' . $printSVG . '</button>';

    // https://cdnjs.cloudflare.com/ajax/libs/hint.css/2.6.0/hint.min.css
    $styles = '';
    if ( ! $loaded_styles ) {
      $styles .=
      '<link rel="stylesheet" href="' . TINYSHARE_URL . 'public/libs/hint.min.css" media="all"/>
      <style id="tiny-share-style">.tiny-share{font-size:inherit;font-family:inherit;font-weight:500} .tiny-share [class^="tiny-share-btn-"]{vertical-align:sub;display:inline-block;background:none;color:inherit;border:none;padding:0;font:inherit;text-transform:initial;cursor:pointer;outline:inherit;margin:0 2px;line-height:1}
      .tiny-share [class^="tiny-share-btn-"]:hover svg,.tiny-share [class^="tiny-share-btn-"].hint--always svg{stroke:' . $atts['color_hover'] . '}[class*=hint--]:after {font-family: inherit; box-shadow: 4px 4px 10px -2px rgba(0,0,0,.3); border-radius: 2px; }.tiny-share [class^="tiny-share-btn-"]:after,.tiny-share [class^="tiny-share-btn-"]:before,.tiny-share [class^="tiny-share-btn-"] svg *{transition:.15s ease-out}</style>';
    }
    $loaded_styles = true;

    $out = $styles . '<div class="tiny-share"><span class="tiny-share-cta">' . $atts['cta'] . '</span>';

    if ($atts['facebook'] ) {
      $out .= $TinyShareFacebook;
    }
    if ($atts['twitter']) {
      $out .= $TinyShareTwitter;
    }
    if ($atts['linkedin']) {
      $out .= $TinyShareLinkedin;
    }
    if ($atts['whatsapp']) {
      $out .= $TinyShareWhatsApp;
    }
    if ($atts['email']) {
      $out .= $TinyShareEmail;
    }
    if ($atts['link']) {
      $out .= $TinyShareLink;
      add_action('wp_footer', 'print_clipboardjs_scripts');
    }
    if ($atts['print']) {
      $out .= $TinySharePrint;
    }
    $out .= '</div>';

    return $out;
  }
  add_shortcode('TinyShare', 'print_tiny_share');
}

function print_clipboardjs_scripts() {
  static $loaded_scripts = false;
  global $wp;

  // source: https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js
  if (!$loaded_scripts) {
    ?>
    <script async src="<?php echo TINYSHARE_URL ?>public/libs/clipboard.min.js"></script>
    <script type="text/javascript">
      window.addEventListener('load', function () {
        var clipboard = new ClipboardJS('.tiny-share-btn-link', {text: function() {return '<?php echo trailingslashit( esc_url( home_url( add_query_arg( array(), $wp->request ) ) ) ) ?>';}});
        clipboard.on('success', function(e) {e.clearSelection();
          e.trigger.setAttribute('aria-label', 'URL copied to clipboard!');
          e.trigger.classList.add("hint--success", "hint--always");
          setTimeout(function() {
            e.trigger.classList.remove("hint--success", "hint--always");
            e.trigger.setAttribute('aria-label', 'Copy URL');
          }, 2000);
        });
        clipboard.on('error', function(e) {
          console.error('Action:', e.action);
          console.error('Trigger:', e.trigger);
        });
      });
    </script>
    <?php
  }
  $loaded_scripts = true;
}

// make the shortcode work in widgets
add_filter( 'widget_text', 'do_shortcode' );
