<?php
/**
 * Plugin Name: OpenLigaWP - Live Sportdaten
 * Plugin URI: https://wordpress.org/plugins/openligawp/
 * Description: Zeige Live-Fussballdaten (Bundesliga, etc.) mit Spieltagen und Tabelle auf deiner Website an.
 * Version: 2.1
 * Author: Frank Kemper
 * Author URI: https://profiles.wordpress.org/frankkemper/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openligawp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPENLIGAWP_VERSION', '2.1');
define('OPENLIGAWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPENLIGAWP_PLUGIN_URL', plugin_dir_url(__FILE__));

class OpenLigaWP {

    private $api_base = 'https://api.openligadb.de';

    public function __construct() {
        add_action('admin_menu', [$this, 'create_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        add_shortcode('olwp_dashboard', [$this, 'render_dashboard']);

        add_action('wp_ajax_olwp_load_data', [$this, 'ajax_load_data']);
        add_action('wp_ajax_nopriv_olwp_load_data', [$this, 'ajax_load_data']);
        add_action('wp_ajax_olwp_get_groups', [$this, 'ajax_get_groups']);
        add_action('wp_ajax_nopriv_olwp_get_groups', [$this, 'ajax_get_groups']);
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'openligawp',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function create_admin_menu() {
        add_options_page(
            __('OpenLigaWP Konfiguration', 'openligawp'),
            'OpenLigaWP',
            'manage_options',
            'openligawp',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('olwp_settings_group', 'olwp_leagues_list', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => "1. Bundesliga|bl1\n2. Bundesliga|bl2\n3. Liga|bl3"
        ]);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('OpenLigaWP Konfiguration', 'openligawp'); ?></h1>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-start;">
                <div style="flex: 2; min-width: 300px;">
                    <form method="post" action="options.php" class="card" style="padding: 20px;">
                        <h3><?php echo esc_html__('Ligen verwalten', 'openligawp'); ?></h3>
                        <?php settings_fields('olwp_settings_group'); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php echo esc_html__('Deine Ligenliste', 'openligawp'); ?></th>
                                <td>
                                    <textarea name="olwp_leagues_list" rows="12" class="large-text code"><?php echo esc_textarea(get_option('olwp_leagues_list', "1. Bundesliga|bl1\n2. Bundesliga|bl2\n3. Liga|bl3")); ?></textarea>
                                    <p class="description"><?php echo esc_html__('Format: Name|Shortcut (pro Zeile)', 'openligawp'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <div class="card" style="padding: 20px;">
                        <h2><?php echo esc_html__('Anleitung', 'openligawp'); ?></h2>
                        <p><?php echo esc_html__('Shortcode:', 'openligawp'); ?> <code>[olwp_dashboard]</code></p>
                        <h3><?php echo esc_html__('Verfuegbare Liga-Shortcuts', 'openligawp'); ?></h3>
                        <ul>
                            <li><code>bl1</code> - 1. Bundesliga</li>
                            <li><code>bl2</code> - 2. Bundesliga</li>
                            <li><code>bl3</code> - 3. Liga</li>
                            <li><code>dfb</code> - DFB-Pokal</li>
                            <li><code>cl</code> - Champions League</li>
                        </ul>
                        <p><?php echo esc_html__('Weitere Ligen auf openligadb.de', 'openligawp'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'olwp-style',
            OPENLIGAWP_PLUGIN_URL . 'style.css',
            [],
            OPENLIGAWP_VERSION
        );

        wp_enqueue_script(
            'olwp-script',
            OPENLIGAWP_PLUGIN_URL . 'script.js',
            ['jquery'],
            OPENLIGAWP_VERSION,
            true
        );

        wp_localize_script('olwp-script', 'olwp_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('olwp_nonce')
        ]);
    }

    private function fetch_data($endpoint, $cache_key, $cache_time = 3600) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $url = $this->api_base . $endpoint;
        $args = [
            'timeout' => 15,
            'sslverify' => true,
            'user-agent' => 'OpenLigaWP WordPress Plugin/' . OPENLIGAWP_VERSION
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return ['error_msg' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) && $body !== '[]') {
            return ['error_msg' => __('API Fehler: Keine Daten erhalten', 'openligawp')];
        }

        set_transient($cache_key, $data, $cache_time);
        return $data;
    }

    public function render_dashboard($atts) {
        $leagues_raw = get_option('olwp_leagues_list', "1. Bundesliga|bl1");
        $leagues_lines = explode("\n", $leagues_raw);
        $leagues = [];

        foreach ($leagues_lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) == 2) {
                $leagues[] = [
                    'name' => trim($parts[0]),
                    'shortcut' => trim($parts[1])
                ];
            }
        }

        $current_year = (int) date('Y');
        $season_start = ((int) date('m') > 6) ? $current_year : $current_year - 1;

        ob_start();
        ?>
        <div class="olwp-dashboard">
            <div class="olwp-controls">
                <select id="olwp-season" class="olwp-input">
                    <?php
                    for ($i = 0; $i < 3; $i++) {
                        $y = $season_start - $i;
                        printf(
                            '<option value="%d">%s</option>',
                            esc_attr($y),
                            esc_html(sprintf(__('Saison %d/%d', 'openligawp'), $y, $y + 1))
                        );
                    }
                    ?>
                </select>

                <select id="olwp-league" class="olwp-input">
                    <option value="" disabled selected><?php echo esc_html__('Liga waehlen...', 'openligawp'); ?></option>
                    <?php foreach ($leagues as $l) : ?>
                        <option value="<?php echo esc_attr($l['shortcut']); ?>"><?php echo esc_html($l['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="olwp-group" class="olwp-input" disabled>
                    <option value=""><?php echo esc_html__('(Erst Liga waehlen)', 'openligawp'); ?></option>
                </select>

                <button id="olwp-refresh" type="button" class="olwp-btn"><?php echo esc_html__('Aktualisieren', 'openligawp'); ?></button>
            </div>

            <div id="olwp-content" class="olwp-loading-area">
                <p><?php echo esc_html__('Waehle eine Liga und klicke auf Aktualisieren.', 'openligawp'); ?></p>
            </div>

            <div style="margin-top: 15px; font-size: 0.8rem; color: #888; text-align: right; border-top: 1px solid #eee; padding-top: 5px;">
                <?php echo esc_html__('Datenquelle:', 'openligawp'); ?>
                <a href="https://www.openligadb.de/" target="_blank" rel="nofollow noopener">OpenLigaDB</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_groups() {
        check_ajax_referer('olwp_nonce', 'nonce');

        $league = isset($_POST['league']) ? sanitize_text_field(wp_unslash($_POST['league'])) : '';
        $season = isset($_POST['season']) ? intval($_POST['season']) : 0;

        if (empty($league) || empty($season)) {
            wp_send_json_error(['message' => __('Ungueltige Parameter', 'openligawp')]);
        }

        $groups = [];
        $cache_key = "olwp_groups_{$league}_{$season}";

        $official_groups = $this->fetch_data(
            "/getavailgroups/{$league}/{$season}",
            $cache_key . '_official',
            DAY_IN_SECONDS
        );

        if (!empty($official_groups) && !isset($official_groups['error_msg'])) {
            $groups = $official_groups;
        } else {
            $all_matches = $this->fetch_data(
                "/getmatchdata/{$league}/{$season}",
                "olwp_fullseason_{$league}_{$season}",
                DAY_IN_SECONDS
            );

            if (!empty($all_matches) && !isset($all_matches['error_msg'])) {
                $extracted = [];
                $seen = [];
                foreach ($all_matches as $m) {
                    if (isset($m['group'])) {
                        $gid = (int) $m['group']['groupOrderID'];
                        if (!in_array($gid, $seen, true)) {
                            $extracted[] = [
                                'groupOrderID' => $gid,
                                'groupName' => sanitize_text_field($m['group']['groupName'])
                            ];
                            $seen[] = $gid;
                        }
                    }
                }
                usort($extracted, function ($a, $b) {
                    return $a['groupOrderID'] - $b['groupOrderID'];
                });
                $groups = $extracted;
            }
        }

        $current_id = null;
        $curr_data = $this->fetch_data("/getcurrentgroup/{$league}", "olwp_curr_{$league}", HOUR_IN_SECONDS);
        if (!empty($curr_data) && isset($curr_data['groupOrderID'])) {
            $current_id = (int) $curr_data['groupOrderID'];
        }

        if (empty($groups)) {
            for ($i = 1; $i <= 34; $i++) {
                $groups[] = [
                    'groupOrderID' => $i,
                    'groupName' => sprintf(__('%d. Spieltag', 'openligawp'), $i)
                ];
            }
        }

        wp_send_json_success(['list' => $groups, 'currentId' => $current_id]);
    }

    public function ajax_load_data() {
        check_ajax_referer('olwp_nonce', 'nonce');

        $league = isset($_POST['league']) ? sanitize_text_field(wp_unslash($_POST['league'])) : '';
        $season = isset($_POST['season']) ? intval($_POST['season']) : 0;
        $group_order_id = isset($_POST['group_order_id']) ? intval($_POST['group_order_id']) : 0;

        if (empty($league) || empty($season) || empty($group_order_id)) {
            wp_send_json_error(['message' => __('Ungueltige Parameter', 'openligawp')]);
        }

        $match_cache = "olwp_matches_{$league}_{$season}_{$group_order_id}";
        $matches = $this->fetch_data(
            "/getmatchdata/{$league}/{$season}/{$group_order_id}",
            $match_cache,
            60
        );

        $table_cache = "olwp_table_{$league}_{$season}";
        $table = $this->fetch_data("/getbltable/{$league}/{$season}", $table_cache, 30 * MINUTE_IN_SECONDS);

        $html = $this->render_matches_and_table($matches, $table);
        wp_send_json_success(['html' => $html]);
    }

    private function render_matches_and_table($matches, $table) {
        ob_start();

        echo '<div class="olwp-grid">';

        echo '<div class="olwp-column"><h3>' . esc_html__('Spiele', 'openligawp') . '</h3><ul class="olwp-match-list">';

        if ($matches && !isset($matches['error_msg'])) {
            foreach ($matches as $match) {
                $this->render_match_item($match);
            }
        } else {
            echo '<li>' . esc_html__('Keine Spiele gefunden (oder Ladefehler).', 'openligawp') . '</li>';
        }

        echo '</ul></div>';

        echo '<div class="olwp-column"><h3>' . esc_html__('Tabelle', 'openligawp') . '</h3>';

        if ($table && !isset($table['error_msg'])) {
            echo '<table class="olwp-table"><thead><tr><th>#</th><th>' . esc_html__('Team', 'openligawp') . '</th><th>' . esc_html__('Pkt', 'openligawp') . '</th></tr></thead><tbody>';
            $pos = 1;
            foreach ($table as $row) {
                $icon = '';
                if (!empty($row['teamIconUrl'])) {
                    $icon_url = esc_url($row['teamIconUrl']);
                    $icon = sprintf(
                        '<img src="%s" class="olwp-logo-mini" style="width:18px;height:18px;object-fit:contain;vertical-align:middle;display:inline-block;" alt="">',
                        $icon_url
                    );
                }

                $team_name = substr(sanitize_text_field($row['teamName']), 0, 18);
                $points = (int) $row['points'];

                echo '<tr>';
                echo '<td>' . esc_html($pos) . '.</td>';
                echo '<td>' . $icon . ' ' . esc_html($team_name) . '</td>';
                echo '<td><strong>' . esc_html($points) . '</strong></td>';
                echo '</tr>';
                $pos++;
            }
            echo '</tbody></table>';
        }

        echo '</div></div>';

        return ob_get_clean();
    }

    private function render_match_item($match) {
        $team1 = sanitize_text_field($match['team1']['teamName'] ?? '');
        $team2 = sanitize_text_field($match['team2']['teamName'] ?? '');
        $icon1 = !empty($match['team1']['teamIconUrl']) ? esc_url($match['team1']['teamIconUrl']) : '';
        $icon2 = !empty($match['team2']['teamIconUrl']) ? esc_url($match['team2']['teamIconUrl']) : '';

        $result_str = '- : -';
        $css_class = '';
        $goal_details = '';

        if (!empty($match['matchResults'])) {
            $last_res = end($match['matchResults']);
            $result_str = (int) $last_res['pointsTeam1'] . ' : ' . (int) $last_res['pointsTeam2'];
            if (empty($match['matchIsFinished'])) {
                $css_class = 'olwp-live';
            }
        } elseif (isset($match['matchDateTime'])) {
            $result_str = date('H:i', strtotime($match['matchDateTime']));
        }

        if (!empty($match['goals'])) {
            $goal_details = '<div class="olwp-goal-list">';
            $scorer_parts = [];
            foreach ($match['goals'] as $g) {
                $min = isset($g['matchMinute']) ? (int) $g['matchMinute'] . "'" : '';
                $name = sanitize_text_field($g['goalGetterName'] ?? '');
                if ($name) {
                    $scorer_parts[] = sprintf(
                        '%s %s (%d:%d)',
                        esc_html($min),
                        esc_html($name),
                        (int) $g['scoreTeam1'],
                        (int) $g['scoreTeam2']
                    );
                }
            }
            $goal_details .= implode(', ', $scorer_parts);
            $goal_details .= '</div>';
        }

        $img_style = 'width:28px;height:28px;object-fit:contain;display:inline-block;border:none;';

        echo '<li class="olwp-match-item ' . esc_attr($css_class) . '">';
        echo '<div class="match-main-row">';

        echo '<div class="match-team home">';
        if ($icon1) {
            printf('<img src="%s" class="olwp-logo-img" style="%s" alt=""> ', $icon1, esc_attr($img_style));
        }
        echo '<span>' . esc_html($team1) . '</span>';
        echo '</div>';

        echo '<div class="match-score">' . esc_html($result_str) . '</div>';

        echo '<div class="match-team guest">';
        echo '<span>' . esc_html($team2) . '</span>';
        if ($icon2) {
            printf(' <img src="%s" class="olwp-logo-img" style="%s" alt="">', $icon2, esc_attr($img_style));
        }
        echo '</div>';

        echo '</div>';
        if ($goal_details) {
            echo $goal_details;
        }
        echo '</li>';
    }
}

new OpenLigaWP();
