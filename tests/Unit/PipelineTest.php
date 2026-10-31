<?php
/**
 * Pipeline tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Jobs\JobContext;
use Backupino\Jobs\Pipeline;
use Backupino\Jobs\Stage;
use Backupino\Jobs\StageResult;
use Backupino\Tests\TestCase;

/**
 * Fake stage used by the pipeline tests.
 */
final class FakeStage implements Stage {

	/**
	 * Stage id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Stage weight.
	 *
	 * @var int
	 */
	private $weight;

	/**
	 * Constructor.
	 *
	 * @param string $id     Stage id.
	 * @param int    $weight Weight.
	 */
	public function __construct( string $id, int $weight ) {
		$this->id     = $id;
		$this->weight = $weight;
	}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function label(): string {
		return $this->id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function weight(): int {
		return $this->weight;
	}

	/**
	 * {@inheritDoc}
	 */
	public function run( JobContext $context ): StageResult {
		return StageResult::completed();
	}
}

/**
 * Covers stage ordering and progress maths.
 */
final class PipelineTest extends TestCase {

	/**
	 * Build the pipeline under test.
	 *
	 * @return Pipeline
	 */
	private function pipeline(): Pipeline {
		return new Pipeline(
			array(
				new FakeStage( 'scan', 10 ),
				new FakeStage( 'files', 50 ),
				new FakeStage( 'database', 30 ),
				new FakeStage( 'finalize', 10 ),
			)
		);
	}

	/**
	 * Stages keep their order.
	 *
	 * @return void
	 */
	public function test_order(): void {
		$pipeline = $this->pipeline();

		$this->assertSame( 'scan', $pipeline->first() );
		$this->assertSame( 'files', $pipeline->next( 'scan' ) );
		$this->assertSame( 'database', $pipeline->next( 'files' ) );
		$this->assertSame( 'finalize', $pipeline->next( 'database' ) );
		$this->assertSame( '', $pipeline->next( 'finalize' ) );
		$this->assertNull( $pipeline->get( 'nope' ) );
	}

	/**
	 * Progress is weighted across stages.
	 *
	 * @return void
	 */
	public function test_progress(): void {
		$pipeline = $this->pipeline();

		$this->assertSame( 0, $pipeline->progress( 'scan', 0.0 ) );
		$this->assertSame( 10, $pipeline->progress( 'scan', 1.0 ) );
		$this->assertSame( 35, $pipeline->progress( 'files', 0.5 ) );
		$this->assertSame( 100, $pipeline->progress( 'finalize', 1.0 ) );
	}

	/**
	 * A single stage pipeline is handled.
	 *
	 * @return void
	 */
	public function test_single_stage(): void {
		$pipeline = new Pipeline( array( new FakeStage( 'only', 1 ) ) );

		$this->assertSame( 'only', $pipeline->first() );
		$this->assertSame( '', $pipeline->next( 'only' ) );
		$this->assertSame( 50, $pipeline->progress( 'only', 0.5 ) );
	}

	/**
	 * Stage results clamp their fraction.
	 *
	 * @return void
	 */
	public function test_stage_result_clamps(): void {
		$this->assertSame( 1.0, StageResult::running( 5.0 )->fraction() );
		$this->assertSame( 0.0, StageResult::running( -2.0 )->fraction() );
		$this->assertTrue( StageResult::completed()->is_completed() );
		$this->assertFalse( StageResult::running( 0.5 )->is_completed() );
	}
}
