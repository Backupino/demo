<?php
/**
 * Notification message.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Notify;

use Backupino\Support\Bytes;

/**
 * Builds the plain text that is delivered to a messenger.
 */
final class Message {

	/**
	 * Message body.
	 *
	 * @var string
	 */
	private $text;

	/**
	 * Constructor.
	 *
	 * @param string $text Body.
	 */
	public function __construct( string $text ) {
		$this->text = trim( $text );
	}

	/**
	 * Body.
	 *
	 * @return string
	 */
	public function text(): string {
		return $this->text;
	}

	/**
	 * Whether there is anything to send.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return '' === $this->text;
	}

	/**
	 * "Backup finished" message.
	 *
	 * @param string $site  Site name.
	 * @param string $kind  What was included.
	 * @param int    $size  Archive size in bytes.
	 * @param string $when  Human readable timestamp.
	 * @param string $link  Permanent download link.
	 * @return self
	 */
	public static function backup_completed( string $site, string $kind, int $size, string $when, string $link ): self {
		$lines = array(
			/* translators: %s: site name. */
			sprintf( __( 'Backupino — backup finished: %s', 'backupino' ), $site ),
			/* translators: %s: backup contents, e.g. "files and database". */
			sprintf( __( 'Contents: %s', 'backupino' ), $kind ),
			/* translators: %s: archive size, e.g. "128.40 MB". */
			sprintf( __( 'Size: %s', 'backupino' ), Bytes::format( $size ) ),
			/* translators: %s: date and time. */
			sprintf( __( 'Date: %s', 'backupino' ), $when ),
			'',
			__( 'Download (sign in as an administrator):', 'backupino' ),
			$link,
		);

		return new self( implode( "\n", $lines ) );
	}

	/**
	 * Message used by the "send test" button.
	 *
	 * @param string $site Site name.
	 * @param string $link Permanent download link.
	 * @return self
	 */
	public static function test( string $site, string $link ): self {
		$lines = array(
			/* translators: %s: site name. */
			sprintf( __( 'Backupino — test message from %s', 'backupino' ), $site ),
			__( 'Notifications are working. Backups will be announced here.', 'backupino' ),
			'',
			$link,
		);

		return new self( implode( "\n", $lines ) );
	}
}
