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
	 * Resolve applicable campaigns.
	 *
	 * Lower priority numbers win. A non-stackable winning campaign is applied
	 * alone; stackable campaigns can be combined with other stackable campaigns.
	 *
	 * @param array $campaigns Campaigns.
	 *
	 * @return array
	 */
	public function resolve( array $campaigns ): array {

		if ( empty( $campaigns ) ) {
			return array();
		}

		usort(
			$campaigns,
			static function ( object $a, object $b ): int {

				$priority = $a->priority <=> $b->priority;

				if ( 0 !== $priority ) {
					return $priority;
				}

				return $a->id <=> $b->id;

			}
		);

		$winner = reset( $campaigns );

		if ( ! $winner->stackable ) {
			return array( $winner );
		}

		return array_values(
			array_filter(
				$campaigns,
				static function ( object $campaign ): bool {
					return $campaign->stackable;
				}
			)
		);

	}

}
