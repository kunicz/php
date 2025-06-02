<?

namespace print_card;

use php2steblya\Logger;

class CardFactory
{
	public function getInstance($data)
	{
		$className = __NAMESPACE__ . "\\Card_{$data['shop_crm_code']}";
		Logger::getInstance()
			->addRoot('card_class', $className)
			->addRoot('card_class_exist', class_exists($className));

		return class_exists($className) ? new $className($data) : new Card($data);
	}
}
