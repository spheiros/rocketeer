<?php
namespace Rocketeer\TestCases;

trait RocketeerAssertions
{
	/**
	 * Assert that the current connection is a specific one
	 *
	 * @param string $connection
	 */
	protected function assertConnectionEquals($connection)
	{
		$this->assertEquals($connection, $this->connections->getConnection());
	}

	/**
	 * Assert that the current repository equals
	 *
	 * @param string $repository
	 */
	protected function assertRepositoryEquals($repository)
	{
		$this->assertEquals($repository, $this->connections->getRepository());
	}

	/**
	 * Assert an option has a certain value
	 *
	 * @param string $value
	 * @param string $option
	 */
	protected function assertOptionValueEquals($value, $option)
	{
		$this->assertEquals($value, $this->rocketeer->getOption($option));
	}

	/**
	 * Assert a task has a particular output
	 *
	 * @param string  $task
	 * @param string  $output
	 * @param \Mockery $command
	 *
	 * @return Assertion
	 */
	protected function assertTaskOutput($task, $output, $command = null)
	{
		if ($command) {
			$this->app['rocketeer.command'] = $command;
		}

		return $this->assertContains($output, $this->task($task)->execute());
	}

	/**
	 * Assert a task's history matches an array
	 *
	 * @param string|Task $task
	 * @param array       $expectedHistory
	 * @param array       $options
	 *
	 * @return string
	 */
	protected function assertTaskHistory($task, array $expectedHistory, array $options = array())
	{
		// Create task if needed
		if (is_string($task)) {
			$task = $this->pretendTask($task, $options);
		}

		// Execute task and get history
		if (is_array($task)) {
			$results     = '';
			$taskHistory = $task;
		} else {
			$results     = $task->execute();
			$taskHistory = $task->history->getFlattenedHistory();
		}

		$this->assertHistory($expectedHistory, $taskHistory);

		return $results;
	}

	/**
	 * Assert an history matches another
	 *
	 * @param array $expected
	 * @param array $obtained
	 */
	public function assertHistory(array $expected, array $obtained)
	{
		// Look for release in history
		$release = implode(array_flatten($obtained));
		preg_match_all('/[0-9]{14}/', $release, $releases);
		$release = array_get($releases, '0.0', date('YmdHis'));
		if ($release === '10000000000000') {
			$release = array_get($releases, '0.1', date('YmdHis'));
		}

		// Replace placeholders
		$expected = $this->replaceHistoryPlaceholders($expected, $release);

		// Check equality
		$this->assertEquals($expected, $obtained);
	}

	//////////////////////////////////////////////////////////////////////
	////////////////////////////// HELPERS ///////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * Replace placeholders in an history
	 *
	 * @param array        $history
	 * @param integer|null $release
	 *
	 * @return array
	 */
	protected function replaceHistoryPlaceholders($history, $release = null)
	{
		$release = $release ?: date('YmdHis');

		foreach ($history as $key => $entries) {
			if (is_array($entries)) {
				$history[$key] = $this->replaceHistoryPlaceholders($entries, $release);
				continue;
			}

			$history[$key] = strtr($entries, array(
				'{php}'        => exec('which php'),
				'{phpunit}'    => exec('which phpunit'),
				'{repository}' => 'https://github.com/'.$this->repository,
				'{server}'     => $this->server,
				'{release}'    => $release,
				'{composer}'   => exec('which composer'),
			));
		}

		return $history;
	}
}