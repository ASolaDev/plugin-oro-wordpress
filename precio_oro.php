<?php
/**
 * Plugin Name: Oro en Tiempo Real
 * Description: Muestra el precio del oro en tiempo casi real.
 * Version: 1.0.0
 * Author: Antonio
 */

if (!defined('ABSPATH')) exit;

// Cargar CSS y JS
function gpl_enqueue_assets() {
    wp_enqueue_style('gpl-style', plugin_dir_url(__FILE__) . 'assets/css/styles.css');
    wp_enqueue_script('gpl-script', plugin_dir_url(__FILE__) . 'assets/scripts/oro.js', [], null, true);

    // Pasar datos de AJAX al JS
    wp_localize_script('gpl-script', 'gpl_ajax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'gpl_enqueue_assets');


// Obtener precio del oro desde API
function gpl_get_gold_price() {
    $api_key = 'TU_API_KEY'; // ← Cambiar por tu API Key
    $response = wp_remote_get('https://www.goldapi.io/api/XAU/EUR', [
        'headers' => [
            'x-access-token' => $api_key
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        error_log('gpl: wp_remote_get error: ' . $response->get_error_message());
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        error_log('gpl: API returned HTTP code ' . $code);
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        error_log('gpl: empty response body from API');
        return false;
    }

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('gpl: json decode error: ' . json_last_error_msg());
        return false;
    }

    if (!isset($data['price'])) {
        error_log('gpl: price field missing in API response');
        return false;
    }

    return [
        'price' => $data['price'],
        'change' => isset($data['ch']) ? $data['ch'] : 0,
        'change_percent' => isset($data['chp']) ? $data['chp'] : 0
    ];
}

// Obtener precio con caché de 5 minutos
function gpl_get_cached_gold_price() {
    $cached = get_transient('gpl_gold_price');

    if ($cached !== false) {
        return $cached;
    }

    // Si hay un error reciente, evitamos consultar de nuevo inmediatamente
    if (get_transient('gpl_gold_price_error')) {
        return false;
    }

    $gold = gpl_get_gold_price();
    if ($gold !== false) {
        set_transient('gpl_gold_price', $gold, 300); // 5 minutos
    } else {
        // Cachear el fallo por corto tiempo para no golpear la API repetidamente
        set_transient('gpl_gold_price_error', true, 60); // 1 minuto
    }

    return $gold;
}

// Shortcode para mostrar el precio
function gpl_gold_shortcode() {
    $gold = gpl_get_cached_gold_price();

    if (!$gold) {
        return '<p>No se pudo obtener el precio del oro.</p>';
    }

    $arrow = $gold['change'] >= 0 ? '▲' : '▼';

    return "
        <div id='gpl-gold-price' class='gold-price'>
            <strong>Oro:</strong> {$gold['price']} €/onza 
            <span class='change {$gold['change'] >= 0 ? 'up' : 'down'}'>
                {$arrow} {$gold['change_percent']}%
            </span>
        </div>
    ";
}
add_shortcode('gold_price', 'gpl_gold_shortcode');


// AJAX para refresco sin recargar
function gpl_ajax_gold_price() {
    $gold = gpl_get_cached_gold_price();

    if (!$gold) {
        wp_send_json_error('Error al obtener el precio.');
    }

    $arrow = $gold['change'] >= 0 ? '▲' : '▼';

    $html = "
        <strong>Oro:</strong> {$gold['price']} €/onza 
        <span class='change {$gold['change'] >= 0 ? 'up' : 'down'}'>
            {$arrow} {$gold['change_percent']}%
        </span>
    ";

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_nopriv_gpl_refresh', 'gpl_ajax_gold_price');
add_action('wp_ajax_gpl_refresh', 'gpl_ajax_gold_price');
