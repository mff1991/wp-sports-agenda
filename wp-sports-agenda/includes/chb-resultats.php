<?php
/**
 * Plugin Name: CHB Resultats
 * Version: 2.4.0 (Plantilla Exacta)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define('CHB_PATH', plugin_dir_path(__FILE__));
define('CHB_URL', plugin_dir_url(__FILE__));

// Carreguem la lògica dels fitxers interns natius
require_once CHB_PATH . 'includes/cpt.php';
require_once CHB_PATH . 'includes/taxonomies.php';
require_once CHB_PATH . 'includes/meta-boxes.php';

// Registre del Widget per a Elementor
add_action('elementor/init', function() {
    if ( ! class_exists('\Elementor\Widget_Base') ) return;
    require_once CHB_PATH . 'includes/class-widget.php';
    add_action('elementor/widgets/register', function($widgets_manager){
        $widgets_manager->register( new \CHB_Resultats_Widget() );
    });
});

// Carreguem els estils CSS
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('chb-style', CHB_URL . 'assets/css/style.css');
});


/* ==========================================================================
   1. COLUMNES DE L'ESCRIPTORI DE L'ADMINISTRADOR
   ========================================================================== */
add_filter('manage_chb_partit_posts_columns', 'chb_afegir_columnes_taula');
function chb_afegir_columnes_taula($columns) {
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

add_action('manage_chb_partit_posts_custom_column', 'chb_omplir_columnes_taula', 10, 2);
function chb_omplir_columnes_taula($column, $post_id) {
    switch ($column) {
        case 'competicio':
            $competicio = get_post_meta($post_id, 'competicio', true);
            echo !empty($competicio) ? '<span style="color:#0a7d3b; font-weight:600;">' . esc_html($competicio) . '</span>' : '<span style="color:#aaa; font-style:italic;">Lliga Regular</span>';
            break;
        case 'rival':
            $local = get_post_meta($post_id, 'equip_local', true);
            $visitant = get_post_meta($post_id, 'equip_rival', true);
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
            $local_res = get_post_meta($post_id, 'resultat_local', true);
            $visitant_res = get_post_meta($post_id, 'resultat_visitant', true);
            echo ($local_res !== '' && $visitant_res !== '') ? '<strong>' . esc_html($local_res) . ' - ' . esc_html($visitant_res) . '</strong>' : '<span style="color:#999; font-style:italic;">Pendent</span>';
            break;
    }
}


/* ==========================================================================
   2. PÀGINA D'OPCIONS INDEPENDENT
   ========================================================================== */
add_action('admin_menu', 'chb_crear_menu_importador_excel');
function chb_crear_menu_importador_excel() {
    add_submenu_page(
        'edit.php?post_type=chb_partit',
        'Actualització en Massa',
        '⚡ Actualitzar Massa (Excel)',
        'edit_posts',
        'chb_importador_excel',
        'chb_render_pagina_importador'
    );
}

function chb_render_pagina_importador() {
    $url_plantilla = admin_url('edit.php?post_type=chb_partit&chb_descarregar_plantilla_perfecta=1');
    ?>
    <div class="wrap">
        <h1 style="color:#0a7d3b; font-weight:700;">Mòdul Automatitzat de Temporades (Excel)</h1>
        <p>Aquest panell et permet descarregar la plantilla oficial, modificar partits existents o crear tots els equips de la nova temporada d'una sola vegada.</p>
        
        <?php if (isset($_GET['chb_success'])) : ?>
            <div class="notice notice-success is-dismissible" style="margin:20px 0;"><p><strong>¡Sincronització Completada!</strong> El full s'ha processat recte i els equips/partits s'han actualitzat correctament.</p></div>
        <?php endif; ?>

        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:8px; max-width:800px; margin-top:20px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <h2>Pas 1: Descarrega la teva plantilla clàssica</h2>
            <p>Obté el full de 9 columnes exactes llest per a Microsoft Excel.</p>
            <a href="<?php echo esc_url($url_plantilla); ?>" class="button button-secondary" style="border-color:#0a7d3b; color:#0a7d3b; padding:5px 15px; height:auto;">📥 Descarregar Plantilla de Treball</a>
            
            <hr style="margin:25px 0; border:0; border-top:1px solid #eee;">
            
            <h2>Pas 2: Puja el teu full completat</h2>
            <p>Selecciona el document desat des d'Excel com a format <strong>CSV (delimitado por comas)</strong>.</p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="chb_processar_excel_directe">
                <?php wp_nonce_field('chb_pujar_directe_nonce', 'chb_nonce'); ?>
                <input type="file" name="chb_fitxer_excel" accept=".csv" required style="margin-bottom:15px; display:block;"><br>
                <input type="submit" class="button button-primary" style="background:#0a7d3b; border-color:#0a7d3b; font-size:14px; padding:6px 20px; height:auto;" value="🚀 Executar Actualització Massiva">
            </form>
        </div>
    </div>
    <?php
}


/* ==========================================================================
   3. DES CÀRREGA I LECTURA ADAPTADA A LA TEVA IMATGE DE 9 COLUMNES
   ========================================================================== */

add_action('admin_init', 'chb_processar_descarga_plantilla_perfecta');
function chb_processar_descarga_plantilla_perfecta() {
    if (isset($_GET['chb_descarregar_plantilla_perfecta'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=plantilla_partits_bordils.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 per a Excel
        
        // Capçalera de 9 columnes clàssica idèntica a la teva imatge
        fputcsv($output, array('ID Partit', 'Categoria Base', 'Torneig o Competicio', 'Equip Local', 'Equip Visitant', 'Localitzacio', 'Resultat Local', 'Resultat Visitant', 'Estat'), ';');

        $partits = get_posts(array('post_type'=>'chb_partit', 'numberposts'=>-1, 'post_status'=>'publish'));
        foreach ($partits as $p) {
            $titol = $p->post_title;
            if (strpos($titol, ' | ') !== false) { $parts = explode(' | ', $titol); $titol = trim($parts[0]); }
            
            $eq_local = get_post_meta($p->ID, 'equip_local', true);
            if(empty($eq_local)) $eq_local = 'CH Bordils';

            fputcsv($output, array(
                $p->ID, $titol,
                get_post_meta($p->ID, 'competicio', true),
                $eq_local,
                get_post_meta($p->ID, 'equip_rival', true),
                get_post_meta($p->ID, 'localitzacio', true),
                get_post_meta($p->ID, 'resultat_local', true),
                get_post_meta($p->ID, 'resultat_visitant', true),
                get_post_meta($p->ID, 'estat', true)
            ), ';');
        }
        fclose($output); exit;
    }
}

add_action('admin_post_chb_processar_excel_directe', 'chb_processar_excel_directe');
function chb_processar_excel_directe() {
    if (!isset($_POST['chb_nonce']) || !wp_verify_nonce($_POST['chb_nonce'], 'chb_pujar_directe_nonce')) wp_die('Error.');

    if (!empty($_FILES['chb_fitxer_excel']['tmp_name'])) {
        $contingut = file_get_contents($_FILES['chb_fitxer_excel']['tmp_name']);
        $contingut = str_replace("\xef\xbb\xbf", "", $contingut); 
        $linies = explode("\n", str_replace("\r", "", $contingut));
        array_shift($linies); 

        foreach ($linies as $linia) {
            if (empty(trim($linia))) continue;

            $separador = (strpos($linia, ';') !== false) ? ';' : ',';
            $data = str_getcsv($linia, $separador);

            $id           = (!empty($data[0])) ? intval($data[0]) : 0; 
            $equip_base   = sanitize_text_field($data[1]);             // Columna B: Títol Equip
            $competicio   = sanitize_text_field($data[2]);             // Columna C: Torneig o Secció (HANDBOL / VOLEIBOL)
            $equip_local  = sanitize_text_field($data[3]);             // Columna D: Local
            $rival        = sanitize_text_field($data[4]);             // Columna E: Visitant
            $localitzacio = sanitize_text_field($data[5]);             // Columna F: Lloc
            $res_local    = sanitize_text_field($data[6]);             // Columna G: Gols Local
            $res_visitant = sanitize_text_field($data[7]);             // Columna H: Gols Visitant
            $estat_manual = strtolower(sanitize_text_field($data[8])); // Columna I: Estat

            if (empty($equip_base)) continue;

            // Creació automàtica si l'ID és zero
            if ($id === 0) {
                $id = wp_insert_post(array(
                    'post_title'  => $equip_base,
                    'post_type'   => 'chb_partit',
                    'post_status' => 'publish'
                ));
            } else {
                wp_update_post(array('ID' => $id, 'post_title' => $equip_base));
            }

            if (empty($equip_local)) $equip_local = 'CH Bordils';
            if (empty($localitzacio)) $localitzacio = 'casa';

            $estat_final = 'no_programat';
            if ($estat_manual === 'descansa' || (empty($rival) && $estat_manual === 'descansa')) {
                $estat_final = 'descansa';
            } elseif (!empty($rival) && ($res_local !== '' && $res_visitant !== '')) {
                $estat_final = 'jugat';
            } elseif (!empty($rival) && ($res_local === '' && $res_visitant === '')) {
                $estat_final = 'programat';
            }

            // LÒGICA PROTEGIDA PER A LA SECCIÓ:
            $seccio_upper = strtoupper(trim($competicio));
            if ($seccio_upper === 'HANDBOL' || $seccio_upper === 'VOLEIBOL') {
                // Si poses l'esport a la columna C, l'assignem com a secció i netegem el camp competició
                $terme = get_term_by('name', $seccio_upper, 'chb_seccio');
                if (!$terme) {
                    $nou_terme = wp_insert_term($seccio_upper, 'chb_seccio');
                    $terme_id = (!is_wp_error($nou_terme)) ? $nou_terme['term_id'] : 0;
                } else {
                    $terme_id = $terme->term_id;
                }
                if ($terme_id > 0) {
                    wp_set_object_terms($id, array(intval($terme_id)), 'chb_seccio');
                }
                $competicio = ''; // Fiquem la competició en blanc perquè no surti escrit "[ HANDBOL ]" al títol
            }

            // Desem les dades a la DB
            update_post_meta($id, 'competicio', $competicio);
            update_post_meta($id, 'equip_local', $equip_local);
            update_post_meta($id, 'equip_rival', $rival);
            update_post_meta($id, 'localitzacio', $localitzacio);
            update_post_meta($id, 'resultat_local', $res_local);
            update_post_meta($id, 'resultat_visitant', $res_visitant);
            update_post_meta($id, 'estat', $estat_final);

            // Generem el títol automatitzat de WordPress
            $bloc_partit = "";
            if (!empty($rival)) {
                $bloc_partit = (strtolower($localitzacio) == 'fora') ? "$rival vs $equip_local" : "$equip_local vs $rival";
            }
            $prefix = (!empty($competicio)) ? " [ $competicio ]" : "";
            $nou_titol = !empty($bloc_partit) ? "$equip_base$prefix | $bloc_partit" : $equip_base;

            wp_update_post(array('ID' => $id, 'post_title' => $nou_titol, 'post_name' => sanitize_title($nou_titol)));
        }
        
        wp_redirect(admin_url('edit.php?post_type=chb_partit&page=chb_importador_excel&chb_success=1'));
        exit;
    }
}