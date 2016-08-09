<?php

namespace Symla\Joomla\Tracy;

abstract class Debugger
{
    public static function enable()
    {
        restore_exception_handler();
        \Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT);
    }
}