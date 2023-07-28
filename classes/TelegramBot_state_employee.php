<?

namespace php2steblya;

abstract class TelegramBot_state_employee extends TelegramBot_state
{
	protected $replyTo;
	protected array $jobs;
	protected array $tasks;

	public function __construct($bot, $chat)
	{
		parent::__construct($bot, $chat);
		$this->replyTo = '@staytrueflowers';
		$this->defineJobs();
		$this->defineEmployeeJobs();
		$this->defineTasks();
	}

	/**
	 * получаем профессии сотрудника
	 */
	private function defineEmployeeJobs()
	{
		if (!isset($this->chat->user->is_employee)) {
			$this->chat->user->is_employee = 0;
			$this->chat->user->is_admin = 0;
			$this->chat->user->is_manager = 0;
			$this->chat->user->is_courier = 0;
			$this->chat->user->is_florist = 0;
		}
		$this->chat->user->jobs = [];
		foreach ($this->jobs as $job_id => $job_title) {
			$is_job = 'is_' . $job_id;
			if ($this->chat->user->$is_job) $this->chat->user->jobs[] = ['id' => $job_id, 'title' => $job_title];
		}
	}

	/**
	 * все возможные должности
	 */
	private function defineJobs()
	{
		$this->jobs = [
			'admin' => 'администратор',
			'manager' => 'менеджер',
			'florist' => 'флорист',
			'courier' => 'курьер'

		];
	}

	/**
	 * все возможные задачи для должностей
	 */
	private function defineTasks()
	{
		$this->tasks = [];
		$globalTasks =
			[
				['Заказы на сегодня', 'orders_today'],
				['Заказы на завтра', 'orders_tomorrow'],
				['Персональные данные', 'personal']
			];
		$jobTasks = [];
		foreach ($this->jobs as $job_id => $job_title) {
			switch ($job_id) {
				case 'admin':
					$jobTasks[] = ['Зарплата флористов', 'florist_salary'];
					break;
				case 'manager':
					break;
				case 'florist':
					break;
				case 'courier':
					break;
			}
			$this->tasks[$job_id] = array_merge($globalTasks, $jobTasks);
		}
	}
}
