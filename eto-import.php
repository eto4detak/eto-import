<?php

/**
 * Plugin Name: Демо данные вордпрес
 * Description: Eto demo
 * Author:       eto | Anatoli Fokin
 *
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version:     1.0
 */




if (!defined('ABSPATH')) {
    exit;
}

class Eto_Demo_Data{

    const POST_COUNT = 20;
    const IMG_COUNT = 10;
    const POST_TYPE_COUNT = 10;
    const CONTENT_COUNT = 100;
    const CATEGORY_TERM = ['apple', 'top', 'boss'];
    const POST_TAG_TERM = ['auto', 'food', 'news'];
    
    

    public function __construct()
    {
        // admin
        add_action('admin_menu', array($this, 'submenu_page_url'), 999);
        add_action('admin_init', array($this, 'settings_page'));
    }

        
    //****************admin*********************
    //страница настроек 

    function submenu_page_url() {
        add_submenu_page( 'options-general.php', 'Eto Demo Data', 'Eto Demo Data', 'manage_options', 'eto_demo_data1', array($this, 'submenu_page_print') );
    }

    function submenu_page_print(){

        ?>
        <div class="wrap">
            <h3><?php echo get_admin_page_title() ?></h3>
    
            <form action="options.php" method="POST">
                <?php
                    settings_fields( 'eto_demo_opt_gr' ); 
                    do_settings_sections( 'eto_demo_page1' );
                    submit_button(__('Start import', 'eto'));
                ?>
            </form>
        </div>
        <?php
    }

    function settings_page(){

        register_setting( 'eto_demo_opt_gr', 'eto_demo_templ', array($this, 'sanitize_clb') );
        add_settings_section( 'eto_demo_section1',  __('Основные настройки', 'eto'), '', 'eto_demo_page1' ); 
        add_settings_field('eto_demo_first_field', __('Field of first', 'eto'), array($this, 'field_first_display'), 'eto_demo_page1', 'eto_demo_section1' );
        add_settings_field('eto_demo_demo_start_field', __('Field of Demo start', 'eto'), array($this, 'field_demo_start_display'), 'eto_demo_page1', 'eto_demo_section1' );
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

    function field_demo_start_display(){

        $val = get_option('eto_demo_templ');
        update_option('eto_demo_templ', false, 'no');
        $demo_start = !empty($val['demo_start']) ? $val['demo_start'] : false;
        if($demo_start){
            
            self::import_demo_data();
        }
        

        ?>
        <label><input type="checkbox" name="eto_demo_templ[demo_start]" value="1" <?php checked( 1, false ) ?> /></label>
        <?php
    }

    function sanitize_clb( $options ){ 

        return $options;
    }
    //****************end admin*********************


    function import_demo_data(){
        $count_posts = wp_count_posts();
        $page_count = wp_count_posts('page');
        $media_ids = [];
        $post_IDs = [];
        $page_IDs = [];
        //$published_posts = wp_count_posts('new_post_type')->publish;

        $categoris = self::set_category_taxonomy();
        $tag = self::set_post_tag_taxonomy();

        for ($i=0; $i < self::POST_COUNT; $i++) { 
            $post_title = 'Post ' . ($count_posts->publish + 1 + $i);
            $page_title = 'Page ' . ($page_count->publish + 1 + $i);
            $content = '';
            $page_content = '';
            for ($c=0; $c < self::CONTENT_COUNT; $c++) { 
                $content .= 'Content ' . $post_title . ' ';
                $page_content .= 'Content ' . $page_title . ' ';
            }

            $post_data = array(
                'post_title'    => sanitize_text_field( $post_title ),
                'post_content'  => $content,
                'post_status'   => 'publish',
                'post_author'   => 1,
            );
            $post_id = wp_insert_post( $post_data );
            $post_IDs[] = $post_id;
            
            $page_data = array(
                'post_title'    => sanitize_text_field( $page_title ),
                'post_content'  => $page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
            );
            $page_id = wp_insert_post( $page_data );
            $page_IDs[] = $page_id;

            $index = $i % count(self::POST_TAG_TERM);
            $cat_index = $i % count(self::CATEGORY_TERM);
            $term_id = term_exists(self::CATEGORY_TERM[$cat_index]);
            wp_set_post_terms( $post_id, self::POST_TAG_TERM[$index], 'post_tag', true );
            wp_set_post_categories( $post_id, $term_id );

            $file = plugin_dir_url(__FILE__) . 'images/'. ($i % 10) .'.jpg';
            if(count($media_ids) < 10){
                $media_id = self::set_image($post_id, $file);
                $media_ids[] = $media_id;
                set_post_thumbnail( $page_id, $media_ids[$i % 10] );
            }else{
                set_post_thumbnail( $post_id, $media_ids[$i % 10] );
                set_post_thumbnail( $page_id, $media_ids[$i % 10] );
            }
            
            
        }
        self::set_blog_page($page_IDs[count($page_IDs) -1], $page_IDs[count($page_IDs) -2]);
        self::set_menu($post_IDs[count($post_IDs)-1], $page_IDs[count($page_IDs)-1], $page_IDs[count($page_IDs)-2]);
        self::set_comments($post_IDs);
        self::set_post_type($media_ids);
    }

    function set_blog_page($for_post, $for_page){
        
        update_option('show_on_front', 'page');
        update_option('page_for_posts', $for_post);
        update_option('page_on_front', $for_page);

        $my_post = array(
            'ID'         => $for_post,
            'post_title' => __('Blog', 'eto'),
        );
        wp_update_post( $my_post );

        $my_post = array(
            'ID'         => $for_page,
            'post_title' => __('Home', 'eto'),
        );
        wp_update_post( $my_post );
        
    }

    function set_post_type($media_ids = []){

        $args = array(
            'public'   => true,
            '_builtin' => false
        );
        $output   = 'names'; // names or objects, note names is the default
        $operator = 'and';   // 'and' or 'or'
        $post_types = get_post_types( $args, $output, $operator );
        foreach ( $post_types as $post_type ) {

            for ($i=0; $i < self::POST_TYPE_COUNT; $i++) {

                $post_count = wp_count_posts($post_type);
                $post_title = $post_type . ' ' . ($post_count->publish + 1 + $i);
                $post_content = '';
                for ($c=0; $c < self::CONTENT_COUNT; $c++) { 
                    $post_content .= 'Content ' . $post_title . ' ';
                }

                $page_data = array(
                    'post_title'    => sanitize_text_field( $post_title ),
                    'post_content'  => $post_content,
                    'post_status'   => 'publish',
                    'post_type'     => $post_type,
                    'post_author'   => 1,
                );
                $page_id = wp_insert_post( $page_data );
                set_post_thumbnail( $page_id, $media_ids[$i % self::IMG_COUNT] );
            }

        }

    }

    function set_comments($post_IDs = []){
        
        foreach ($post_IDs as $key => $id) {
            $commentdata = [
                'comment_post_ID'      => $id,
                'comment_author'       => 'admin',
                'comment_author_email' => 'admin@admin.com',
                'comment_author_url'   => 'http://example.com',
                'comment_content'      => 'Текст нового комментария для поста ' . $id,
                'comment_type'         => 'comment',
                //'comment_parent'       => 315,
                'user_ID'              => 1,
            ];

            wp_new_comment( $commentdata );
        } 

    }

    function set_menu($page_id = 0, $post_id = 0, $blog_id = 0){

        $name = 'primary-menu';
        $menu_exists = wp_get_nav_menu_object($name);
        if( !$menu_exists){
            $menu_id = wp_create_nav_menu($name);
            $menu = get_term_by( 'name', $name, 'nav_menu' );

            wp_update_nav_menu_item($menu->term_id, 0, array(
                'menu-item-title' =>  __('Home'),
                'menu-item-classes' => 'topics-dropdown',
                'menu-item-url' => get_home_url(),
                'menu-item-type' => 'custom',
                'menu-item-status' => 'publish'));
        }
        $menu = get_term_by( 'name', $name, 'nav_menu' );
        $the_post = get_post( $post_id );
        wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-title' =>  $the_post->post_title,
            'menu-item-classes' => 'topics-dropdown',
            'menu-item-url' => get_permalink($post_id),
            'menu-item-type' => 'custom',
            'menu-item-status' => 'publish'));
            $the_post = get_post( $post_id );

        $the_post = get_post( $page_id );
        wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-title' =>  $the_post->post_title,
            'menu-item-classes' => 'topics-dropdown',
            'menu-item-url' => get_permalink($page_id),
            'menu-item-type' => 'custom',
            'menu-item-status' => 'publish'));

        $the_post = get_post( $blog_id );
        wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-title' =>  $the_post->post_title,
            'menu-item-classes' => 'topics-dropdown',
            'menu-item-url' => get_permalink($blog_id),
            'menu-item-type' => 'custom',
            'menu-item-status' => 'publish'));

    }

    function set_category_taxonomy(){

        $terms = [];
        foreach (self::CATEGORY_TERM as $key => $term) {
           $term = wp_insert_term( $term, 'category' );
           $terms[] = $term;
        }
        
        return $terms;
    }
    function set_post_tag_taxonomy(){

        $terms = [];
        foreach (self::POST_TAG_TERM as $key => $term) {
           $term = wp_insert_term( $term, 'post_tag' );
           $terms[] = $term;
        }
        
        return $terms;
    }

    function set_image($post_id, $file){

        $desc = 'Описание картинки';

        global $debug; // определяется за пределами функции как true

        if( ! function_exists('media_handle_sideload') ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        // Загружаем файл во временную директорию
        $tmp = download_url( $file );

        // Устанавливаем переменные для размещения
        $file_array = [
            'name'     => basename( $file ),
            'tmp_name' => $tmp
        ];

        // Удаляем временный файл, при ошибке
        if ( is_wp_error( $tmp ) ) {
            $file_array['tmp_name'] = '';
            if( $debug ) echo 'Ошибка нет временного файла! <br />';
        }

        // проверки при дебаге
        if( $debug ){
            echo 'File array: <br />';
            var_dump( $file_array );
            echo '<br /> Post id: ' . $post_id . '<br />';
        }

        $id = media_handle_sideload( $file_array, $post_id, $desc );

        // Проверяем работу функции
        if ( is_wp_error( $id ) ) {
            var_dump( $id->get_error_messages() );
        } else {
            update_post_meta( $post_id, '_thumbnail_id', $id );
        }

        // удалим временный файл
        @unlink( $tmp );

        return $id;
    }
}


new Eto_Demo_Data();



