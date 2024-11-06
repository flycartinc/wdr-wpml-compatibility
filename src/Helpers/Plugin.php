<?php

namespace WDR_WPML\Helpers;

defined('ABSPATH') || exit;

class Plugin
{
    /**
     * Active plugins.
     *
     * @var array
     */
    private static $active_plugins;

    /**
     * Check dependencies.
     *
     * @return bool
     */
    public static function checkDependencies($requires, $plugin_name)
    {
        $error_message = self::getDependenciesError($requires, $plugin_name);
        if (!empty($error_message)) {
            self::adminNotice($error_message, 'error');
            return false;
        }
        return true;
    }

    /**
     * Get all active plugins.
     *
     * @return array
     */
    public static function activePlugins()
    {
        if (!isset(self::$active_plugins)) {
            $active_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
            if (function_exists('is_multisite') && is_multisite()) {
                $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
            }
            self::$active_plugins = $active_plugins;
        }
        return self::$active_plugins;
    }

    /**
     * Check if the plugin is active or not.
     *
     * @param string $file
     * @return bool
     */
    public static function isActive($file)
    {
        $active_plugins = self::activePlugins();
        return in_array($file, $active_plugins) || array_key_exists($file, $active_plugins);
    }

    /**
     * Get plugin data.
     *
     * @param string $file
     * @return array
     */
    public static function getData($file)
    {
        $plugin_file = ABSPATH . 'wp-content/plugins/' . $file;
        if (file_exists($plugin_file) && function_exists('get_plugin_data')) {
            return get_plugin_data($plugin_file);
        }
        return [];
    }

    /**
     * Get plugin version.
     *
     * @param string $file
     * @return string|null
     */
    public static function getVersion($file)
    {
        $data = self::getData($file);
        return $data['Version'] ?? null;
    }

    /**
     * Returns error message if requirement not satisfied.
     *
     * @param array $requires
     * @param string $plugin_name
     * @return string|false
     */
    public static function getDependenciesError($requires, $plugin_name = '')
    {
        $package_requirement_short = __('Requires %s plugin.', 'wdr-wpml-compatibility');
        $package_requirement = __('%1$s requires %2$s plugin to be installed and active.', 'wdr-wpml-compatibility');

        $version_requirement_short = __('Requires %1$s version %2$s or above.', 'wdr-wpml-compatibility');
        $version_requirement = __('%1$s requires %2$s version %3$s or above.', 'wdr-wpml-compatibility');

        if (!empty($requires['php'])) {
            if (!version_compare(PHP_VERSION, $requires['php'], '>=')) {
                return empty($plugin_name) ? sprintf($version_requirement_short, 'PHP', $requires['php'])
                    : sprintf($version_requirement, $plugin_name, 'PHP', $requires['php']);
            }
        }

        global $wp_version;
        if (!empty($requires['wordpress'])) {
            if (!version_compare($wp_version, $requires['wordpress'], '>=')) {
                $wordpress = __('WordPress', 'wdr-wpml-compatibility');
                return empty($plugin_name) ? sprintf($version_requirement_short, $wordpress, $requires['wordpress'])
                    : sprintf($version_requirement, $plugin_name, $wordpress, $requires['wordpress']);
            }
        }

        if (!empty($requires['woocommerce'])) {
            $error = self::getDependenciesError(['plugins' => [[
                'name' => 'Discount Rules',
                'version' => $requires['woocommerce'],
                'file' => 'woocommerce/woocommerce.php',
                'url' => 'https://wordpress.org/plugins/woocommerce',
            ]]], $plugin_name);
            if ($error !== false) {
                return $error;
            }
        }

        if (!empty($requires['wdr_core'])) {
            $error = self::getDependenciesError(['plugins' => [[
                'name' => 'Discount Rules',
                'version' => $requires['wdr_core'],
                'file' => 'woo-discount-rules/woo-discount-rules.php',
                'url' => 'https://wordpress.org/plugins/woo-discount-rules',
            ]]], $plugin_name);
            if ($error !== false) {
                return $error;
            }
        }

        if (!empty($requires['wdr_pro'])) {
            $error = self::getDependenciesError(['plugins' => [[
                'name' => 'Discount Rules PRO',
                'version' => $requires['wdr_pro'],
                'file' => 'woo-discount-rules/woo-discount-rules-pro.php',
                'url' => 'https://www.flycart.org/products/wordpress/woocommerce-discount-rules',
            ]]], $plugin_name);
            if ($error !== false) {
                return $error;
            }
        }

        foreach ($requires['plugins'] ?? [] as $plugin) {
            if (!isset($plugin['name']) || !isset($plugin['file'])) {
                continue;
            }

            $formatted_name = $plugin['name'];
            if (isset($plugin['url'])) {
                $formatted_name = '<a href="' . esc_url($plugin['url']) . '" target="_blank">' . esc_html($formatted_name) . '</a>';
            }

            if (!self::isActive($plugin['file'])) {
                return empty($plugin_name) ? sprintf($package_requirement_short, $formatted_name)
                    : sprintf($package_requirement, $plugin_name, $formatted_name);
            }

            if (!empty($plugin['version'])) {
                $plugin_version = self::getVersion($plugin['file']);
                if (!empty($plugin_version) && !version_compare($plugin_version, $plugin['version'], '>=')) {
                    return empty($plugin_name) ? sprintf($version_requirement_short, $formatted_name, $plugin['version'])
                        : sprintf($version_requirement, $plugin_name, $formatted_name, $plugin['version']);
                }
            }
        }

        return false;
    }

    /**
     * Add admin notice.
     *
     * @param string $message
     * @param string $status
     * @return void
     */
    private static function adminNotice($message, $status = 'success')
    {
        add_action('admin_notices', function () use ($message, $status) {
            ?>
            <div class="notice notice-<?php echo esc_attr($status); ?>">
                <p><?php echo wp_kses_post($message); ?></p>
            </div>
            <?php
        }, 1);
    }
}
