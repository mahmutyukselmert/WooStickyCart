<?php
/*
Plugin Name: WooSticky Cart
Description: Woocommerce için ürünler sayfasında özelleştirilmiş sepete ekleme paneli oluşturmak için oluşturulmuş eklenti.
Version: 1.0
Text Domain: woo-sticky-cart
Author: Mahmut Yüksel Mert - Mr.Mert
*/

// WooCommerce etkin değilse eklentiyi çalıştırmayı engelliyoruz
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('plugins_loaded', function() {
    load_plugin_textdomain('woo-sticky-cart', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

function add_ace_editor_scripts() {
    wp_enqueue_style('ace-editor', 'https://cdn.jsdelivr.net/npm/ace-builds@1.32.0/css/ace.min.css');
	wp_enqueue_script('ace-editor', 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.0/ace.min.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'add_ace_editor_scripts');


function wsc_load_css() {
	wp_enqueue_style( 'wsc-plugin', plugin_dir_url( __FILE__ ) . '/style.css', array(), time() );
}
add_action( 'wp_enqueue_scripts', 'wsc_load_css' );

add_action('woocommerce_after_add_to_cart_button', 'wp_product_page_add_to_cart_button_following_the_scroll');
function wp_product_page_add_to_cart_button_following_the_scroll(){
	if ( is_product() ){
		?>
		<div class="add-to-cart-bottom">
			<button name="add-to-cart" type="submit" value="<?php echo get_the_ID();?>" class="single_add_to_cart_button">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16"> <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/> </svg>
			</button>
			<span><?php echo get_option('wsc_cart_text');?></span>
		</div>
		<?php
    }
}

//WP-Admin Settings
add_action('admin_menu', 'wsc_custom_settings_menu');

function wsc_custom_settings_menu() {
    add_menu_page(
        __('WooStickyCart Ayarları', 'woo-sticky-cart'), // Sayfa başlığı
        'WooStickyCart', // Menü adı
        'manage_options', // Kullanıcı izni
        'wsc-cart-settings', // Menü URL'si
        'wsc_custom_settings_page', // Sayfa içeriği işlevi
        'dashicons-cart', // Menü simgesi
        80 // Menü sırası
    );
}

function wsc_custom_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo __('WooStickyCart Ayarları', 'woo-sticky-cart');?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wsc-cart-settings');
            do_settings_sections('wsc-cart-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'wsc_custom_settings');
function wsc_custom_settings() {
    register_setting('wsc-cart-settings', 'wsc_cart_bg_color');
    register_setting('wsc-cart-settings', 'wsc_cart_color');
    register_setting('wsc-cart-settings', 'wsc_cart_text_color');
    register_setting('wsc-cart-settings', 'wsc_cart_text');

    add_settings_section('wsc_cart_settings_section', __('Sepet Ayarları', 'woo-sticky-cart'), 'wsc_cart_settings_section_callback', 'wsc-cart-settings');

    function wsc_cart_settings_section_callback() {
        echo __('Eklentiyi özelleştirmek için ayarları buradan düzenleyebilirsiniz.', 'woo-sticky-cart');
    }

    add_settings_field('wsc_cart_text', __('Sepet Yazısı', 'woo-sticky-cart'), 'wsc_cart_text_callback', 'wsc-cart-settings', 'wsc_cart_settings_section');

    add_settings_field('wsc_custom_css', __('Özel CSS', 'woo-sticky-cart'), 'wsc_cart_custom_css_callback', 'wsc-cart-settings', 'wsc_cart_settings_section' );

    if (isset($_POST['custom_css'])) {
        $custom_css = wp_strip_all_tags($_POST['custom_css']);
        file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $custom_css);
    }

    if ( !get_option( 'wsc_cart_text' ) ) {
        update_option( 'wsc_cart_text', __( 'Sepete Ekle', 'woo-sticky-cart' ) );
    }
}

function wsc_cart_text_callback() {
    $wsc_cart_text = esc_attr(get_option('wsc_cart_text'));
    echo '<input type="text" name="wsc_cart_text" value="' . $wsc_cart_text . '">';
}

function wsc_cart_custom_css_callback() {
	$wsc_custom_css = file_get_contents(plugin_dir_path(__FILE__) . 'style.css');
    echo '<div style="height: 500px; width: 100%;" id="wsc-code-editor">'. $wsc_custom_css . '</div>';
    echo '<textarea name="custom_css" id="custom_css_textarea" style="display: none;"></textarea>';
    ?>
    <script>
        jQuery(document).ready(function() {
            var editor = ace.edit("wsc-code-editor");
            editor.setTheme("ace/theme/monokai");
            editor.session.setMode("ace/mode/css");
            editor.setAutoScrollEditorIntoView(true);

            setInterval(function(){
                let cssContent = editor.getValue();
                jQuery('#custom_css_textarea').val(cssContent);
            }, 1);
        });
    </script>
    <?php
}