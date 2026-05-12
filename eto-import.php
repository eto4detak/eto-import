<?php

/**
 * Plugin Name: Демо данные вордпрес
 * Description: Eto demo
 * Author:       eto | anatolif
 *
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version:     1.0
 */




if (!defined('ABSPATH')) {
    exit;
}

class Eto_Demo_Data{

    public function __construct()
    {
        // admin
        add_action('admin_menu', array($this, 'submenu_page_url'), 999);
        add_action('admin_init', array($this, 'settings_page'));
    }

        
    //****************admin*********************
    //страница настроек 

    function submenu_page_url() {
        add_submenu_page( 'options-general.php', 'Eto Demo Data', 'Eto Demo Data', 'manage_options', 'compare-cars', array($this, 'submenu_page_print') );
    }

    function submenu_page_print(){

        ?>
        <div class="wrap">
            <h3><?php echo get_admin_page_title() ?></h3>

            <form action="options.php" method="POST">
                <?php
                    settings_fields( 'eto_demo_opt_gr' ); 
                    do_settings_sections( 'eto_demo_page1' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    function settings_page(){

        register_setting( 'eto_demo_opt_gr', 'eto_demo_templ', array($this, 'sanitize_clb') );
        add_settings_section( 'eto_demo_section1',  __('Основные настройки', 'eto'), '', 'eto_demo_page1' ); 
        add_settings_field('primer_field1', __('Field of first', 'eto'), array($this, 'field_first_display'), 'eto_demo_page1', 'eto_demo_section1' );
        add_settings_field('compare_field_favorite',  __('Field of hide', 'eto'), array($this, 'field_hide_display'), 'eto_demo_page1', 'eto_demo_section1' );
    }

    function field_first_display(){

        $val = get_option('eto_demo_templ'); 
        $template = !empty($val['template']) ? $val['template'] : -1;
        ?>

        <select name="eto_demo_templ[template]"> 
        <option value="-1">
        <?php echo esc_attr( __( 'Select page' ) ); ?></option> 
        <?php 
        $pages = get_pages(); 
        foreach ( $pages as $page ) {
                $option = '<option value="' .  $page->ID . '" ' . selected( $template, $page->ID ) . '>';
                $option .= $page->post_title;
                $option .= '</option>';
                echo $option;
        }
        ?>
        </select>

        <?php
    }
    function field_hide_display(){

        $val = get_option('eto_demo_templ'); 
        $no_favorite = !empty($val['no_favorite']) ? $val['no_favorite'] : false;
        ?>
        <label><input type="checkbox" name="eto_demo_templ[no_favorite]" value="1" <?php checked( 1, $no_favorite ) ?> /></label>
        <?php
    }

    function sanitize_clb( $options ){ 

        return $options;
    }
    //****************end admin*********************


}


new Eto_Demo_Data();



