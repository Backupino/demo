<?php
/**
 * Role and Capability Manager.
 *
 * Implements S3-T03 and D-042 user role and capability boundaries.
 *
 * @package Backupino\Portal\Auth
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Manages custom portal roles and security capabilities.
 */
final class RoleManager {

	// Custom Portal Roles.
	public const ROLE_CUSTOMER = 'backupino_customer';
	public const ROLE_SUPPORT  = 'backupino_support';

	// Capabilities.
	public const CAP_MANAGE_ACCOUNT         = 'backupino_manage_account';
	public const CAP_TRIGGER_CLOUD_ACTIONS  = 'backupino_trigger_cloud_actions'; // Owner only (D-042)
	public const CAP_SUPPORT_VIEW           = 'backupino_support_view';
	public const CAP_SUPPORT_MANAGE_TICKETS = 'backupino_support_manage_tickets';
	public const CAP_ADMIN_PORTAL           = 'backupino_admin_portal';

	/**
	 * Register custom roles and assign capabilities.
	 */
	public static function register_roles(): void {
		// 1. Customer Role (can manage own account and trigger actions on own domains)
		add_role(
			self::ROLE_CUSTOMER,
			__( 'Backupino Customer', 'backupino-portal' ),
			array(
				'read'                         => true,
				self::CAP_MANAGE_ACCOUNT        => true,
				self::CAP_TRIGGER_CLOUD_ACTIONS => true,
			)
		);

		// Also grant Customer capabilities to WooCommerce 'customer' role if active.
		$wc_customer = get_role( 'customer' );
		if ( $wc_customer instanceof \WP_Role ) {
			$wc_customer->add_cap( self::CAP_MANAGE_ACCOUNT );
			$wc_customer->add_cap( self::CAP_TRIGGER_CLOUD_ACTIONS );
		}

		// 2. Support Specialist Role (CANNOT trigger cloud backup/restore actions per D-042)
		add_role(
			self::ROLE_SUPPORT,
			__( 'Backupino Support Specialist', 'backupino-portal' ),
			array(
				'read'                          => true,
				self::CAP_SUPPORT_VIEW           => true,
				self::CAP_SUPPORT_MANAGE_TICKETS => true,
				// NOTE: self::CAP_TRIGGER_CLOUD_ACTIONS is explicitly FALSE for Support!
			)
		);

		// 3. Administrator Role (Add portal management capabilities)
		$admin = get_role( 'administrator' );
		if ( $admin instanceof \WP_Role ) {
			$admin->add_cap( self::CAP_MANAGE_ACCOUNT );
			$admin->add_cap( self::CAP_SUPPORT_VIEW );
			$admin->add_cap( self::CAP_SUPPORT_MANAGE_TICKETS );
			$admin->add_cap( self::CAP_ADMIN_PORTAL );
			// Note: Even Admin cannot trigger a restore for customer without owning the license!
			// This is enforced strictly at runtime by AuthorizationGuard (D-042).
		}
	}
}
