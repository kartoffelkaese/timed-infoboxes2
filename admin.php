<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

function timed_infoboxes_admin_menu() {
    add_menu_page(
        'Infoboxen Verwaltung',
        'Infoboxen',
        'manage_options',
        'timed-infoboxes',
        'timed_infoboxes_admin_page',
        'dashicons-info',
        30
    );
}
add_action('admin_menu', 'timed_infoboxes_admin_menu');

function timed_infoboxes_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'timed_infoboxes';
    
    // Debugging: Überprüfen, ob die Tabelle existiert
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if (!$table_exists) {
        echo '<div class="notice notice-error"><p>Die Infoboxen-Tabelle existiert nicht. Bitte deaktivieren und reaktivieren Sie das Plugin.</p></div>';
        return;
    }
    
    // Hole Infobox zum Bearbeiten, falls eine ID übergeben wurde
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $edit_box = null;
    if ($edit_id > 0) {
        $edit_box = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
        if (!$edit_box) {
            echo '<div class="notice notice-error"><p>Infobox nicht gefunden.</p></div>';
            $edit_id = 0;
        }
    }
    
    // Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $result = $wpdb->insert(
                $table_name,
                [
                    'infobox_id' => sanitize_text_field($_POST['infobox_id']),
                    'anfang' => $_POST['anfang'] ? sanitize_text_field($_POST['anfang']) : null,
                    'ende' => $_POST['ende'] ? sanitize_text_field($_POST['ende']) : null,
                    'farbe' => sanitize_text_field($_POST['farbe']),
                    'sfarbe' => sanitize_text_field($_POST['sfarbe']),
                    'inhalt' => wp_kses_post($_POST['inhalt'])
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                echo '<div class="notice notice-error"><p>Fehler beim Speichern der Infobox: ' . $wpdb->last_error . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Infobox erfolgreich gespeichert.</p></div>';
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $result = $wpdb->update(
                $table_name,
                [
                    'infobox_id' => sanitize_text_field($_POST['infobox_id']),
                    'anfang' => $_POST['anfang'] ? sanitize_text_field($_POST['anfang']) : null,
                    'ende' => $_POST['ende'] ? sanitize_text_field($_POST['ende']) : null,
                    'farbe' => sanitize_text_field($_POST['farbe']),
                    'sfarbe' => sanitize_text_field($_POST['sfarbe']),
                    'inhalt' => wp_kses_post($_POST['inhalt'])
                ],
                ['id' => intval($_POST['id'])],
                ['%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            if ($result === false) {
                echo '<div class="notice notice-error"><p>Fehler beim Aktualisieren der Infobox: ' . $wpdb->last_error . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Infobox erfolgreich aktualisiert.</p></div>';
                // Redirect nach dem Speichern, um das Bearbeitungsformular zu schließen
                if (isset($_POST['redirect']) && $_POST['redirect'] === 'list') {
                    wp_redirect(add_query_arg(['page' => 'timed-infoboxes'], admin_url('admin.php')));
                    exit;
                }
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $result = $wpdb->delete($table_name, ['id' => intval($_POST['id'])], ['%d']);
            if ($result === false) {
                echo '<div class="notice notice-error"><p>Fehler beim Löschen der Infobox: ' . $wpdb->last_error . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Infobox erfolgreich gelöscht.</p></div>';
            }
        }
    }

    // Get all infoboxes with error checking
    // Sortiere nach Enddatum absteigend, dann nach Infobox-ID und Erstellungsdatum
    $infoboxes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY ende ASC, infobox_id, erstellt_am DESC");
    if ($infoboxes === null) {
        echo '<div class="notice notice-error"><p>Fehler beim Abrufen der Infoboxen: ' . $wpdb->last_error . '</p></div>';
        $infoboxes = [];
    }
    
    // Debugging: Anzahl der gefundenen Infoboxen anzeigen
    echo '<div class="notice notice-info"><p>Gefundene Infoboxen: ' . count($infoboxes) . '</p></div>';
    
    // Verfügbare Farben aus CSS-Variablen
    $available_colors = [
        'black' => 'Schwarz',
        'white' => 'Weiß',
        'purple' => 'Lila',
        'green' => 'Grün',
        'grey' => 'Grau',
        'red' => 'Rot',
        'mtgreen' => 'MT Grün'
    ];
    ?>
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --purple: #a50775;
            --green: #3ead48;
            --grey: #8c8c8c;
            --red: #f00;
            --mtgreen: #3ead48;
            --bradius: 3px;
        }
    </style>
    <div class="wrap">
        <h1>Infoboxen Verwaltung</h1>
        
        <?php if ($edit_id > 0 && $edit_box): ?>
        <h2>Infobox bearbeiten</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo esc_attr($edit_box->id); ?>">
            <input type="hidden" name="redirect" value="list">
            <table class="form-table">
                <tr>
                    <th><label for="infobox_id">Infobox ID</label></th>
                    <td><input type="text" name="infobox_id" id="infobox_id" class="regular-text" value="<?php echo esc_attr($edit_box->infobox_id); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="anfang">Startdatum</label></th>
                    <td><input type="date" name="anfang" id="anfang" value="<?php echo esc_attr($edit_box->anfang); ?>"></td>
                </tr>
                <tr>
                    <th><label for="ende">Enddatum</label></th>
                    <td><input type="date" name="ende" id="ende" value="<?php echo esc_attr($edit_box->ende); ?>"></td>
                </tr>
                <tr>
                    <th><label for="farbe">Hintergrundfarbe</label></th>
                    <td>
                        <select name="farbe" id="farbe" required>
                            <?php foreach ($available_colors as $color_key => $color_name): ?>
                                <option value="<?php echo esc_attr($color_key); ?>" <?php selected($edit_box->farbe, $color_key); ?>>
                                    <?php echo esc_html($color_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Wählen Sie eine der vordefinierten Farben aus.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfarbe">Schriftfarbe</label></th>
                    <td>
                        <select name="sfarbe" id="sfarbe">
                            <?php foreach ($available_colors as $color_key => $color_name): ?>
                                <option value="<?php echo esc_attr($color_key); ?>" <?php selected($edit_box->sfarbe, $color_key); ?>>
                                    <?php echo esc_html($color_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Wählen Sie eine der vordefinierten Farben aus.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="inhalt">Inhalt</label></th>
                    <td><?php wp_editor($edit_box->inhalt, 'inhalt', ['textarea_rows' => 5]); ?></td>
                </tr>
            </table>
            <?php submit_button('Infobox aktualisieren'); ?>
            <a href="<?php echo add_query_arg(['page' => 'timed-infoboxes'], admin_url('admin.php')); ?>" class="button">Abbrechen</a>
        </form>
        <?php else: ?>
        <h2>Neue Infobox erstellen</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="add">
            <table class="form-table">
                <tr>
                    <th><label for="infobox_id">Infobox ID</label></th>
                    <td><input type="text" name="infobox_id" id="infobox_id" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="anfang">Startdatum</label></th>
                    <td><input type="date" name="anfang" id="anfang"></td>
                </tr>
                <tr>
                    <th><label for="ende">Enddatum</label></th>
                    <td><input type="date" name="ende" id="ende"></td>
                </tr>
                <tr>
                    <th><label for="farbe">Hintergrundfarbe</label></th>
                    <td>
                        <select name="farbe" id="farbe" required>
                            <?php foreach ($available_colors as $color_key => $color_name): ?>
                                <option value="<?php echo esc_attr($color_key); ?>">
                                    <?php echo esc_html($color_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Wählen Sie eine der vordefinierten Farben aus.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfarbe">Schriftfarbe</label></th>
                    <td>
                        <select name="sfarbe" id="sfarbe">
                            <?php foreach ($available_colors as $color_key => $color_name): ?>
                                <option value="<?php echo esc_attr($color_key); ?>" <?php selected($color_key, 'black'); ?>>
                                    <?php echo esc_html($color_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Wählen Sie eine der vordefinierten Farben aus.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="inhalt">Inhalt</label></th>
                    <td><?php wp_editor('', 'inhalt', ['textarea_rows' => 5]); ?></td>
                </tr>
            </table>
            <?php submit_button('Infobox speichern'); ?>
        </form>
        <?php endif; ?>

        <h2>Vorhandene Infoboxen</h2>
        <?php if (empty($infoboxes)): ?>
            <p>Keine Infoboxen gefunden.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Infobox ID</th>
                    <th>Inhalt</th>
                    <th>Zeitraum</th>
                    <th>Farben</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($infoboxes as $box): ?>
                <tr>
                    <td><?php echo esc_html($box->infobox_id); ?></td>
                    <td><?php echo wp_kses_post($box->inhalt); ?></td>
                    <td>
                        <?php 
                        echo $box->anfang ? esc_html($box->anfang) : 'Sofort';
                        echo ' - ';
                        echo $box->ende ? esc_html($box->ende) : 'Unbegrenzt';
                        ?>
                    </td>
                    <td>
                        <span style="background-color: var(--<?php echo esc_attr($box->farbe); ?>); color: var(--<?php echo esc_attr($box->sfarbe); ?>); padding: 2px 5px; border-radius: 3px;">
                            Vorschau
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo add_query_arg(['page' => 'timed-infoboxes', 'edit' => $box->id], admin_url('admin.php')); ?>" class="button button-small">Bearbeiten</a>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo esc_attr($box->id); ?>">
                            <button type="submit" class="button button-small" onclick="return confirm('Wirklich löschen?')">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
} 