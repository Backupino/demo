<?php
/**
 * S3 signature tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Storage\S3\S3Client;
use Backupino\Storage\S3\S3Signer;
use Backupino\Tests\TestCase;

/**
 * Verifies the SigV4 implementation against the example published in the AWS
 * S3 documentation ("Authenticating Requests: Using the Authorization Header").
 */
final class S3SignerTest extends TestCase {

	/**
	 * The documented example request.
	 *
	 * @return array<string, mixed>
	 */
	private function example(): array {
		return array(
			'method'       => 'GET',
			'uri'          => '/test.txt',
			'query'        => array(),
			'headers'      => array(
				'Host'  => 'examplebucket.s3.amazonaws.com',
				'Range' => 'bytes=0-9',
			),
			'payload_hash' => S3Client::empty_hash(),
			'region'       => 'us-east-1',
			'service'      => 's3',
			'access_key'   => 'AKIAIOSFODNN7EXAMPLE',
			'secret_key'   => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
			'amz_date'     => '20130524T000000Z',
		);
	}

	/**
	 * The canonical request matches the specification.
	 *
	 * @return void
	 */
	public function test_canonical_request_matches_aws_example(): void {
		$request = $this->example();
		$actual  = S3Signer::canonical_request( $request );

		$expected = "GET\n"
			. "/test.txt\n"
			. "\n"
			. "host:examplebucket.s3.amazonaws.com\n"
			. 'range:bytes=0-9' . "\n"
			. 'x-amz-content-sha256:' . S3Client::empty_hash() . "\n"
			. "x-amz-date:20130524T000000Z\n"
			. "\n"
			. 'host;range;x-amz-content-sha256;x-amz-date' . "\n"
			. S3Client::empty_hash();

		$this->assertSame( $expected, $actual );
		$this->assertSame(
			'7344ae5b7ee6c3e7e6b0fe0640412a37625d1fbfff95c48bbb2dc43964946972',
			hash( 'sha256', $actual )
		);
	}

	/**
	 * The resulting Authorization header matches the documented signature.
	 *
	 * @return void
	 */
	public function test_signature_matches_aws_example(): void {
		$headers = S3Signer::sign( $this->example() );

		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertSame(
			'AWS4-HMAC-SHA256 Credential=AKIAIOSFODNN7EXAMPLE/20130524/us-east-1/s3/aws4_request,'
			. 'SignedHeaders=host;range;x-amz-content-sha256;x-amz-date,'
			. 'Signature=f0e8bdb87c964420e857bd35b5d6ed310bd44f0170aba48dd91039c6036bdb41',
			$headers['Authorization']
		);
	}

	/**
	 * Header names are case-insensitive and values are whitespace-normalized.
	 *
	 * @return void
	 */
	public function test_headers_are_normalized(): void {
		$request = $this->example();
		$request['headers'] = array(
			'host'  => 'examplebucket.s3.amazonaws.com',
			'Range' => '  bytes=0-9  ',
		);

		$this->assertSame(
			S3Signer::canonical_request( $this->example() ),
			S3Signer::canonical_request( $request )
		);
	}

	/**
	 * Query parameters are sorted and encoded.
	 *
	 * @return void
	 */
	public function test_query_is_canonicalized(): void {
		$request = $this->example();
		$request['uri']   = '/bucket/key with space.txt';
		$request['query'] = array( 'b' => '2', 'a' => '1' );

		$canonical = S3Signer::canonical_request( $request );
		$lines     = explode( "\n", $canonical );

		$this->assertSame( '/bucket/key%20with%20space.txt', $lines[1] );
		$this->assertSame( 'a=1&b=2', $lines[2] );
	}

	/**
	 * The signing key derivation is deterministic.
	 *
	 * @return void
	 */
	public function test_signing_key_is_stable(): void {
		$key = S3Signer::signing_key(
			'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
			'20130524',
			'us-east-1',
			's3'
		);

		$this->assertSame( 32, strlen( $key ) );
		$this->assertSame( $key, S3Signer::signing_key(
			'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
			'20130524',
			'us-east-1',
			's3'
		) );
	}
}
