<?php
namespace WeDevs\ERP\HRM;

/**
 * The HRM Class
 *
 * This is loaded in `init` action hook
 */
class Human_Resource {

    /**
     * Initializes the WeDevs_ERP() class
     *
     * Checks for an existing WeDevs_ERP() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Kick-in the class
     *
     * @return void
     */
    public function __construct() {

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        // Initialize the action hooks
        $this->init_actions();

        // Initialize the filter hooks
        $this->init_filters();

        do_action( 'erp_hrm_loaded' );
    }

    /**
     * Define the plugin constants
     *
     * @return void
     */
    private function define_constants() {
        define( 'WPERP_HRM_FILE', __FILE__ );
        define( 'WPERP_HRM_PATH', dirname( __FILE__ ) );
        define( 'WPERP_HRM_VIEWS', dirname( __FILE__ ) . '/views' );
        define( 'WPERP_HRM_JS_TMPL', WPERP_HRM_VIEWS . '/js-templates' );
        define( 'WPERP_HRM_ASSETS', plugins_url( '/assets', __FILE__ ) );
    }

    /**
     * Include the required files
     *
     * @return void
     */
    private function includes() {
        require_once WPERP_HRM_PATH . '/admin/class-menu.php';

        require_once WPERP_HRM_PATH . '/includes/functions.php';
        require_once WPERP_HRM_PATH . '/includes/functions-department.php';
        require_once WPERP_HRM_PATH . '/includes/functions-designation.php';
        require_once WPERP_HRM_PATH . '/includes/functions-employee.php';

        require_once WPERP_HRM_PATH . '/includes/class-department.php';
        require_once WPERP_HRM_PATH . '/includes/class-walker-department.php';
        require_once WPERP_HRM_PATH . '/includes/class-designation.php';
        require_once WPERP_HRM_PATH . '/includes/class-ajax.php';
    }

    /**
     * Initialize WordPress action hooks
     *
     * @return void
     */
    private function init_actions() {
        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts' ) );
        add_action( 'admin_footer', array($this, 'admin_js_templates' ) );
    }

    /**
     * Initialize WordPress filter hooks
     *
     * @return void
     */
    private function init_filters() {

    }

    /**
     * Load admin scripts and styles
     *
     * @param  string
     *
     * @return void
     */
    public function admin_scripts( $hook ) {
        // var_dump( $hook );
        $suffix = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';

        wp_enqueue_media( );
        wp_enqueue_script( 'wp-erp-hr', WPERP_HRM_ASSETS . "/js/hrm$suffix.js", array( 'wp-erp-script' ), date( 'Ymd' ), true );
        $localize_script = apply_filters( 'erp_hr_localize_script', array(
            'nonce' => wp_create_nonce( 'wp-erp-hr-nonce' ),
            'popup' => array(
                'dept_title'        => __( 'New Department', 'wp-erp' ),
                'dept_submit'       => __( 'Create Department', 'wp-erp' ),
                'dept_update'       => __( 'Update Department', 'wp-erp' ),
                'desig_title'       => __( 'New Designation', 'wp-erp' ),
                'desig_submit'      => __( 'Create Designation', 'wp-erp' ),
                'desig_update'      => __( 'Update Designation', 'wp-erp' ),
                'employee_title'    => __( 'New Employee', 'wp-erp' ),
                'employee_create'   => __( 'Create Employee', 'wp-erp' ),
                'employee_update'   => __( 'Update Employee', 'wp-erp' ),
                'employment_status' => __( 'Employment Status', 'wp-erp' ),
                'update_status'     => __( 'Update', 'wp-erp' ),
            ),
            'emp_upload_photo'   => __( 'Upload Employee Photo', 'wp-erp' ),
            'emp_set_photo'      => __( 'Set Photo', 'wp-erp' ),
            'delConfirmDept'     => __( 'Are you sure to delete this department?', 'wp-erp' ),
            'delConfirmEmployee' => __( 'Are you sure to delete this employee?', 'wp-erp' )
        ) );

        // if its an employee page
        if ( 'hr-management_page_erp-hr-employee' == $hook ) {
            wp_enqueue_script( 'post' );

            $employee                          = new Employee();
            $localize_script['employee_empty'] = $employee->to_array();
        }

        wp_localize_script( 'wp-erp-hr', 'wpErpHr', $localize_script );
    }

    /**
     * Print JS templates in footer
     *
     * @return void
     */
    public function admin_js_templates() {
        global $current_screen;

        erp_get_js_template( WPERP_HRM_JS_TMPL . '/new-dept.php', 'erp-new-dept' );
        erp_get_js_template( WPERP_HRM_JS_TMPL . '/row-dept.php', 'erp-dept-row' );

        erp_get_js_template( WPERP_HRM_JS_TMPL . '/new-designation.php', 'erp-new-desig' );
        erp_get_js_template( WPERP_HRM_JS_TMPL . '/row-desig.php', 'erp-desig-row' );

        erp_get_js_template( WPERP_HRM_JS_TMPL . '/new-employee.php', 'erp-new-employee' );
        erp_get_js_template( WPERP_HRM_JS_TMPL . '/row-employee.php', 'erp-employee-row' );
        erp_get_js_template( WPERP_HRM_JS_TMPL . '/employment-status.php', 'erp-employment-status' );
        erp_get_js_template( WPERP_HRM_JS_TMPL . '/compensation.php', 'erp-employment-compensation' );
    }
}

Human_Resource::init();