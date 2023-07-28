<?

namespace php2steblya;

use stdClass;
use php2steblya\Logger;

class TelegramBot_chat
{
	use TelegramBot_trait_state;

	public $log;
	private $db;
	private $api;
	private $bot;
	private $update;
	private $chatId;
	protected object $chat;
	protected array $response;

	public function __construct($db, $api, $bot, $update)
	{
		$this->log = new Logger();
		$this->db = $db;
		$this->api = $api;
		$this->bot = $bot;
		$this->update = $update;
		$this->chatId = isset($update['callback_query']) ? $update['callback_query']['message']['chat']['id'] : $update['message']['chat']['id'];
		$this->chat();
		$this->state($this->chat->state);
		$this->update();
		$this->response();
	}

	/**
	 * получаем или создаем чат
	 */
	private function chat()
	{
		$c = $this->db->getTelegramChat($this->bot, $this->chatId);
		if ($c) {
			$this->chat = $c;
			$this->chat->user = json_decode($this->chat->user);
			$this->chat->messages = json_decode($this->chat->messages, true);
		} else {
			$this->chat = new stdClass();
			$this->chat->id = $this->chatId;
			$this->chat->updated = date('Y.m.d H:i:s');
			$this->chat->state = 'start';
			$this->chat->user = $this->user();
			$this->chat->messages = [];
		}
		$this->chat->callback = isset($this->update['callback_query']) ? $this->update['callback_query']['data'] : '';
		$this->chat->message = isset($this->update['callback_query']) ? '' : $this->update['message']['text'];
		$this->chat->initial_state = $this->chat->state;
		$this->log->push('chatInitial', $this->chat);
	}

	/**
	 * получаем юзера
	 */
	private function user()
	{
		switch ($this->bot) {
			case 'employee':
				return $this->db->getEmployee('telegram_chat_id', $this->chatId);
			case 'stf':
			case '2steblya':
				return new stdClass(); // заменить на запрос из базы данных
		}
	}

	/**
	 * обновляем чат в базе данных
	 */
	public function update()
	{
		$this->log->push('chatModified', $this->chat);
		$data = [
			'chat_id' => $this->chat->id,
			'state' => $this->chat->state,
			'state_comment' => $this->chat->state_comment,
			'user' => $this->chat->user,
			'messages' => $this->chat->messages
		];
		$this->db->setTelegramChat($this->bot, $data);
	}

	/**
	 * отправляем ответ юзеру в телеграм
	 * может быть несколько сообщений, поэтому итерируем массив
	 */
	public function response()
	{
		foreach ($this->response as $i => $args) {
			$this->log->push('chatResponse' . $i, $args);
			$args['chat_id'] = $this->chatId;
			$this->api->post('sendMessage', $args);
			if ($this->api->hasErrors()) {
				$this->log->pushError($this->api->getError());
			}
			$this->log->push('apiResponse' . $i, $this->api->response);
		}
	}

	public function getLog()
	{
		return $this->log->get();
	}
}
