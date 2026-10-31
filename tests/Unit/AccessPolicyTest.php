<?php
/**
 * Download access policy tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Download\AccessPolicy;
use Backupino\Download\DownloadLink;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;

/**
 * Covers every branch of the permanent-link authorisation rules.
 */
final class AccessPolicyTest extends TestCase {

	/**
	 * A wrong or missing key is refused before anything else.
	 *
	 * @return void
	 */
	public function test_wrong_key_is_forbidden(): void {
		$policy = $this->policy();

		$this->assertSame( AccessPolicy::FORBIDDEN, $policy->evaluate( 'nope', false, false, true ) );
		$this->assertSame( AccessPolicy::FORBIDDEN, $policy->evaluate( '', true, true, true ) );
	}

	/**
	 * A visitor without a session is sent to the login screen, not refused.
	 *
	 * @return void
	 */
	public function test_valid_key_without_session_asks_for_login(): void {
		$link   = $this->link();
		$policy = new AccessPolicy( $link );

		$this->assertSame( AccessPolicy::LOGIN, $policy->evaluate( $link->key(), false, false, true ) );
	}

	/**
	 * A signed in user without the capability may not download.
	 *
	 * @return void
	 */
	public function test_signed_in_without_capability_is_forbidden(): void {
		$link   = $this->link();
		$policy = new AccessPolicy( $link );

		$this->assertSame( AccessPolicy::FORBIDDEN, $policy->evaluate( $link->key(), true, false, true ) );
	}

	/**
	 * A valid administrator sees a clear "nothing yet" state.
	 *
	 * @return void
	 */
	public function test_missing_package_is_reported(): void {
		$link   = $this->link();
		$policy = new AccessPolicy( $link );

		$this->assertSame( AccessPolicy::MISSING, $policy->evaluate( $link->key(), true, true, false ) );
	}

	/**
	 * The happy path streams the package.
	 *
	 * @return void
	 */
	public function test_valid_request_is_allowed(): void {
		$link   = $this->link();
		$policy = new AccessPolicy( $link );

		$this->assertSame( AccessPolicy::ALLOW, $policy->evaluate( $link->key(), true, true, true ) );
	}

	/**
	 * Policy bound to a fresh link.
	 *
	 * @return AccessPolicy
	 */
	private function policy(): AccessPolicy {
		return new AccessPolicy( $this->link() );
	}

	/**
	 * Link bound to a memory store.
	 *
	 * @return DownloadLink
	 */
	private function link(): DownloadLink {
		return new DownloadLink( new MemoryOptions() );
	}
}
