<?php
/**
 * Plugin Name: WC Dynamic Price Modifier
 * Description: Dynamically modify displayed WooCommerce prices without changing the database.
 * Version: 1.3
 * Author: Alexander Nemirov
 * Text Domain: wc-dynamic-price
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check WooCommerce on activation
register_activation_hook(__FILE__, 'wc_dpm_check_woocommerce');
function wc_dpm_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce.');
    }
}

// Initialize after all plugins loaded
add_action('plugins_loaded', 'wc_dpm_init', 20);

function wc_dpm_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>WC Dynamic Price Modifier:</strong> WooCommerce is required.</p></div>';
        });
        return;
    }

    new WC_Dynamic_Price_Modifier();
}

class WC_Dynamic_Price_Modifier {

    private $discount_percent;
    private $action_type;
    private $enabled;
    private $excluded_products;

    public function __construct() {
        $this->enabled = get_option('wc_dpm_enabled', 'no') === 'yes';
        $this->discount_percent = floatval(get_option('wc_dpm_discount_percent', 20));
        $this->action_type = get_option('wc_dpm_action_type', 'decrease');
        $this->excluded_products = $this->get_excluded_products();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        if ($this->enabled) {
            if (!is_admin() || wp_doing_ajax()) {
                add_filter('woocommerce_product_get_price', array($this, 'modify_price'), 99, 2);
                add_filter('woocommerce_product_get_regular_price', array($this, 'modify_price'), 99, 2);
                add_filter('woocommerce_product_get_sale_price', array($this, 'modify_price'), 99, 2);

                add_filter('woocommerce_product_variation_get_price', array($this, 'modify_price'), 99, 2);
                add_filter('woocommerce_product_variation_get_regular_price', array($this, 'modify_price'), 99, 2);
                add_filter('woocommerce_product_variation_get_sale_price', array($this, 'modify_price'), 99, 2);

                add_filter('woocommerce_variation_prices_price', array($this, 'modify_variation_price'), 99, 3);
                add_filter('woocommerce_variation_prices_regular_price', array($this, 'modify_variation_price'), 99, 3);
                add_filter('woocommerce_variation_prices_sale_price', array($this, 'modify_variation_price'), 99, 3);

                add_filter('woocommerce_get_variation_prices_hash', array($this, 'add_hash_modifier'), 99, 1);
            }

            if (is_admin()) {
                add_action('woocommerce_product_options_pricing', array($this, 'show_dual_prices_simple'));
                add_action('woocommerce_variation_options_pricing', array($this, 'show_dual_prices_variation'), 10, 3);
                add_filter('manage_edit-product_columns', array($this, 'add_price_column'));
                add_action('manage_product_posts_custom_column', array($this, 'render_price_column'), 10, 2);
                add_action('admin_head', array($this, 'admin_styles'));
            }
        }
    }

    private function get_excluded_products() {
        $excluded = get_option('wc_dpm_excluded_products', '');
        if (empty($excluded)) return array();
        $ids = preg_split('/[\s,]+/', $excluded, -1, PREG_SPLIT_NO_EMPTY);
        $ids = array_filter(array_map('intval', $ids));
        return array_unique($ids);
    }

    private function is_product_excluded($product) {
        if (empty($this->excluded_products)) return false;
        $product_id = $product->get_id();
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
            return in_array($product_id, $this->excluded_products) || in_array($parent_id, $this->excluded_products);
        }
        return in_array($product_id, $this->excluded_products);
    }

    public function admin_styles() {
        echo '<style>
            .column-actual_price { width: 120px; }
            .wc-dpm-price-box { background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px; margin: 12px 0; }
            .wc-dpm-price-box.excluded { background: #fff3cd; border-left-color: #ffc107; }
            .wc-dpm-price-box table { width: 100%; border-collapse: collapse; }
            .wc-dpm-price-box td { padding: 4px 8px; }
            .wc-dpm-actual-price { color: #2271b1; font-weight: bold; font-size: 1.2em; }
            .wc-dpm-excluded-badge { background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        </style>';
    }

    public function modify_price($price, $product) {
        if ($price === '' || $price === null || !is_numeric($price)) return $price;
        if ($this->is_product_excluded($product)) return $price;
        return $this->calculate_new_price(floatval($price));
    }

    public function modify_variation_price($price, $variation, $product) {
        if ($price === '' || $price === null || !is_numeric($price)) return $price;
        if (is_numeric($variation)) {
            $variation_obj = wc_get_product($variation);
            if ($variation_obj && $this->is_product_excluded($variation_obj)) return $price;
        }
        return $this->calculate_new_price(floatval($price));
    }

    private function calculate_new_price($price) {
        if ($price <= 0) return $price;
        $multiplier = $this->action_type === 'decrease'
            ? (100 - $this->discount_percent) / 100
            : (100 + $this->discount_percent) / 100;
        $new_price = $price * $multiplier;
        $round_to = intval(get_option('wc_dpm_round_to', 0));
        if ($round_to > 0) {
            $new_price = round($new_price / $round_to) * $round_to;
        } else {
            $new_price = round($new_price, 2);
        }
        return max(0, $new_price);
    }

    public function add_hash_modifier($hash) {
        $hash[] = $this->discount_percent;
        $hash[] = $this->action_type;
        $hash[] = get_option('wc_dpm_round_to', 0);
        $hash[] = md5(serialize($this->excluded_products));
        return $hash;
    }

    public function add_price_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'price') {
                $new_columns['actual_price'] = 'Display Price';
            }
        }
        return $new_columns;
    }

    public function render_price_column($column, $post_id) {
        if ($column !== 'actual_price') return;
        if (!function_exists('wc_get_product')) return;
        $product = wc_get_product($post_id);
        if (!$product) return;
        $original = $product->get_regular_price();
        if ($original === '' || !is_numeric($original) || floatval($original) <= 0) { echo '—'; return; }
        $is_excluded = $this->is_product_excluded($product);
        if ($is_excluded) {
            echo '<span class="wc-dpm-actual-price">' . wc_price($original) . '</span>';
            echo '<br><span class="wc-dpm-excluded-badge">EXCLUDED</span>';
        } else {
            $modified = $this->calculate_new_price(floatval($original));
            $percent = round(abs(1 - $modified / floatval($original)) * 100);
            $label = $this->action_type === 'decrease' ? 'discount' : 'markup';
            echo '<span class="wc-dpm-actual-price">' . wc_price($modified) . '</span>';
            echo '<br><small style="color: #666;">(' . esc_html($percent) . '% ' . esc_html($label) . ')</small>';
        }
    }

    public function show_dual_prices_simple() {
        global $post;
        if (!$post || !function_exists('wc_get_product')) return;
        $product = wc_get_product($post->ID);
        if (!$product) return;
        $original = $product->get_regular_price();
        if ($original === '' || !is_numeric($original)) return;
        $is_excluded = $this->is_product_excluded($product);
        $box_class = $is_excluded ? 'wc-dpm-price-box excluded' : 'wc-dpm-price-box';
        echo '<div class="' . esc_attr($box_class) . '">';
        if ($is_excluded) {
            echo '<p style="margin: 0 0 10px 0;"><strong>Product excluded from price modification</strong></p>';
            echo '<table>';
            echo '<tr><td>Database price:</td><td><strong>' . wc_price($original) . '</strong></td></tr>';
            echo '<tr><td>Display price:</td><td><strong>' . wc_price($original) . '</strong> <span class="wc-dpm-excluded-badge">UNCHANGED</span></td></tr>';
            echo '</table>';
        } else {
            $modified = $this->calculate_new_price(floatval($original));
            $action_text = $this->action_type === 'decrease' ? 'discount' : 'markup';
            echo '<p style="margin: 0 0 10px 0;"><strong>Price modifier active (' . esc_html($this->discount_percent) . '% ' . esc_html($action_text) . ')</strong></p>';
            echo '<table>';
            echo '<tr><td>Database price:</td><td><strong>' . wc_price($original) . '</strong></td></tr>';
            echo '<tr><td>Display price:</td><td><span class="wc-dpm-actual-price">' . wc_price($modified) . '</span></td></tr>';
            echo '</table>';
        }
        echo '</div>';
    }

    public function show_dual_prices_variation($loop, $variation_data, $variation) {
        if (!function_exists('wc_get_product')) return;
        $variation_obj = wc_get_product($variation->ID);
        if (!$variation_obj) return;
        $original = $variation_obj->get_regular_price();
        if ($original === '' || !is_numeric($original)) return;
        $is_excluded = $this->is_product_excluded($variation_obj);
        if ($is_excluded) {
            echo '<div style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 8px; margin: 8px 0; clear: both;">';
            echo '<small><strong>Excluded:</strong> ' . wc_price($original) . ' <span class="wc-dpm-excluded-badge">UNCHANGED</span></small>';
            echo '</div>';
        } else {
            $modified = $this->calculate_new_price(floatval($original));
            echo '<div style="background: #f0f6fc; border-left: 3px solid #2271b1; padding: 8px; margin: 8px 0; clear: both;">';
            echo '<small><strong>Display:</strong> <span style="color: #2271b1;">' . wc_price($modified) . '</span> &nbsp;|&nbsp; <strong>Database:</strong> ' . wc_price($original) . '</small>';
            echo '</div>';
        }
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Price Modifier',
            'Price Modifier',
            'manage_woocommerce',
            'wc-dynamic-price',
            array($this, 'admin_page')
        );
    }

    public function register_settings() {
        register_setting('wc_dpm_settings', 'wc_dpm_enabled', array(
            'sanitize_callback' => function($val) { return $val === 'yes' ? 'yes' : 'no'; }
        ));
        register_setting('wc_dpm_settings', 'wc_dpm_discount_percent', array(
            'sanitize_callback' => function($val) { return max(0.1, min(99, floatval($val))); }
        ));
        register_setting('wc_dpm_settings', 'wc_dpm_action_type', array(
            'sanitize_callback' => function($val) { return in_array($val, ['decrease', 'increase']) ? $val : 'decrease'; }
        ));
        register_setting('wc_dpm_settings', 'wc_dpm_round_to', array(
            'sanitize_callback' => function($val) { $a = [0,1,10,50,100]; return in_array(intval($val), $a) ? intval($val) : 0; }
        ));
        register_setting('wc_dpm_settings', 'wc_dpm_excluded_products', array(
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
    }

    public function admin_page() {
        // Clear cache on settings update
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            if (function_exists('wc_delete_product_transients')) wc_delete_product_transients();
            delete_transient('wc_var_prices');
            global $wpdb;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '%_wc_var_prices_%'));
        }

        $enabled = get_option('wc_dpm_enabled', 'no');
        $discount = get_option('wc_dpm_discount_percent', 20);
        $action = get_option('wc_dpm_action_type', 'decrease');
        $round = get_option('wc_dpm_round_to', 0);
        $excluded = get_option('wc_dpm_excluded_products', '');
        ?>
        <div class="wrap">
            <h1>Dynamic Price Modifier</h1>

            <div class="notice notice-info">
                <p><strong>How it works:</strong> This plugin modifies prices only on the storefront and at checkout. Database prices remain unchanged.</p>
            </div>

            <?php if ($enabled === 'yes'): ?>
                <div class="notice notice-success">
                    <p><strong>Modifier is active.</strong> All prices are <?php echo $action === 'decrease' ? 'decreased' : 'increased'; ?> by <?php echo esc_html($discount); ?>%
                    <?php if (!empty($this->excluded_products)): ?>
                        <br><small>Excluded products: <?php echo count($this->excluded_products); ?></small>
                    <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><strong>Modifier is disabled.</strong> Prices are displayed without changes.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('wc_dpm_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th>Enable Modifier</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_dpm_enabled" value="yes" <?php checked($enabled, 'yes'); ?>>
                                Apply price modification
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_dpm_discount_percent">Percentage</label></th>
                        <td>
                            <input type="number" id="wc_dpm_discount_percent" name="wc_dpm_discount_percent"
                                   value="<?php echo esc_attr($discount); ?>" min="0.1" max="99" step="0.1" style="width: 100px;"> %
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_dpm_action_type">Action</label></th>
                        <td>
                            <select id="wc_dpm_action_type" name="wc_dpm_action_type">
                                <option value="decrease" <?php selected($action, 'decrease'); ?>>Decrease prices (discount)</option>
                                <option value="increase" <?php selected($action, 'increase'); ?>>Increase prices (markup)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_dpm_round_to">Rounding</label></th>
                        <td>
                            <select id="wc_dpm_round_to" name="wc_dpm_round_to">
                                <option value="0" <?php selected($round, 0); ?>>No rounding (2 decimals)</option>
                                <option value="1" <?php selected($round, 1); ?>>Whole number</option>
                                <option value="10" <?php selected($round, 10); ?>>Nearest 10</option>
                                <option value="50" <?php selected($round, 50); ?>>Nearest 50</option>
                                <option value="100" <?php selected($round, 100); ?>>Nearest 100</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_dpm_excluded_products">Exclude Products</label></th>
                        <td>
                            <textarea id="wc_dpm_excluded_products" name="wc_dpm_excluded_products"
                                      rows="5" cols="50" style="font-family: monospace;"
                                      placeholder="Enter product IDs separated by commas, spaces, or newlines&#10;Example: 123, 456, 789"><?php echo esc_textarea($excluded); ?></textarea>
                            <p class="description">
                                <strong>These products will show original prices without modification.</strong><br>
                                You can enter simple or variable product IDs (parent ID excludes all variations).<br>
                                Format: IDs separated by commas, spaces, or newlines. Example: <code>123, 456, 789</code>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <hr>

            <h2>Preview</h2>

            <?php if (function_exists('wc_get_products')): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Database Price</th>
                        <th>Display Price</th>
                        <th>Difference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $preview_products = wc_get_products(array('limit' => 15, 'status' => 'publish', 'type' => 'simple'));
                    if (empty($preview_products)) echo '<tr><td colspan="6">No products to display</td></tr>';
                    foreach ($preview_products as $product):
                        $original_price = $product->get_regular_price();
                        if ($original_price === '' || !is_numeric($original_price)) continue;
                        $original_float = floatval($original_price);
                        if ($original_float <= 0) continue;
                        $is_excluded = $this->is_product_excluded($product);
                        $modified_price = $is_excluded ? $original_float : $this->calculate_new_price($original_float);
                        $diff = $modified_price - $original_float;
                    ?>
                    <tr<?php if ($is_excluded) echo ' style="background: #fff9e6;"'; ?>>
                        <td><?php echo esc_html($product->get_id()); ?></td>
                        <td><?php echo esc_html($product->get_name()); ?></td>
                        <td><?php echo wc_price($original_price); ?></td>
                        <td><strong style="color: <?php echo $is_excluded ? '#666' : '#2271b1'; ?>;"><?php echo wc_price($modified_price); ?></strong></td>
                        <td style="color: <?php echo $diff < 0 ? 'green' : ($diff > 0 ? 'red' : '#666'); ?>">
                            <?php echo $is_excluded ? '—' : (($diff < 0 ? '' : '+') . wc_price($diff)); ?>
                        </td>
                        <td>
                            <?php if ($is_excluded): ?>
                                <span class="wc-dpm-excluded-badge">EXCLUDED</span>
                            <?php else: ?>
                                <span style="color: green;">Active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>WooCommerce not loaded</p>
            <?php endif; ?>
        </div>
        <?php
    }
}
