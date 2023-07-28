<?

namespace php2steblya;

use php2steblya\TelegramBot_state_factory as StateFactory;

trait TelegramBot_trait_state
{
	/**
	 * отрабатываем стейт
	 */
	protected function state($state)
	{
		$this->chat->state = $state;
		$stateFactory = new StateFactory();
		$stateObject = $stateFactory->createState($this->bot, $this->chat);
		$stateObject->init();
		$this->chat = $stateObject->chat;
		$this->response = $stateObject->response;
	}
}
