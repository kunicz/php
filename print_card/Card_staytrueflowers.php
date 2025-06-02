<?php

namespace print_card;

class Card_staytrueflowers extends Card
{
	protected function shouldShowQRCodes(): bool
	{
		return false;
	}
}
