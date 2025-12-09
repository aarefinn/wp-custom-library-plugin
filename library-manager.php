<?php
/**
 * Plugin Name: Library Manager
 * Description: Custom book management plugin
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('LM_VERSION', '1.0');
define('LM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LM_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Activation hook - create table
register_activation_hook(__FILE__, 'lm_create_table');
function lm_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'library_books';
    
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description longtext,
        author varchar(255),
        publicationyear int(11),
        status enum('available','borrowed','unavailable') DEFAULT 'available',
        createdat datetime DEFAULT CURRENT_TIMESTAMP,
        updatedat datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Admin menu
add_action('admin_menu', 'lm_admin_menu');
function lm_admin_menu() {
    add_menu_page(
        'Library Manager',
        'Library Manager',
        'manage_options',
        'library-manager',
        'lm_admin_page',
        'dashicons-book',
        30
    );
}

// REST API
add_action('rest_api_init', 'lm_register_routes');
function lm_register_routes() {
    register_rest_route('library/v1', '/books', [
        'methods' => 'GET',
        'callback' => 'lm_get_books',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route('library/v1', '/books/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'lm_get_book',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route('library/v1', '/books', [
        'methods' => 'POST',
        'callback' => 'lm_create_book',
        'permission_callback' => 'lm_permission_check'
    ]);
    
    register_rest_route('library/v1', '/books/(?P<id>\d+)', [
        'methods' => ['PUT', 'DELETE'],
        'callback' => 'lm_update_delete_book',
        'permission_callback' => 'lm_permission_check'
    ]);
}

function lm_permission_check($request) {
    return current_user_can('edit_posts');
}

function lm_get_books($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'library_books';
    
    $args = [
        'status' => $request->get_param('status'),
        'author' => $request->get_param('author'),
        'year' => $request->get_param('year')
    ];
    
    $where = [];
    $params = [];
    
    if ($args['status']) {
        $where[] = 'status = %s';
        $params[] = $args['status'];
    }
    if ($args['author']) {
        $where[] = 'author = %s';
        $params[] = $args['author'];
    }
    if ($args['year']) {
        $where[] = 'publicationyear = %d';
        $params[] = intval($args['year']);
    }
    
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $books = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where_sql", $params));
    
    return new WP_REST_Response($books, 200);
}

function lm_get_book($request) {
    global $wpdb;
    $id = intval($request['id']);
    $table_name = $wpdb->prefix . 'library_books';
    
    $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    if (!$book) return new WP_Error('no_book', 'Book not found', ['status' => 404]);
    
    return new WP_REST_Response($book, 200);
}

function lm_create_book($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'library_books';
    
    $data = [
        'title' => sanitize_text_field($request['title']),
        'description' => sanitize_textarea_field($request['description'] ?? ''),
        'author' => sanitize_text_field($request['author'] ?? ''),
        'publicationyear' => intval($request['publicationyear'] ?? 0),
        'status' => in_array($request['status'], ['available', 'borrowed', 'unavailable']) ? $request['status'] : 'available'
    ];
    
    if (empty($data['title'])) {
        return new WP_Error('invalid_title', 'Title is required', ['status' => 400]);
    }
    
    $result = $wpdb->insert($table_name, $data);
    if ($result === false) return new WP_Error('db_error', 'Failed to create book', ['status' => 500]);
    
    $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $wpdb->insert_id));
    return new WP_REST_Response($book, 201);
}

function lm_update_delete_book($request) {
    global $wpdb;
    $id = intval($request['id']);
    $table_name = $wpdb->prefix . 'library_books';
    
    $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    if (!$book) return new WP_Error('no_book', 'Book not found', ['status' => 404]);
    
    if ($request->get_method() === 'DELETE') {
        $wpdb->delete($table_name, ['id' => $id]);
        return new WP_REST_Response(null, 200);
    }
    
    // Update
    $data = [
        'title' => sanitize_text_field($request['title'] ?? $book->title),
        'description' => sanitize_textarea_field($request['description'] ?? $book->description),
        'author' => sanitize_text_field($request['author'] ?? $book->author),
        'publicationyear' => intval($request['publicationyear'] ?? $book->publicationyear),
        'status' => in_array($request['status'], ['available', 'borrowed', 'unavailable']) ? $request['status'] : $book->status
    ];
    
    $wpdb->update($table_name, $data, ['id' => $id]);
    $updated_book = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    return new WP_REST_Response($updated_book, 200);
}



// Admin page
function lm_admin_page() {
    ?>
    <div class="wrap">
        <h1>Library Manager Dashboard</h1>
        <div id="lm-root"></div>
    </div>
    <?php
    lm_enqueue_admin_scripts();
}

// Enqueue React app
function lm_enqueue_admin_scripts() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'library-manager') return;
    
    wp_enqueue_script(
        'lm-react-app',
        LM_PLUGIN_URL . 'assets/js/app.js',
        ['wp-element', 'wp-api-fetch'],
        LM_VERSION,
        true
    );
    
    wp_localize_script('lm-react-app', 'lmData', [
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'nonceName' => 'X-WP-Nonce'
    ]);
}
