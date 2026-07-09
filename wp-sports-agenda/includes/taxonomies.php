<?php
add_action('init', function(){
    register_taxonomy('chb_seccio','chb_partit',[
        'label'=>'Seccions',
        'hierarchical'=>true,
        'show_admin_column'=>true
    ]);
});
