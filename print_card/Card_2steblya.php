<?php

namespace print_card;

class Card_2steblya extends Card
{
	protected function getQrCodesCaptions(): array
	{
		return [
			'main' => "сайт\n{$this->shopCrmCode}",
			'telegram_channel' => "телеграмк\nанал",
			'care' => "шо делать\nс цветами"
		];
	}
}
