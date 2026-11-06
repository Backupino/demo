<?php
/**
 * Main Portal Engine Coordinator.
 *
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

namespace Backupino\Portal;

use Backupino\Portal\Config\Environment;
use Backupino\Portal\Database\MigrationRunner;
use Backupino\Portal\Security\KeypairManager;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Auth\RoleManager;
use Backupino\Portal\Api\LicenseController;
use Backupino\Portal\Api\PlansController;
use Backupino\Portal\Commerce\OrderSyncService;
use Backupino\Portal\Commerce\PaymentGatewayManager;
use Backupino\Portal\Lifecycle\LicenseLifecycleManager;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton coordinator for the portal plugin.
 */
final class PortalEngine {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Boot portal engine on plugins_loaded.
	 */
	public function boot(): void {
		// Auto-migrate on activation or version change
		$installed_ver = get_option( 'backupino_portal_db_version', '0.0.0' );
		if ( version_compare( $installed_ver, BACKUPINO_PORTAL_VERSION, '<' ) ) {
			MigrationRunner::run();
			update_option( 'backupino_portal_db_version', BACKUPINO_PORTAL_VERSION );
		}

		// Ensure Ed25519 keypair exists
		KeypairManager::ensure_secure_dir();

		// Register portal roles and capabilities (S3-T03)
		RoleManager::register_roles();

		// Register REST API routes (S3-T04 / S3-T05 / S3-T10)
		add_action( 'rest_api_init', array( LicenseController::class, 'register_routes' ) );
		add_action( 'rest_api_init', array( PlansController::class, 'register_routes' ) );

		// Register Commerce & Order hooks (S3-T06)
		OrderSyncService::init();
		PaymentGatewayManager::init();

		// Register Lifecycle & Renewal hooks (S3-T07)
		LicenseLifecycleManager::init();

		// Add admin notice in development environment
		if ( Environment::is_dev() ) {
			add_action( 'admin_notices', array( $this, 'render_dev_notice' ) );
		}
	}

	/**
	 * Run on plugin activation.
	 */
	public function activate(): void {
		MigrationRunner::run();
		update_option( 'backupino_portal_db_version', BACKUPINO_PORTAL_VERSION );
		KeypairManager::initialize_keypair();
		AuditLogger::log( 'portal_engine_activated', get_current_user_id() );
	}

	/**
	 * Render subtle dev badge in wp-admin.
	 */
	public function render_dev_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible" style="border-inline-start-color: #0284c7;">
			<p>
				<strong>Backupino Portal Engine</strong> &mdash; 
				<span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">
					ENV: <?php echo esc_html( strtoupper( Environment::current() ) ); ?>
				</span>
				&bull; Ed25519 Public Key: <code><?php echo esc_html( substr( KeypairManager::get_public_key(), 0, 16 ) ); ?>...</code>
			</p>
		</div>
		<?php
	}
}
