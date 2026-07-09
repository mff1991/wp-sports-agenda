<?php
add_action('init', function(){
    register_post_type('chb_partit',[
        'label'=>'Partits',
        'public'=>true,
        'show_ui'=>true,
        'supports'=>['title'],
        'menu_icon'=>'dashicons-awards',
        'taxonomies'=>['chb_seccio']
    ]);
});
