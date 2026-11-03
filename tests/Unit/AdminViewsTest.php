<?php
/**
 * Admin view rendering tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Admin\Views;
use Backupino\Tests\TestCase;

/**
 * Tests covering admin templates rendering.
 */
final class AdminViewsTest extends TestCase {

	/**
	 * Test dashboard template renders without errors.
	 *
	 * @return void
	 */
	public function test_dashboard_template_renders(): void {
		ob_start();
		Views::render( 'dashboard' );
		$html = ob_get_clean();

		$this->assertStringContains( 'backupino-wrap', $html );
		$this->assertStringContains( 'Settings', $html );
	}

	/**
	 * Test settings template renders without errors.
	 *
	 * @return void
	 */
	public function test_settings_template_renders(): void {
		ob_start();
		Views::render( 'settings' );
		$html = ob_get_clean();

		$this->assertStringContains( 'backupino-wrap', $html );
		$this->assertStringContains( 'backupino-settings', $html );
	}

	/**
	 * Test view URLs.
	 *
	 * @return void
	 */
	public function test_view_urls(): void {
		$dashboard_url = Views::url();
		$settings_url  = Views::url( 'settings' );

		$this->assertStringContains( 'page=backupino', $dashboard_url );
		$this->assertStringContains( 'page=backupino-settings', $settings_url );
	}
}
