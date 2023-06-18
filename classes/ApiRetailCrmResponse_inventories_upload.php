<?

namespace php2steblya;

class ApiRetailCrmResponse_inventories_upload extends ApiRetailCrmResponse
{
	public function __construct($source, array $args)
	{
		$this->log = new Logger('upload inventory');
		parent::__construct($source);
		$this->log->push('orderData', json_decode($args['order'], true));
		$this->method = 'store/inventories/upload';
		$this->args = $args;
		$this->request('post');
	}
	public function getRemark()
	{
		return $this->response->processedOffersCount . 'inventories uploaded';
	}
}
