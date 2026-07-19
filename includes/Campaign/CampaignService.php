<?php
namespace HSGCM\Campaign;

use HSGCM\Repository\CampaignRepository;

class CampaignService {
    private $repository;

    public function __construct(CampaignRepository $repository) {
        $this->repository = $repository;
    }

    // Existing methods...

    public function duplicate($campaignId) {
        $campaign = $this->repository->getById($campaignId);

        if (!$campaign) {
            return false;
        }

        $newCampaignData = [
            'name' => 'Copy of ' . \apply_filters('esc_html', $campaign->name),
            'status' => 'draft',
            'priority' => $campaign->priority,
            'products' => $campaign->products,
            'type' => $campaign->type,
            'value' => $campaign->value,
            'coupon' => $campaign->coupon,
            'stackable' => $campaign->stackable,
            'start_date' => '',
            'end_date' => ''
        ];

        return $this->repository->create($newCampaignData);
    }
}