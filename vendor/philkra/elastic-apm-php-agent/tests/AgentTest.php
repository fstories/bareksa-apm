<?php
namespace PhilKra\Tests;

use \PhilKra\Agent;
use PhilKra\Stores\ErrorsStore;
use PhilKra\Stores\TransactionsStore;
use \PhilKra\Transaction\Summary;
use PHPUnit\Framework\MockObject\MockObject;
use Yaoi\Mock;

/**
 * Test Case for @see \PhilKra\Agent
 */
final class AgentTest extends TestCase {

  /**
   * @covers \PhilKra\Agent::__construct
   * @covers \PhilKra\Agent::startTransaction
   * @covers \PhilKra\Agent::stopTransaction
   * @covers \PhilKra\Agent::getTransaction
   */
  public function testStartAndStopATransaction() {
    $agent = new Agent( [ 'appName' => 'phpunit_1' ] );

    // Create a Transaction, wait and Stop it
    $name = 'trx';
    $agent->startTransaction( $name );
    usleep( 10 * 1000 ); // sleep milliseconds
    $agent->stopTransaction( $name );

    // Transaction Summary must be populated
    $summary = $agent->getTransaction( $name )->getSummary();

    $this->assertArrayHasKey( 'duration', $summary );
    $this->assertArrayHasKey( 'backtrace', $summary );

    // Expect duration in milliseconds
    $this->assertDurationIsWithinThreshold(10, $summary['duration']);
    $this->assertNotEmpty( $summary['backtrace'] );
  }

  /**
   * @covers \PhilKra\Agent::__construct
   * @covers \PhilKra\Agent::startTransaction
   * @covers \PhilKra\Agent::stopTransaction
   * @covers \PhilKra\Agent::getTransaction
   */
  public function testStartAndStopATransactionWithExplicitStart() {
    $agent = new Agent( [ 'appName' => 'phpunit_1' ] );

    // Create a Transaction, wait and Stop it
    $name = 'trx';
    $agent->startTransaction( $name, [], microtime(true) - 1);
    usleep( 500 * 1000 ); // sleep milliseconds
    $agent->stopTransaction( $name );

    // Transaction Summary must be populated
    $summary = $agent->getTransaction( $name )->getSummary();

    $this->assertArrayHasKey( 'duration', $summary );
    $this->assertArrayHasKey( 'backtrace', $summary );

    // Expect duration in milliseconds
    $this->assertDurationIsWithinThreshold(1500, $summary['duration'], 150);
    $this->assertNotEmpty( $summary['backtrace'] );
  }

  /**
   * @depends testStartAndStopATransaction
   *
   * @covers \PhilKra\Agent::__construct
   * @covers \PhilKra\Agent::getTransaction
   */
  public function testForceErrorOnUnknownTransaction() {
    $agent = new Agent( [ 'appName' => 'phpunit_x' ] );

    $this->expectException( \PhilKra\Exception\Transaction\UnknownTransactionException::class );

    // Let it go boom!
    $agent->getTransaction( 'unknown' );
  }

  /**
   * @depends testForceErrorOnUnknownTransaction
   * 
   * @covers \PhilKra\Agent::__construct
   * @covers \PhilKra\Agent::stopTransaction
   */
  public function testForceErrorOnUnstartedTransaction() {
    $agent = new Agent( [ 'appName' => 'phpunit_2' ] );

    $this->expectException( \PhilKra\Exception\Transaction\UnknownTransactionException::class );

    // Stop an unstarted Transaction and let it go boom!
    $agent->stopTransaction( 'unknown' );
  }

  public function testClearErrorStoreOnSendWhenNotActive()
  {
      /** @var TransactionsStore|MockObject $transactionStore */
      $transactionStore = $this->createMock(TransactionsStore::class);
      /** @var ErrorsStore|MockObject $errorStore */
      $errorStore = $this->createMock(ErrorsStore::class);

      $agent = new Agent(
          [ 'appName' => 'phpunit_1', 'active' => false ],
          [],
          null,
          $transactionStore,
          $errorStore
      );

      $transactionStore->expects($this->once())->method('reset');
      $errorStore->expects($this->once())->method('reset');

      $agent->send();
  }
}
