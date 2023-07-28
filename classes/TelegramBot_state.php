<?

namespace php2steblya;

abstract class TelegramBot_state
{
	use TelegramBot_trait_hello;
	use TelegramBot_trait_state;
	use TelegramBot_trait_finish;

	public $chat;
	protected $bot;
	protected $message;
	protected $messageKey;
	public array $response;

	public function __construct($bot, $chat)
	{
		$this->bot = $bot;
		$this->chat = $chat;
		$this->response = [];
		$this->message = ltrim($this->chat->message, '/');
	}

	/**
	 * добавляем сообщение юзера в массив сообщений
	 */
	private function addMessage()
	{
		if (!$this->chat->message) return;
		$this->chat->messages[$this->messageKey ?: count($this->chat->messages)] = $this->chat->message;
	}

	/**
	 * инициализируем стейт
	 * внутри стейта можно инициализировать новый стейт
	 */
	abstract protected function process();
	public function init()
	{
		$this->hello($this->bot);
		$this->process();
		$this->addMessage();
		$this->finish($this->bot);
	}
}
