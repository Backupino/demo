<?php
/**
 * Bot API transport.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Notify;

/**
 * Talks to a Telegram compatible Bot API.
 *
 * Bale exposes the same endpoints as Telegram, so a single transport serves
 * both services; only the base URL changes.
 */
final class BotApi {

	/**
	 * Base URL without the trailing slash.
	 *
	 * @var string
	 */
	private $base;

	/**
	 * Bot token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Target chat id.
	 *
	 * @var string
	 */
	private $chat_id;

	/**
	 * HTTP transport: `function ( string $url, array $args ): array`.
	 *
	 * @var callable
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
	 * @param string        $base    Base URL, e.g. `https://api.telegram.org`.
	 * @param string        $token   Bot token.
	 * @param string        $chat_id Target chat.
	 * @param callable|null $http    Optional HTTP transport.
	 */
	public function __construct( string $base, string $token, string $chat_id, ?callable $http = null ) {
		$this->base    = rtrim( $base, '/' );
		$this->token   = trim( $token );
		$this->chat_id = trim( $chat_id );

		$this->http = $http ?? static function ( string $url, array $args ): array {
			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				return array(
					'code'  => 0,
					'body'  => '',
					'error' => $response->get_error_message(),
				);
			}

			return array(
				'code'  => (int) wp_remote_retrieve_response_code( $response ),
				'body'  => (string) wp_remote_retrieve_body( $response ),
				'error' => '',
			);
		};
	}

	/**
	 * Whether the channel has everything it needs.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return '' !== $this->token && '' !== $this->chat_id;
	}

	/**
	 * Endpoint of the `sendMessage` method.
	 *
	 * @return string
	 */
	public function endpoint(): string {
		return $this->base . '/bot' . $this->token . '/sendMessage';
	}

	/**
	 * Payload for a message.
	 *
	 * @param Message $message Message.
	 * @return array<string, mixed>
	 */
	public function payload( Message $message ): array {
		return array(
			'chat_id'                  => $this->chat_id,
			'text'                     => $message->text(),
			'disable_web_page_preview' => true,
		);
	}

	/**
	 * Deliver a message.
	 *
	 * @param Message $message Message.
	 * @return bool
	 */
	public function send( Message $message ): bool {
		$this->error = '';

		if ( ! $this->is_configured() ) {
			$this->error = __( 'The bot token or chat id is missing.', 'backupino' );

			return false;
		}

		if ( $message->is_empty() ) {
			$this->error = __( 'There is nothing to send.', 'backupino' );

			return false;
		}

		$result = ( $this->http )(
			$this->endpoint(),
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => (string) wp_json_encode( $this->payload( $message ) ),
			)
		);

		$result = is_array( $result ) ? $result : array();

		if ( ! empty( $result['error'] ) ) {
			$this->error = (string) $result['error'];

			return false;
		}

		$code = isset( $result['code'] ) ? (int) $result['code'] : 0;
		$body = isset( $result['body'] ) ? (string) $result['body'] : '';
		$json = json_decode( $body, true );

		if ( 200 !== $code || ! is_array( $json ) ) {
			/* translators: %d: HTTP status code. */
			$this->error = sprintf( __( 'The messenger answered with HTTP %d.', 'backupino' ), $code );

			return false;
		}

		if ( empty( $json['ok'] ) ) {
			$this->error = isset( $json['description'] )
				? (string) $json['description']
				: __( 'The messenger rejected the message.', 'backupino' );

			return false;
		}

		return true;
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
