<?php
add_action('add_meta_boxes', function(){
    add_meta_box('chb_dades','Dades del Partit','chb_render','chb_partit','normal','high');
});

function chb_render($post){
    $fields=['equip_rival','localitzacio','data_partit','estat','resultat_local','resultat_visitant','stream_url','destacat','competicio','equip_local'];
    foreach($fields as $f){ $$f=get_post_meta($post->ID,$f,true); }
    if (empty($equip_local)) $equip_local = 'CH Bordils'; // Valor per defecte natiu
?>
<p><label>Equip Rival</label><br>
<input type="text" name="equip_rival" style="width:100%;" value="<?php echo esc_attr($equip_rival); ?>"></p>

<p><label><input type="checkbox" name="destacat" value="1" <?php checked($destacat,1); ?>> Destacar partit</label></p>

<p><label>Localització</label><br>
<select name="localitzacio">
<option value="casa" <?php selected($localitzacio,'casa'); ?>>Casa</option>
<option value="fora" <?php selected($localitzacio,'fora'); ?>>Fora</option>
<option value="neutral" <?php selected($localitzacio,'neutral'); ?>>Neutral (Torneig / Top 4)</option>
</select></p>

<p><label>Data i Hora</label><br>
<input type="datetime-local" name="data_partit" value="<?php echo esc_attr($data_partit); ?>"></p>

<p><label>Estat</label><br>
<select name="estat">
<option value="no_programat" <?php selected($estat,'no_programat'); ?>>No programat</option>
<option value="programat" <?php selected($estat,'programat'); ?>>Programat</option>
<option value="jugat" <?php selected($estat,'jugat'); ?>>Jugat</option>
<option value="descansa" <?php selected($estat,'descansa'); ?>>Descansa</option>
</select></p>

<p><label>Resultat Local</label><br>
<input type="number" name="resultat_local" value="<?php echo esc_attr($resultat_local); ?>"></p>

<p><label>Resultat Visitant</label><br>
<input type="number" name="resultat_visitant" value="<?php echo esc_attr($resultat_visitant); ?>"></p>

<p><label>URL Streaming</label><br>
<input type="url" name="stream_url" style="width:100%;" value="<?php echo esc_attr($stream_url); ?>"></p>

<!-- SECCIÓ DE TORNEIGS INTEGRADA INTEGRALMENT AL FORMULARI CLÀSSIC -->
<hr style="margin:20px 0; border:0; border-top:1px solid #eee;">
<h4 style="color:#0a7d3b; margin-bottom:5px;">Configuració de Torneig / Partits Neutrals</h4>
<div style="display:flex; gap:20px;">
    <p style="flex:1;"><label><strong>Tipus de Competició / Torneig:</strong> (Ex: Top 4, Torneig Reis)</label><br>
    <input type="text" name="competicio" value="<?php echo esc_attr($competicio); ?>" style="width:100%; margin-top:5px;" placeholder="Deixar buit per a Lliga Regular"></p>
    
    <p style="flex:1;"><label><strong>Equip Local del Partit:</strong> (Canviar si és un partit neutral)</label><br>
    <input type="text" name="equip_local" value="<?php echo esc_attr($equip_local); ?>" style="width:100%; margin-top:5px; font-weight:bold; color:#0a7d3b;"></p>
</div>

<?php }

add_action('save_post', function($id){
    // Sincronitzat amb els nous camps per al guardat segur
    $fields=['equip_rival','localitzacio','data_partit','estat','resultat_local','resultat_visitant','stream_url','competicio','equip_local'];
    foreach($fields as $f){
        if(isset($_POST[$f])) update_post_meta($id,$f,sanitize_text_field($_POST[$f]));
    }
    update_post_meta($id,'destacat', isset($_POST['destacat']) ? 1 : 0);
});