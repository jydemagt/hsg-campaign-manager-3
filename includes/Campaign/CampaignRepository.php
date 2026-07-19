<?php
namespace HSGCM\Repository;

class CampaignRepository {
    // Define methods for repository operations here

    public function getById(int $id): ?array {
        global $wpdb; // Access the global WordPress database object if needed

        $post = get_post($id); // Call the global function directly

        if (!$post || $post->post_type !== 'campaign') {
            return null;
        }

        $data = get_post_meta($id, '_hsgcm_campaign', true); // Call the global function directly

        if (!is_array($data)) {
            $data = [];
        }

        $data = array_merge(
            [
                'id'         => $id,
                'name'       => $post->post_title,
                'status'     => $post->post_status,
                'start_date' => '',
                'end_date'   => '',
                'priority'   => 0,
                'quantity'   => 2,
                'bundle_price' => '',
                'products'   => [],
                'type'       => 'fixed_price',
                'value'      => '',
                'coupon'     => '',
                'stackable'  => false,
            ],
            $data
        );

        $data['id']       = $id;
        $data['name']     = $post->post_title;
        $data['status']   = $post->post_status;
        $data['products'] = is_array($data['products']) ? $data['products'] : [];

        return $data;
    }

    // Existing methods...
}