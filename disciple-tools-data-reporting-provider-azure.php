<?php
/**
 * Plugin Name: Disciple Tools - Data Reporting Azure Provider
 * Plugin URI: https://github.com/cairocoder01/disciple-tools-data-reporting-provider-azure
 * Description: Disciple Tools - Data Reporting Azure Provider add the Azure provider to the Disciple Tools Data Reporting plugin
 * Version:  0.1.0
 * Author URI: https://github.com/cairocoder01
 * GitHub Plugin URI: https://github.com/cairocoder01/disciple-tools-data-reporting-provider-azure
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.5
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$dt_data_reporting_provider_azure_required_dt_theme_version = '0.28.0';

/**
 * Gets the instance of the `DT_Data_Reporting_Provider_Azure_Plugin` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_data_reporting_provider_azure_plugin() {
    global $dt_data_reporting_provider_azure_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $dt_data_reporting_provider_azure_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'dt_data_reporting_provider_azure_plugin_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }
    /*
     * Don't load the plugin on every rest request. Only those with the 'azure' namespace
     */
    $is_rest = dt_is_rest();
    if ( ! $is_rest ){
        return DT_Data_Reporting_Provider_Azure_Plugin::get_instance();
    }
}
add_action( 'after_setup_theme', 'dt_data_reporting_provider_azure_plugin' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Data_Reporting_Provider_Azure_Plugin {

    /**
     * Declares public variables
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public $img_uri = '';
    public $includes_path;

    /**
     * Returns the instance.
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new dt_data_reporting_provider_azure_plugin();
            $instance->setup();
            $instance->includes();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {
        add_action( "admin_head", array( $this, "add_styles" ) );
    }

    function add_styles() {
        echo '<style>
            body.wp-admin.extensions-dt_page_DT_Data_Reporting
            .pre-div {
                background:#eee;
                border:0 none;
                width: 90%;
                display: block;
                font-family: monospace;
                white-space: pre;
                margin: 1em 0;
            }
          </style>';
    }

    /**
     * Loads files needed by the plugin.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {
        require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
    }

    /**
     * Sets up globals.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup() {

        // Main plugin directory path and URI.
        $this->dir_path     = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->dir_uri      = trailingslashit( plugin_dir_url( __FILE__ ) );

        // Plugin directory paths.
        $this->includes_path      = trailingslashit( $this->dir_path . 'includes' );

        // Plugin directory URIs.
        $this->img_uri      = trailingslashit( $this->dir_uri . 'img' );

        // Admin and settings variables
        $this->token             = 'dt_data_reporting_provider_azure_plugin';
        $this->version             = '0.1';

    }

    /**
     * Sets up main plugin actions and filters.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup_actions() {

        if ( is_admin() ){
            // Check for plugin updates
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
            /**
             * Below is the publicly hosted .json file that carries the version information. This file can be hosted
             * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
             * a template.
             * Also, see the instructions for version updating to understand the steps involved.
             * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
             * @todo enable this section with your own hosted file
             * @todo An example of this file can be found in /includes/admin/disciple-tools-data-reporting-provider-azure-version-control.json
             * @todo It is recommended to host this version control file outside the project itself. Github is a good option for delivering static json.
             */

            /***** @todo remove from here

            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-version-control/master/disciple-tools-data-reporting-provider-azure-version-control.json"; // @todo change this url
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-data-reporting-provider-azure'
            );

            ********* @todo to here */

        }

        // Internationalize the text strings used.
        add_action( 'init', array( $this, 'i18n' ), 2 );

        if ( is_admin() ) {
            // adds links to the plugin description area in the plugin admin list.
            add_filter('plugin_row_meta', [$this, 'plugin_description_links'], 10, 4);

            add_filter('dt_data_reporting_providers', [$this, 'data_reporting_providers'], 10, 4);
            add_action('dt_data_reporting_export_provider_azure', [$this, 'data_reporting_export'], 10, 4);
            add_action('dt_data_reporting_tab_provider_azure', [$this, 'data_reporting_tab'], 10, 1);
        }
    }

    /**
     * Config a new provider to be available in the Data Reporting Plugin
     * @param $providers
     * @return mixed
     */
    public function data_reporting_providers($providers) {
        $providers ['azure'] = [
            'name' => 'Azure',
            'fields' => [
                'azure_storage_account' => [
                    'label' => 'Storage Account',
                    'type' => 'text',
                    'helpText' => 'This is the Azure Storage Account Name (e.g., "discipletools")'
                ],
                'azure_storage_account_container' => [
                    'label' => 'Storage Account Container',
                    'type' => 'text',
                    'helpText' => 'This is the Azure Storage Account Container Name (e.g., "discipletools")'
                ],
                'azure_storage_account_key' => [
                    'label' => 'Storage Account Key',
                    'type' => 'text',
                    'helpText' => 'This is the key to authenticate with this Storage Account (https://docs.microsoft.com/en-us/azure/storage/common/storage-account-keys-manage?tabs=azure-portal#view-account-access-keys)'
                ]
            ]
        ];
        return $providers;
    }

  /**
   * Process the data retrieving by the Data Reporting Plugin and send to custom provider
   * @param array $columns
   * @param array $rows
   * @param string $type
   * @param array $config
   */
    public function data_reporting_export( $columns, $rows, $type, $config ) {
        echo '<li>Sending to provider from hook</li>';
        echo '<li>Items: ' . count($rows) . '</li>';
        echo '<li>Config: ' . print_r($config, true) . '</li>';
        $storage_account_key = $config['azure_storage_account_key'];
        $storage_account = $config['azure_storage_account'];
        $storage_account_container = $config['azure_storage_account_container'];
        $settings_link = 'admin.php?page='.$this->token.'&tab=settings';
        if ( empty( $storage_account_key ) ) {
            echo "<p>A Storage Account Key has not been set. Please update in <a href='$settings_link'>Settings</a></p>";
        } else if ( empty( $storage_account ) ) {
            echo "<p>A Storage Account has not been set. Please update in <a href='$settings_link'>Settings</a></p>";
        } else if ( empty( $storage_account_container ) ) {
            echo "<p>A Storage Account Container has not been set. Please update in <a href='$settings_link'>Settings</a></p>";
        } else {
            $columns = array_map(function ( $column ) { return $column['name']; }, $columns);
            // TODO: do not hardcode maxmemory value
            $csv = fopen('php://temp/maxmemory:'. (100*1024*1024), 'r+');
            fputcsv($csv, $columns);
            // loop over the rows, outputting them
            foreach ($rows as $row ) {
                fputcsv( $csv, $row );
            }
            rewind($csv);
            $content = stream_get_contents($csv);
            // Azure specifics
            $blob_name = "contacts_".strval(gmdate('Ymdhi', time())).".csv";
            $connectionString = "DefaultEndpointsProtocol=https;AccountName=".$storage_account.";AccountKey=".$storage_account_key.";EndpointSuffix=core.windows.net";
            $blobClient = MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($connectionString);
            // TODO: RBAC Support
            //$aadtoken = "";
            //$blobClient = MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobServiceWithTokenCredential($aadtoken, $connectionString);
            try {
              //Upload blob
              $blobClient->createBlockBlob($storage_account_container, $blob_name, $content);
              echo '<div class="notice notice-success notice-dt-data-reporting is-dismissible" data-notice="dt-data-reporting"><p>'.$blob_name.' successfully uploaded to Azure Blob</p></div>';
            } catch(MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e){
              $code = $e->getCode();
              $error_message = $e->getMessage();
              echo $code.": ".$error_message."<br />";
            }
        }
    }

    public function data_reporting_tab( ) {
      ?>
        <script>
          // see: https://www.30secondsofcode.org/blog/s/copy-text-to-clipboard-with-javascript
          function copyText(elementId) {
            const el = document.createElement('textarea');
            el.value = document.getElementById(elementId).value;
            el.setAttribute('readonly', '');
            el.style.position = 'absolute';
            el.style.left = '-9999px';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
          }
        </script>

        <br>
        <table class="widefat">
        <thead>
          <tr><th>Run the following commands in <a href="https://docs.microsoft.com/en-us/azure/cloud-shell/overview">Azure Cloud Shell</a></th></tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <p>This plugin was built with the intention of using Microsoft Azure as its external data store from which to do reporting and analysis. As such, you can find below some info and examples of how you can duplicate that setup.</p>
              <p>Using Azure, these resources should stay within the free usage limits, depending on your usage. You will need to add your credit card to your account, but as long as your usage isn&#39;t overly much, you shouldn&#39;t be billed for anything.</p>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

        <table class="widefat">
        <thead>
          <tr>
            <th>
              1. Create Resource Group <i>(optional, if you choose to reuse an existing Resource Group)</i>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="margin: 20px;">
                <button onclick="copyText('AzResourceGroup');" type="button" class="button" style="float: right;">Copy 📋</button>
                <input type="text"
                  class="pre-div"
                  id="AzResourceGroup"
                  value='az group create --name discipletools --location "US East"'
                />
              </div>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

        <table class="widefat">
        <thead>
          <tr>
            <th>
              2. Create Storage Account
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="margin: 20px;">
                <button onclick="copyText('AzStorageAccount')" type="button" class="button" style="float: right;">Copy 📋</button>
                <input type="text"
                  class="pre-div"
                  id="AzStorageAccount"
                  value='az storage account create --resource-group discipletools --name discipletools --location "US East" --sku Standard_ZRS --encryption-services blob'
                />
              </div>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

        <table class="widefat">
        <thead>
          <tr>
            <th>
              3. Create Blob Container
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="margin: 20px;">
                <button onclick="copyText('AzStorageContainer')" type="button" class="button" style="float: right;">Copy 📋</button>
                <input type="text"
                  class="pre-div"
                  id="AzStorageContainer"
                  value='az storage container create --name discipletools --account-name discipletools'
                />
              </div>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

        <table class="widefat">
        <thead>
          <tr>
            <th>
              4A. Provide Storage Account Key
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="margin-left: 20px;">
            <i>(<a href="https://docs.microsoft.com/en-us/azure/storage/common/storage-account-keys-manage?tabs=azure-portal#view-account-access-keys">https://docs.microsoft.com/en-us/azure/storage/common/storage-account-keys-manage?tabs=azure-portal#view-account-access-keys</a>)</i>
              </div>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

        <table class="widefat">
        <thead>
          <tr>
            <th>
              4B. Create RBAC Role (coming soon, to replace need for providing storage account key)
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="margin-left: 20px;">
            <i>NOTE: (replace &lt;subscription&gt; with your Subscription)</i>
              </div>
              <div style="margin: 20px;">
                <button onclick="copyText('AzRBAC')" type="button" class="button" style="float: right;">Copy 📋</button>
                <input type="text"
                  class="pre-div"
                  id="AzRBAC"
                  value='az ad sp create-for-rbac --name DISCIPLETOOLS --role "Storage Blob Data Contributor" --scopes /subscriptions/&lt;subscription&gt;/resourceGroups/discipletools/providers/Microsoft.Storage/storageAccounts/discipletools'
                />
              </div>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

        <table class="widefat">
        <thead>
          <tr>
            <th>
              References
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="margin: 20px;">
                <ul>
                  <li><a href="https://docs.microsoft.com/en-us/azure/storage/blobs/storage-quickstart-blobs-cli">https://docs.microsoft.com/en-us/azure/storage/blobs/storage-quickstart-blobs-cli</a></li>
                  <li><a href="https://docs.microsoft.com/en-us/azure/role-based-access-control/built-in-roles">https://docs.microsoft.com/en-us/azure/role-based-access-control/built-in-roles</a></li>
                </ul>
              </div>
            </td>
          </tr>
        </tbody>
        </table>
        <br>

      <?php
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.

            // add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when
        // Disciple Tools theme is not installed, otherwise this will already have been installed by the Disciple Tools Theme
        $role = get_role( 'administrator' );
        if ( !empty( $role ) ) {
            $role->add_cap( 'manage_dt' ); // gives access to dt plugin options
        }

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-data-reporting-provider-azure' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_data_reporting_provider_azure_plugin', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_data_reporting_provider_azure_plugin';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "dt_data_reporting_provider_azure_plugin::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Data_Reporting_Provider_Azure_Plugin', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Data_Reporting_Provider_Azure_Plugin', 'deactivation' ] );

function dt_data_reporting_provider_azure_plugin_hook_admin_notice() {
    global $dt_data_reporting_provider_azure_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $current_version = $wp_theme->version;
    $message = __( "'Disciple Tools - Data Reporting Provider Azure' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.", "dt_data_reporting_provider_azure_plugin" );
    if ( $wp_theme->get_template() === "disciple-tools-theme" ){
        $message .= sprintf( esc_html__( 'Current Disciple Tools version: %1$s, required version: %2$s', 'dt_data_reporting_provider_azure_plugin' ), esc_html( $current_version ), esc_html( $dt_data_reporting_provider_azure_required_dt_theme_version ) );
    }
    // Check if it's been dismissed...
    if ( ! get_option( 'dismissed-dt-data-reporting-provider-azure', false ) ) { ?>
        <div class="notice notice-error notice-dt-data-reporting-provider-azure is-dismissible" data-notice="dt-data-reporting-provider-azure">
            <p><?php echo esc_html( $message );?></p>
        </div>
        <script>
            jQuery(function($) {
                $( document ).on( 'click', '.notice-dt-data-reporting-provider-azure .notice-dismiss', function () {
                    $.ajax( ajaxurl, {
                        type: 'POST',
                        data: {
                            action: 'dismissed_notice_handler',
                            type: 'dt-data-reporting-provider-azure',
                            security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                        }
                    })
                });
            });
        </script>
    <?php }
}


/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}
