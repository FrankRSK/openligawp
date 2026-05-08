<?php
/**
 * OpenLigaWP Uninstall
 *
 * Wird aufgerufen, wenn das Plugin gelöscht wird.
 * Entfernt alle gespeicherten Optionen und Transients aus der Datenbank.
 */

// Wenn die Deinstallation nicht von WordPress aufgerufen wurde, beenden
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Einzelne Option löschen 
delete_option('olwp_leagues_list');

// Transients löschen (Cache aufräumen)
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_olwp_%'"
);
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_olwp_%'"
);
