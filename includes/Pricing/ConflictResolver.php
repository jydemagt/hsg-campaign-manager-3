<?php
/**
 * Conflict Resolver
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class ConflictResolver {

	/**
	 * Select the winning campaign.
	 *
	 * Higher priority values win. Ties fall back to the highest campaign ID.
	 *
	 * @param array $campaigns Campaigns.
	 *
	 * @return array|null
	 */
	public function resolve( array $campaigns ): ?array {

		if ( empty( $campaigns ) ) {
			return null;
		}

		usort(
			$campaigns,
			static function ( array $left, array $right ): int {

				$priority = (int) ( $right['priority'] ?? 0 ) <=> (int) ( $left['priority'] ?? 0 );

				if ( 0 !== $priority ) {
					return $priority;
				}

				return (int) ( $right['id'] ?? 0 ) <=> (int) ( $left['id'] ?? 0 );

			}
		);

		return $campaigns[0];

	}

}
