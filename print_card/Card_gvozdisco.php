<?php

namespace print_card;

class Card_gvozdisco extends Card
{

	protected function logoHtml(bool $shouldShow): string
	{
		return "
			<div id='logo'>
				<img src='{$this->logosPath}/2steblya.png' class='logo'>
				<img src='{$this->logosPath}/gvozdisco.png' class='logo'>
			</div>";
	}

	protected function ourContentHtml(bool $shouldShow): string
	{
		return "
		<div id='our_text' contenteditable='true'>
			<b>Д</b>урачься<br>
			<b>И</b>скрись<br>
			<b>С</b>ияй<br>
			<b>К</b>очевряжься<br>
			<b>О</b>чаровывай<br>
		</div>
		";
	}

	protected function getQrCodes(): array
	{
		$qrCodes = [
			'2steblya' => [
				'url' => "{$this->qrCodesPath}/2steblya/main.gif",
				'caption' => "сайт\n2steblya"
			]
		];
		foreach ($this->getQrCodesCaptions() as $key => $caption) {
			$qrCodes[$key] = [
				'url' => "{$this->qrCodesPath}/{$this->shopCrmCode}/{$key}.gif",
				'caption' => $caption
			];
		}

		$this->logger->addRoot('qr_codes', $qrCodes);
		return $qrCodes;
	}
}
