<?php

namespace php2steblya\scripts;

use php2steblya\Script;

class Test extends Script
{
	public function init()
	{
		$this->logger->add('shop_cr_id', GVOZDISCO_CRM_ID);
		$args = [
			'where' => ['shop_crm_id' => '2'],
			'limit' => 5
		];
		$response = $this->db->products()->get($args);
		$this->setResponse($response);
	}
}
