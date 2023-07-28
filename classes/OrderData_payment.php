<?

namespace php2steblya;

class OrderData_payment
{
	private $externalId;
	private $amount;
	private $paidAt;
	private $type;
	private $status;
	private $comment;

	public function __construct($paymentFromTilda = null)
	{
		if (!$paymentFromTilda) return;
		$this->externalId = $paymentFromTilda['systranid'] ?: null;
		$this->amount = $paymentFromTilda['amount'];
		$this->paidAt = date('Y-m-d H:i:s');
		$this->type = 'site';
		$this->status = 'paid';
		$this->comment = '';
		if (isset($paymentFromTilda['promocode'])) $this->comment = 'применен промокод: "' . $paymentFromTilda['promocode'] . '" (' . $paymentFromTilda['discount'] . ' р.)';
	}
	public function getCrm()
	{
		return [
			'externalId' => $this->externalId,
			'amount' => $this->amount,
			'paidAt' => $this->paidAt,
			'type' => $this->type,
			'status' => $this->status,
			'comment' => $this->comment
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
	public function setComment($data)
	{
		$this->comment = $data;
	}
}
