<?php


namespace net\mcpes\summit\yui\entity;


use pocketmine\entity\Animal;
use pocketmine\math\Vector3;

class Lightning extends Animal
{
    const NETWORK_ID = 93;

    public $width = 0.3;
    public $length = 0.9;
    public $height = 1.8;

    /**
     * @return string
     */
    public function getName() : string{
        return "Lightning";
    }

    public function initEntity():void
    {
        parent::initEntity();
        $this->setMaxHealth(2);
        $this->setHealth(2);
    }
}