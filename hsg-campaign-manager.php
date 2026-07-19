<?php
/*
Plugin Name: HSG Campaign Manager
Description: A WordPress plugin for managing WooCommerce sales campaigns.
Version: 1.0.0
Author: Michael Sommerlund
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/Loader.php';
require_once __DIR__ . '/includes/Campaign/CampaignService.php';
require_once __DIR__ . '/includes/Admin/AjaxController.php';

class HSGCampaignManager {
    private $loader;

    public function __construct() {
        $this->loader = new Loader();
        $this->define_admin_hooks();
    }

    private function define_admin_hooks() {
        require_once __DIR__ . '/includes/Admin/Admin.php';
        $plugin_admin = new \HSGCM\Admin\Admin();
        $this->loader->add_action('admin_menu', $plugin_admin, 'register_menu_pages');

        // Initialize CampaignService and AjaxController
        $repository = new \HSGCM\Repository\CampaignRepository();
        $campaign_service = new \HSGCM\Campaign\CampaignService($repository);
        $ajax_controller = new \HSGCM\Admin\AjaxController($campaign_service);

        $this->loader->add_action('wp_ajax_hsgcm_duplicate_campaign', $ajax_controller, 'handle_duplicate_campaign');
    }

    public function run() {
        $this->loader->run();
    }
}

$plugin = new HSGCampaignManager();
$plugin->run();