<?php
use Elementor\Widget_Base;

class CHB_Resultats_Widget extends Widget_Base {

    public function get_name(){ return 'chb_resultats'; }
    public function get_title(){ return 'CHB Resultats'; }
    public function get_icon(){ return 'eicon-post-list'; }
    public function get_categories(){ return ['general']; }

    protected function render() {
        $seccions = get_terms([
            'taxonomy' => 'chb_seccio',
            'hide_empty' => true,
        ]);

        echo '<div class="chb-filters-container">';
            echo '<div class="filter-group">
                    <label>Lloc:</label>
                    <select id="filter-loc">
                        <option value="tots">Tots</option>
                        <option value="casa" selected>Casa</option>
                        <option value="fora">Fora</option>
                    </select>
                  </div>';

            echo '<div class="filter-group">
                    <label>Esport:</label>
                    <select id="filter-sec">
                        <option value="tots" selected>Tots els esports</option>';
                        if (!empty($seccions) && !is_wp_error($seccions)) {
                            foreach ($seccions as $seccio) {
                                echo '<option value="' . esc_attr($seccio->slug) . '">' . esc_html($seccio->name) . '</option>';
                            }
                        }
            echo '  </select>
                  </div>';
        echo '</div>';

        echo '<div id="chb-results-container" class="chb-wrapper">';

        $featured = new WP_Query([
            'post_type'      => 'chb_partit',
            'meta_query'     => [['key' => 'destacat', 'value' => 1]],
            'posts_per_page' => -1
        ]);

        $featured_ids = [];
        if($featured->have_posts()){
            echo '<h2 class="chb-featured-title">⭐ PARTITS DESTACATS</h2>';
            while($featured->have_posts()): $featured->the_post();
                $featured_ids[] = get_the_ID();
                $this->render_card(get_post_meta(get_the_ID(), 'estat', true), true);
            endwhile;
            wp_reset_postdata();
        }

        $upcoming = new WP_Query([
            'post_type'      => 'chb_partit',
            'meta_query'     => [['key' => 'estat', 'value' => ['programat','descansa'], 'compare' => 'IN']],
            'meta_key'       => 'data_partit',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            'post__not_in'   => $featured_ids
        ]);

        if($upcoming->have_posts()){
            echo '<h2 class="chb-section-title">🗓 PROPERS PARTITS</h2>';
            while($upcoming->have_posts()): $upcoming->the_post();
                $this->render_card(get_post_meta(get_the_ID(), 'estat', true));
            endwhile;
            wp_reset_postdata();
        }

        $results = new WP_Query([
            'post_type'      => 'chb_partit',
            'meta_query'     => [['key' => 'estat', 'value' => 'jugat']],
            'meta_key'       => 'data_partit',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'posts_per_page' => -1,
            'post__not_in'   => $featured_ids
        ]);

        if($results->have_posts()){
            echo '<h2 class="chb-section-title">🏆 RESULTATS</h2>';
            while($results->have_posts()): $results->the_post();
                $this->render_card('jugat');
            endwhile;
            wp_reset_postdata();
        }

        echo '</div>';
    }

    private function render_card($estat, $featured = false) {
        $id = get_the_ID();
        
        $rival       = get_post_meta($id, 'equip_rival', true);
        $loc         = get_post_meta($id, 'localitzacio', true);
        $data        = get_post_meta($id, 'data_partit', true);
        $local       = get_post_meta($id, 'resultat_local', true);
        $visitant    = get_post_meta($id, 'resultat_visitant', true);
        $stream_url  = get_post_meta($id, 'stream_url', true);
        
        // RECUPEREM ELS NOUS VALORS DINÀMICS DES DE LA BASE DE DADES
        $competicio  = get_post_meta($id, 'competicio', true);
        $equip_local = get_post_meta($id, 'equip_local', true);
        if (empty($equip_local)) { 
            $equip_local = 'CH BORDILS'; 
        }
        
        $terms       = wp_get_post_terms($id, 'chb_seccio');
        $seccio_slug = !empty($terms) ? $terms[0]->slug : '';
        $seccio_nom  = !empty($terms) ? $terms[0]->name : '';
        
        // Netegem el títol base dels post_title de WordPress per l'etiqueta
        $categoria = strtoupper(get_the_title());
        if (strpos($categoria, ' | ') !== false) {
            $parts = explode(' | ', $categoria);
            $categoria = trim($parts[0]);
        }
        
        $class = $featured ? 'chb-card featured' : 'chb-card';
        $loc_label = ($loc == 'casa') ? 'CASA' : (($loc == 'neutral') ? 'NEUTRAL' : 'FORA');
        
        echo '<div class="' . esc_attr($class) . '" data-loc="' . esc_attr($loc) . '" data-sec="' . esc_attr($seccio_slug) . '">';
            
            echo '<div class="chb-badges">';
                echo '<span class="badge badge-cat">' . esc_html($categoria) . '</span>';
                if (!empty($competicio)) {
                    echo '<span class="badge" style="background:#2980b9; color:#fff;">' . esc_html(strtoupper($competicio)) . '</span>';
                }
                if ($seccio_nom) echo '<span class="badge badge-sec">' . esc_html($seccio_nom) . '</span>';
                echo '<span class="badge badge-loc">' . esc_html($loc_label) . '</span>';
            echo '</div>';

            echo '<div class="chb-main-title">';
                if ($estat === 'jugat') {
                    if ($loc === 'fora') {
                        echo esc_html($rival) . ' ' . esc_html($local) . ' - ' . esc_html($visitant) . ' <span class="chb-club">' . esc_html(strtoupper($equip_local)) . '</span>';
                    } else {
                        echo '<span class="chb-club">' . esc_html(strtoupper($equip_local)) . '</span> ' . esc_html($local) . ' - ' . esc_html($visitant) . ' ' . esc_html($rival);
                    }
                } elseif ($estat === 'descansa') {
                    echo '<span class="chb-club">' . esc_html(strtoupper($equip_local)) . '</span> - Descansa';
                } else {
                    if ($loc === 'fora') {
                        echo esc_html($rival) . ' vs <span class="chb-club">' . esc_html(strtoupper($equip_local)) . '</span>';
                    } else {
                        echo '<span class="chb-club">' . esc_html(strtoupper($equip_local)) . '</span> vs ' . esc_html($rival);
                    }
                }
            echo '</div>';

            if (!empty($data) && $estat !== 'descansa') {
                $data_val = date('d/m/Y H:i', strtotime($data));
                echo '<div class="chb-date" style="display:flex; align-items:center; gap:10px; margin-top:10px;">';
                    echo '<span>' . esc_html($data_val) . '</span>';
                    
                    if (!empty($stream_url)) {
                        echo '<a class="chb-stream" href="' . esc_url($stream_url) . '" target="_blank" style="line-height:0; display:inline-block;">';
                            echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="#FF0000" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M23.5 6.2s-.2-1.7-.9-2.4c-.9-.9-1.9-.9-2.4-1C16.8 2.5 12 2.5 12 2.5h0s-4.8 0-8.2.3c-.5.1-1.5.1-2.4 1C.7 4.5.5 6.2.5 6.2S.2 8.1.2 10v2c0 1.9.3 3.8.3 3.8s.2 1.7.9 2.4c.9.9 2.1.9 2.6 1 1.9.2 8 .3 8 .3s4.8 0 8.2-.3c.5-.1 1.5-.1 2.4-1 .7-.7.9-2.4.9-2.4s.3-1.9.3-3.8v-2c0-1.9-.3-3.8-.3-3.8zM9.8 14.6V8.8l6 2.9-6 2.9z"/>
                                  </svg>';
                        echo '</a>';
                    }
                echo '</div>';
            }
        echo '</div>';
    }
}