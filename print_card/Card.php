<?php

namespace print_card;

use \php2steblya\Logger;

class Card
{
	protected Logger $logger;
	protected string $shopCrmCode;
	protected string $title;
	protected string $type;
	protected string $ourContent;
	protected string $customerContent;
	protected string $customerChoice;
	protected string $logosPath = '/print_card/logos';
	protected string $qrCodesPath = '/print_card/qr_codes';
	protected array $qrCodes = [];

	public function __construct(array $data)
	{
		$this->logger = Logger::getInstance();
		$this->shopCrmCode = $data['shop_crm_code'];
		$this->title = $data['title'];
		$this->type = $data['type'];
		$this->ourContent = $data['our_content'];
		$this->customerContent = $data['customer_content'];
		$this->customerChoice = $data['customer_choice'];
		$this->qrCodes = $this->getQrCodes();
	}

	// определяет, нужно ли показывать логотип.
	protected function shouldShowLogo(): bool
	{
		return $this->customerChoice !== 'без айдентики';
	}

	// определяет, нужно ли показывать наш заголовок.
	protected function shouldShowOurTitle(): bool
	{
		return $this->customerChoice === 'с нашей карточкой';
	}

	// определяет, нужно ли показывать наш текст.
	protected function shouldShowOurText(): bool
	{
		return $this->customerChoice === 'с нашей карточкой';
	}

	// определяет, нужно ли показывать текст клиента.
	protected function shouldShowCustomerText(): bool
	{
		return !empty($this->customerContent);
	}

	// определяет, нужно ли показывать QR-коды.
	protected function shouldShowQRCodes(): bool
	{
		return $this->customerChoice !== 'без айдентики';
	}

	// получает массив QR-кодов.
	protected function getQrCodes(): array
	{
		$qrCodes = [];
		foreach ($this->getQrCodesCaptions() as $key => $caption) {
			$qrCodes[$key] = [
				'url' => "{$this->qrCodesPath}/{$this->shopCrmCode}/{$key}.gif",
				'caption' => $caption
			];
		}

		$this->logger->addRoot('qr_codes', $qrCodes);
		return $qrCodes;
	}

	// подписи к QR-кодам.
	protected function getQrCodesCaptions(): array
	{
		return [
			'main' => "сайт\n{$this->shopCrmCode}",
			'telegram_channel' => "телеграм\nканал",
			'care' => "что делать\nс цветами"
		];
	}

	// генерация HTML логотипа.
	protected function logo(): string
	{
		return $this->logoHtml($this->shouldShowLogo());
	}

	// возвращает HTML логотипа.
	// может быть переопределен в дочерних классах.
	protected function logoHtml(bool $shouldShow): string
	{
		$src = "{$this->logosPath}/{$this->shopCrmCode}.png";
		return "<div id='logo' style='display:" . $this->display($shouldShow) . "'><img src='{$src}' class='logo'></div>";
	}

	// генерация HTML нашего заголовка.
	protected function ourTitle(): string
	{
		return $this->ourTitleHtml($this->shouldShowOurTitle());
	}

	// возвращает HTML нашего заголовка.
	// может быть переопределен в дочерних классах.
	protected function ourTitleHtml(bool $shouldShow): string
	{
		$title = htmlspecialchars($this->title);
		return "
		<div id='title_content' style='display:" . $this->display($shouldShow) . "'>
			<div id='pre_title'>этот букет называется</div>
			<div id='title' contenteditable='true'>{$title}</div>
		</div>
		";
	}

	// генерация HTML нашего текста.
	protected function ourText(): string
	{
		return $this->ourTextHtml($this->shouldShowOurText());
	}

	// возвращает HTML нашего текста.
	// может быть переопределен в дочерних классах.
	protected function ourTextHtml(bool $shouldShow): string
	{
		switch ($this->type) {
			case 'image':
				return "<img id ='our_text' src='{$this->ourContent}' style='width:100%; height:auto'>";
			case 'text':
				$text = nl2br(str_replace('*br*', "\n", $this->ourContent));
				return "<div id='our_text' contenteditable='true' style='display:" . $this->display($shouldShow) . "'>{$text}</div>";
			default:
				return '';
		}
	}

	// генерация HTML текста клиента.
	protected function customerText(): string
	{
		return $this->customerTextHtml($this->shouldShowCustomerText());
	}

	// возвращает HTML текста клиента.
	// может быть переопределен в дочерних классах.
	protected function customerTextHtml(bool $shouldShow): string
	{
		$text = nl2br($this->customerContent);
		return "<div id='customer_text' contenteditable='true' style='display:" . $this->display($shouldShow) . "'>{$text}</div>";
	}

	// генерация HTML qr-кодов.
	protected function qrCodes(): string
	{
		if (empty($this->qrCodes)) {
			return '';
		}
		return $this->qrCodesHtml($this->shouldShowQRCodes());
	}

	// возвращает HTML qr-кодов.
	// может быть переопределен в дочерних классах.
	protected function qrCodesHtml(bool $shouldShow): string
	{
		$html = "
			<table id='qr_codes' style='display:" . $this->display($shouldShow) . "'>
				<tr>";
		foreach ($this->qrCodes as $qrCode) {
			$caption = nl2br($qrCode['caption']);
			$html .= "
					<td>
						<div class='caption'>$caption</div>
					</td>";
		}
		$html .= "
				</tr>
				<tr>";
		foreach ($this->qrCodes as $qrCode) {
			$html .= "
					<td>
						<img src='{$qrCode['url']}' class='qr_code'></td>
					</td>";
		}
		$html .= "
				</tr>
			</table>";
		return $html;
	}

	private function display(bool $shouldShow): string
	{
		return $shouldShow ? 'block' : 'none';
	}

	// генерация HTML карточки.
	public function render(): string
	{
		$logData = json_encode($this->logger->getLogData(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		return "
		<!DOCTYPE html>
		<html lang='ru'>

		<head>
			<meta charset='UTF-8'>
			<meta name='viewport' content='width=device-width, initial-scale=1.0'>
			<title>Карточка букета</title>
			<link rel='stylesheet' href='main.css'>
		</head>

		<body>
			<div id='card' data-shop='{$this->shopCrmCode}' data-card-type='{$this->type}'>
				{$this->logo()}
				{$this->ourTitle()}
				{$this->ourText()}
				{$this->customerText()}
				{$this->qrCodes()}
			</div>
			<script src='main.js'></script>
			<script>console.log($logData);</script>
		</body>

		</html>
		<style>
			html, body {overflow: hidden;height: 100%;}
		</style>";
	}
}
