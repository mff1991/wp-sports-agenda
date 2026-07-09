<?php
/**
 * Plugin Name: wp-sports-agenda
 * Version: 5.8.0 
 * Description: Mòdul de gestió massiva des d'Excel per al CH Bordils amb extractor universal de dates.
* Author: Marc Fornes 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('CHB_PATH', plugin_dir_path(__FILE__));
define('CHB_URL', plugin_dir_url(__FILE__));

require_once CHB_PATH . 'includes/cpt.php';
require_once CHB_PATH . 'includes/taxonomies.php';
require_once CHB_PATH . 'includes/meta-boxes.php';

add_action('elementor/init', function() {
    if ( ! class_exists('\Elementor\Widget_Base') ) return;
    require_once CHB_PATH . 'includes/class-widget.php';
    add_action('elementor/widgets/register', function($widgets_manager){
        $widgets_manager->register( new \CHB_Resultats_Widget() );
    });
});

add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('chb-style', CHB_URL . 'assets/css/style.css');
});

/* Columnes d'Administració */
add_filter('manage_chb_partit_posts_columns', 'chb_afegir_columnes_partits_taula');
function chb_afegir_columnes_partits_taula($columns) {
    $nou_ordre = array();
    foreach($columns as $key => $value) {
        if ($key == 'date') {
            $nou_ordre['competicio'] = 'Competició / Torneig';
            $nou_ordre['rival'] = 'Partit / Equips';
            $nou_ordre['localitzacio'] = 'Localització';
            $nou_ordre['resultat'] = 'Resultat';
        }
        $nou_ordre[$key] = $value;
    }
    return $nou_ordre;
}

add_action('manage_chb_partit_posts_custom_column', 'chb_omplir_columnes_partits_taula', 10, 2);
function chb_omplir_columnes_partits_taula($column, $post_id) {
    switch ($column) {
        case 'competicio':
            $competicio = get_post_meta($post_id, 'competicio', true);
            echo !empty($competicio) ? '<span style="color:#0a7d3b; font-weight:600;">' . esc_html($competicio) . '</span>' : '<span style="color:#aaa; font-style:italic;">Lliga Regular</span>';
            break;
        case 'rival':
            $local = get_post_meta($post_id, 'equip_local', true); $visitant = get_post_meta($post_id, 'equip_rival', true);
            if (empty($local)) $local = 'CH Bordils';
            echo !empty($visitant) ? esc_html($local) . ' <span style="color:#aaa;">vs</span> ' . esc_html($visitant) : esc_html($local);
            break;
        case 'localitzacio':
            $localitzacio = get_post_meta($post_id, 'localitzacio', true);
            if (!empty($localitzacio)) {
                $color = (strtolower($localitzacio) == 'casa') ? '#0a7d3b' : (($localitzacio == 'neutral') ? '#2980b9' : '#666');
                echo '<span style="background:' . $color . '; color:#fff; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:bold; text-transform:uppercase;">' . esc_html($localitzacio) . '</span>';
            }
            break;
        case 'resultat':
            $local_res = get_post_meta($post_id, 'resultat_local', true); $visitant_res = get_post_meta($post_id, 'resultat_visitant', true);
            echo ($local_res !== '' && $visitant_res !== '') ? '<strong>' . esc_html($local_res) . ' - ' . esc_html($visitant_res) . '</strong>' : '<span style="color:#999; font-style:italic;">Pendent</span>';
            break;
    }
}

/* Interfície de Càrrega */
add_filter('views_edit-chb_partit', 'chb_restaurar_interficie_excel_original');
function chb_restaurar_interficie_excel_original($views) {
    $url_plantilla = admin_url('edit.php?post_type=chb_partit&chb_descarregar_plantilla=1');
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="background:#fff; border:1px solid #ccd0d4; padding:15px; margin:15px 0 20px 0; border-radius:8px; display:flex; justify-content:space-between; align-items:center; gap:15px; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
        <input type="hidden" name="action" value="chb_importar_excel_segur">
        <?php wp_nonce_field('chb_pujar_csv_nonce', 'chb_nonce'); ?>
        <div>
            <h4 style="margin:0 0 5px 0; color:#0a7d3b; font-size:14px;">Mòdul Automatitzat (Excel de Temporada)</h4>
            <p style="margin:0; font-size:12px; color:#666;">Crea nous equips o actualitza partits de forma massiva.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="<?php echo esc_url($url_plantilla); ?>" class="button button-secondary" style="border-color:#0a7d3b; color:#0a7d3b;">Descarregar Plantilla</a>
            <input type="file" name="chb_fitxer_csv" required style="font-size:12px;">
            <input type="submit" class="button button-primary" style="background:#0a7d3b; border-color:#0a7d3b;" value="Pujar Full Omplert">
        </div>
    </form>
    <?php
    return $views;
}

/* GENERADOR DE PLANTILLA AVANÇAT CON EXTRACCIÓ MULTI-KEY */
add_action('admin_init', 'chb_processar_descarga_plantilla_original');
function chb_processar_descarga_plantilla_original() {
    if (isset($_GET['chb_descarregar_plantilla']) && isset($_GET['post_type']) && $_GET['post_type'] == 'chb_partit') {
        if (!current_user_can('edit_posts')) wp_die('Permís denegat.');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=plantilla_partits_bordils.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, array('ID Partit', 'Categoria Base', 'Torneig o Competicio', 'Equip Local', 'Equip Visitant', 'Localitzacio', 'Resultat Local', 'Resultat Visitant', 'Estat', 'Data i Hora'), ';');
        
        $partits = get_posts(array('post_type'=>'chb_partit', 'numberposts'=>-1, 'post_status'=>'publish'));
        foreach ($partits as $partit) {
            $id = $partit->ID; $titol = $partit->post_title;
            if (strpos($titol, ' | ') !== false) { $parts = explode(' | ', $titol); $titol = trim($parts[0]); }
            if (strpos($titol, ' [ ') !== false) { $parts = explode(' [ ', $titol); $titol = trim($parts[0]); }
            
            $eq_local = get_post_meta($id, 'equip_local', true); if(empty($eq_local)) $eq_local = 'CH Bordils';
            $competicio = get_post_meta($id, 'competicio', true);
            
            if (empty($competicio)) {
                $termes = wp_get_object_terms($id, 'chb_seccio');
                if (!is_wp_error($termes) && !empty($termes)) {
                    $competicio = ucfirst(strtolower($termes[0]->name));
                }
            }

            $estat_export = get_post_meta($id, 'estat', true);
            if (empty($estat_export)) $estat_export = 'programat';

            // ESCANEIG DE CLAUS: Provem de treure la data des de qualsevol clau possible dels inputs custom
            $data_hora = get_post_meta($id, 'data_hora', true);
            if (empty($data_hora)) $data_hora = get_post_meta($id, 'data_partit', true);
            if (empty($data_hora)) $data_hora = get_post_meta($id, 'data', true);
            
            // Si el metabox utilitza el format de post de WordPress del panell dret superior
            if (empty($data_hora) && isset($partit->post_date) && $partit->post_date != current_time('mysql', 0)) {
                $data_hora = date('d/m/Y H:i', strtotime($partit->post_date));
            }

            fputcsv($output, array($id, $titol, $competicio, $eq_local, get_post_meta($id, 'equip_rival', true), get_post_meta($id, 'localitzacio', true), get_post_meta($id, 'resultat_local', true), get_post_meta($id, 'resultat_visitant', true), $estat_export, $data_hora), ';');
        }
        fclose($output); exit;
    }
}

/* IMPORTADOR MULTI-DATA MAPPED */
add_action('admin_post_chb_importar_excel_segur', 'chb_processar_pujada_segura');
function chb_processar_pujada_segura() {
    if (!isset($_POST['chb_nonce']) || !wp_verify_nonce($_POST['chb_nonce'], 'chb_pujar_csv_nonce')) wp_die('Error de seguretat.');
    if (!current_user_can('edit_posts')) wp_die('Permís denegat.');

    if (!empty($_FILES['chb_fitxer_csv']['tmp_name'])) {
        $contingut = file_get_contents($_FILES['chb_fitxer_csv']['tmp_name']);
        $contingut = str_replace('"', '', $contingut); 
        $contingut = str_replace("\xef\xbb\xbf", "", $contingut); 
        $linies = explode("\n", str_replace("\r", "", $contingut));
        array_shift($linies); 

        foreach ($linies as $linia) {
            if (empty(trim($linia))) continue;
            
            $separador = (strpos($linia, ';') !== false) ? ';' : ',';
            $data = str_getcsv($linia, $separador);

            $id_partit    = (!empty($data[0])) ? intval($data[0]) : 0; 
            $equip_base   = isset($data[1]) ? trim(sanitize_text_field($data[1])) : ''; 
            $competicio   = isset($data[2]) ? trim(sanitize_text_field($data[2])) : ''; 
            $equip_local  = isset($data[3]) ? trim(sanitize_text_field($data[3])) : ''; 
            $rival        = (isset($data[4]) && trim($data[4]) !== '') ? trim(sanitize_text_field($data[4])) : ''; 
            $localitzacio = isset($data[5]) ? trim(sanitize_text_field($data[5])) : ''; 
            $res_local    = isset($data[6]) ? trim(sanitize_text_field($data[6])) : ''; 
            $res_visitant = isset($data[7]) ? trim(sanitize_text_field($data[7])) : ''; 
            $estat_manual = (isset($data[8]) && trim($data[8]) !== '') ? strtolower(trim(sanitize_text_field($data[8]))) : 'programat'; 
            $data_hora    = isset($data[9]) ? trim(sanitize_text_field($data[9])) : '';

            if (empty($equip_base)) continue;

            $nou_titol = $equip_base . (!empty($competicio) ? " [ $competicio ]" : "");

            $args_post = array(
                'post_title'  => $nou_titol,
                'post_name'   => sanitize_title($nou_titol),
                'post_type'   => 'chb_partit',
                'post_status' => 'publish'
            );

            // Si es passa una data de l'Excel, mirem de lligar-la també a la data de publicació nativa del post per si s'alimenta d'aquí
            if (!empty($data_hora)) {
                $date_formatted = str_replace('/', '-', $data_hora);
                $timestamp = strtotime($date_formatted);
                if ($timestamp) {
                    $args_post['post_date'] = date('Y-m-d H:i:s', $timestamp);
                    $args_post['post_date_gmt'] = get_gmt_from_date($args_post['post_date']);
                }
            }

            $id_actualitzada = 0;

            if ($id_partit > 0 && get_post_status($id_partit) !== false) {
                $args_post['ID'] = $id_partit;
                $id_actualitzada = wp_update_post($args_post);
            }
            
            if (empty($id_actualitzada) || is_wp_error($id_actualitzada) || $id_actualitzada === 0) {
                unset($args_post['ID']);
                $id_actualitzada = wp_insert_post($args_post);
            }

            if (is_wp_error($id_actualitzada) || empty($id_actualitzada)) {
                continue;
            }

            if (empty($equip_local)) $equip_local = 'CH Bordils';
            if (empty($localitzacio)) $localitzacio = 'casa';

            $seccio_upper = strtoupper(trim($competicio)); 
            if ($seccio_upper === 'HANDBOL' || $seccio_upper === 'VOLEIBOL') {
                $terme = get_term_by('name', $seccio_upper, 'chb_seccio');
                if (!$terme) {
                    $nou_terme = wp_insert_term($seccio_upper, 'chb_seccio');
                    $terme_id = (!is_wp_error($nou_terme)) ? $nou_terme['term_id'] : 0;
                } else {
                    $terme_id = $terme->term_id;
                }
                if (intval($terme_id) > 0) {
                    wp_set_object_terms($id_actualitzada, array(intval($terme_id)), 'chb_seccio');
                }
            }

            update_post_meta($id_actualitzada, 'competicio', $competicio);
            update_post_meta($id_actualitzada, 'equip_local', $equip_local);
            update_post_meta($id_actualitzada, 'equip_rival', $rival);
            update_post_meta($id_actualitzada, 'localitzacio', $localitzacio);
            update_post_meta($id_actualitzada, 'resultat_local', $res_local);
            update_post_meta($id_actualitzada, 'resultat_visitant', $res_visitant);
            update_post_meta($id_actualitzada, 'estat', $estat_manual);
            
            // Forçat mapejat total per a qualsevol metaclau de data que demani el formulari de la foto a62
            update_post_meta($id_actualitzada, 'data_hora', $data_hora);
            update_post_meta($id_actualitzada, 'data_partit', $data_hora);
            update_post_meta($id_actualitzada, 'data', $data_hora);
        }
        
        wp_redirect(admin_url('edit.php?post_type=chb_partit&chb_import_success=1')); 
        exit;
    }
}

add_action('admin_notices', function() {
    if (isset($_GET['chb_import_success'])) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>¡Mòdul Sincronitzat!</strong> Dades i calendaris processats.</p></div>';
    }
});