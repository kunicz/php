<?php

namespace print_card;

class Card_2steblya_white extends Card
{
	protected function getQrCodesCaptions(): array
	{
		return [
			'main' => "сайт\n2steblya",
			'telegram_channel' => "телеграм\nканал",
			'care' => "что делать\nс цветами"
		];
	}
}
