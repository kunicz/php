<?

namespace php2steblya;

class OrderData_payment
{
	private $externalId;
	private $amount;
	private $paidAt;
	private $type;
	private $status;

	public function __construct($paymentFromTilda = null)
	{
		if (!$paymentFromTilda) return;
		if (isset($paymentFromTilda['systranid'])) $this->externalId = $paymentFromTilda['systranid'];
		$this->amount = $paymentFromTilda['amount'];
		$this->paidAt = date('Y-m-d H:i:s');
		$this->type = 'site';
		$this->status = 'paid';
	}
	public function getCrm()
	{
		return [
			'externalId' => $this->externalId,
			'amount' => $this->amount,
			'paidAt' => $this->paidAt,
			'type' => $this->type,
			'status' => $this->status
		];
	}
	public function setExternalId($data)
	{
		$this->externalId = $data;
	}
	public function setAmount($data)
	{
		$this->amount = $data;
	}
	public function setPaidAt($data)
	{
		$this->paidAt = $data;
	}
	public function setType($data)
	{
		$this->type = $data;
	}
	public function setStatus($data)
	{
		$this->status = $data;
	}
}
