<?php
declare(strict_types=1);

/*
Plugin Name: Timed Infoboxes2
Plugin URI: https://github.com/kartoffelkaese/timed-infoboxes2
Description: Plugin für Funktionen zur zeitlichen Anzeige von Infoboxen in Wordpress
Author: Martin Urban
Author URI: https://github.com/kartoffelkaese/timed-infoboxes2
Version: 3.0
Requires PHP: 8.3
*/

/* Verbiete den direkten Zugriff auf die Plugin-Datei */
if (!defined('ABSPATH')) exit;

// Lade Admin-Funktionen
require_once plugin_dir_path(__FILE__) . 'admin.php';

/**
 * Registriert und lädt die Plugin-spezifischen Styles
 */
function timed_infoboxes_enqueue_styles() {
    wp_enqueue_style(
        'timed-infoboxes-styles',
        plugin_dir_url(__FILE__) . 'css/timed-infoboxes.css',
        [],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'timed_infoboxes_enqueue_styles');

/**
 * Installationsfunktion für die Datenbank
 */
function timed_infoboxes_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'timed_infoboxes';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        infobox_id varchar(50) NOT NULL,
        anfang date DEFAULT NULL,
        ende date DEFAULT NULL,
        farbe varchar(50) NOT NULL,
        sfarbe varchar(50) DEFAULT 'black',
        inhalt text NOT NULL,
        erstellt_am datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY infobox_id (infobox_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Deinstallationsfunktion für die Datenbank
 */
function timed_infoboxes_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'timed_infoboxes';
    
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Registriere Aktivierungs- und Deaktivierungshooks
register_activation_hook(__FILE__, 'timed_infoboxes_install');
register_deactivation_hook(__FILE__, 'timed_infoboxes_uninstall');

/**
 * Infobox Shortcode Handler
 */
function infobox_handler(array $atts = [], ?string $content = null, string $tag = ''): ?string 
{
    if (!isset($atts['id'])) {
        error_log("Timed Infoboxes: Erforderlicher Parameter 'id' fehlt");
        return null;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'timed_infoboxes';
    
    // Hole alle Infoboxen mit der angegebenen ID
    $infoboxes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE infobox_id = %s",
        $atts['id']
    ));

    if (empty($infoboxes)) {
        return null;
    }

    $output = '';
    $heute = new DateTime('today');

    foreach ($infoboxes as $box) {
        try {
            $enddatum = new DateTime($box->ende);
            
            $shouldDisplay = match(true) {
                empty($box->anfang) => $enddatum > $heute,
                default => (new DateTime($box->anfang)) <= $heute && $enddatum > $heute
            };
            
            if ($shouldDisplay) {
                $output .= generate_infobox($box->farbe, $box->sfarbe, $box->inhalt);
            }
        } catch (Exception $e) {
            error_log("Timed Infoboxes Error: " . $e->getMessage());
            continue;
        }
    }

    return $output;
}

/**
 * Generiert das HTML für die Infobox
 */
function generate_infobox(string $farbe, string $sfarbe, string $content): string 
{
    // Zeilenumbrüche in HTML-Zeilenumbrüche umwandeln
    $formatted_content = nl2br(wp_kses_post($content));
    
    return sprintf(
        '<div class="blockdiv"><div class="block" style="background-color:var(--%s);color:var(--%s);">%s</div></div>',
        esc_attr($farbe),
        esc_attr($sfarbe),
        $formatted_content
    );
}

add_shortcode('infobox', 'infobox_handler');
?>
