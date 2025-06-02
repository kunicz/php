<?php

namespace php2steblya;

// абстрактный класс-обертка для работы со скриптами
// подкючает в себя статические методы (ScriptStaticTrait) и инстансную функциональность (ScriptInstanceTrait)
abstract class Script
{
	use ScriptStaticTrait;
	use ScriptInstanceTrait;
}
