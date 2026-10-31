<?php
/**
 * Notification orchestrator.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Notify;

use Backupino\Core\Settings;
use Backupino\Download\DownloadLink;
use Backupino\Jobs\Job;
use Backupino\Support\OptionsStore;

/**
 * Sends backup notifications and retries the ones that failed.
 *
 * Notifications are a courtesy: a broken messenger never fails a backup, it
 * only fills the retry queue.
 */
final class Notifier {

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Retry queue.
	 *
	 * @var Queue
	 */
	private $queue;

	/**
	 * Permanent link.
	 *
	 * @var DownloadLink
	 */
	private $link;

	/**
	 * Optional HTTP transport, used by the tests.
	 *
	 * @var callable|null
	 */
	private $http;

	/**
	 * Last error.
	 *
	 * @var string
	 */
	private $error = '';

	/**
	 * Constructor.
	 *
	 * @param Settings      $settings Settings.
	 * @param OptionsStore  $options  Options store for the queue.
	 * @param DownloadLink  $link     Permanent link service.
	 * @param callable|null $http     Optional HTTP transport.
	 */
	public function __construct( Settings $settings, OptionsStore $options, DownloadLink $link, ?callable $http = null ) {
		$this->settings = $settings;
		$this->queue    = new Queue( $options );
		$this->link     = $link;
		$this->http     = $http;
	}

	/**
	 * Configured service id.
	 *
	 * @return string
	 */
	public function service(): string {
		return (string) $this->settings->get( 'notify.service', 'none' );
	}

	/**
	 * Whether a messenger is configured well enough to send.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$channel = $this->channel();

		return null !== $channel && $channel->is_configured();
	}

	/**
	 * Configured transport.
	 *
	 * @return BotApi|null
	 */
	private function channel(): ?BotApi {
		return ChannelFactory::make(
			$this->service(),
			(string) $this->settings->get( 'notify.token', '' ),
			(string) $this->settings->get( 'notify.chat_id', '' ),
			$this->http
		);
	}

	/**
	 * Announce a finished backup.
	 *
	 * @param Job    $job  Finished job.
	 * @param string $site Site name.
	 * @return bool
	 */
	public function announce( Job $job, string $site ): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		$package = isset( $job->payload['package'] ) && is_array( $job->payload['package'] )
			? $job->payload['package']
			: array();

		$files    = (bool) $job->option( 'files', true );
		$database = (bool) $job->option( 'database', true );

		if ( $files && $database ) {
			$kind = __( 'files and database', 'backupino' );
		} elseif ( $database ) {
			$kind = __( 'database only', 'backupino' );
		} else {
			$kind = __( 'files only', 'backupino' );
		}

		$message = Message::backup_completed(
			$site,
			$kind,
			isset( $package['size'] ) ? (int) $package['size'] : 0,
			'' !== $job->created_at ? $job->created_at : gmdate( 'Y-m-d H:i' ),
			$this->link->url()
		);

		return $this->send( $message );
	}

	/**
	 * Send the message behind the "test" button.
	 *
	 * @param string $site Site name.
	 * @return bool
	 */
	public function send_test( string $site ): bool {
		return $this->send( Message::test( $site, $this->link->url() ) );
	}

	/**
	 * Deliver a message, queueing it when the messenger is unreachable.
	 *
	 * @param Message $message Message.
	 * @return bool
	 */
	public function send( Message $message ): bool {
		$this->error = '';

		$channel = $this->channel();

		if ( null === $channel || ! $channel->is_configured() ) {
			$this->error = __( 'No messenger is configured.', 'backupino' );

			return false;
		}

		if ( $channel->send( $message ) ) {
			return true;
		}

		$this->error = $channel->error();
		$this->queue->push( $message->text() );

		return false;
	}

	/**
	 * Retry queued messages.
	 *
	 * @return int Delivered messages.
	 */
	public function flush(): int {
		if ( $this->queue->is_empty() ) {
			return 0;
		}

		$channel = $this->channel();

		if ( null === $channel || ! $channel->is_configured() ) {
			return 0;
		}

		return $this->queue->flush(
			static function ( string $text ) use ( $channel ) {
				return $channel->send( new Message( $text ) );
			}
		);
	}

	/**
	 * Pending messages.
	 *
	 * @return int
	 */
	public function pending(): int {
		return count( $this->queue->all() );
	}

	/**
	 * Last error.
	 *
	 * @return string
	 */
	public function error(): string {
		return $this->error;
	}
}
