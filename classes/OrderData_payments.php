<?

namespace php2steblya;

use php2steblya\OrderData_payment as Payment;

class OrderData_payments
{
	private array $payments;

	public function fromTilda(array $paymentFromTilda)
	{
		$payment = new Payment($paymentFromTilda);
		$this->payments[] = $payment;
	}
	public function getCrm(): array
	{
		$payments = [];
		foreach ($this->payments as $payment) {
			$payments[] = $payment->getCrm();
		}
		return $payments;
	}
	public function push($data)
	{
		$this->payments[] = $data;
	}
}
