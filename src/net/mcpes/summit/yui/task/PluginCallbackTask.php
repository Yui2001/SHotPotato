<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 21:31
 */
/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 *
 *
*/

namespace net\mcpes\summit\yui\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

/**
 * Allows the creation of simple callbacks with extra data
 * The last parameter in the callback will be this object
 *
 */
class PluginCallbackTask extends Task {
    /** @var callable */
    protected $callable;
    /** @var array */
    protected $args;
    /**
     * @param Plugin   $owner
     * @param callable $callable
     * @param array    $args
     */
    public function __construct(callable $callable, array $args = []){
        $this->callable = $callable;
        $this->args = $args;
        $this->args[] = $this;
    }
    /**
     * @return callable
     */
    public function getCallable(){
        return $this->callable;
    }
    public function onRun($currentTicks){
        $c = $this->callable;
        $args = $this->args;
        $args[] = $currentTicks;
        $c(...$args);
    }
}