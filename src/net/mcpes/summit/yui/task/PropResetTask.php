<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/11
 * Time: 16:04
 */

namespace net\mcpes\summit\yui\task;


use net\mcpes\summit\yui\gameFunction\GameState;
use net\mcpes\summit\yui\SHotPotato;
use pocketmine\item\Item;
use pocketmine\item\LeatherTunic;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\utils\Color;

class PropResetTask extends Task
{
    private $state;
    private $refresh;
    private $props = array();

    public function __construct(GameState $state){
        $this->state = $state;
        $this->refresh = SHotPotato::getApi()->getRoom($state->getRoomName())->getPropResetTime();
    }

    public function onRun($currentTick)
    {
        if($this->state->getRoomData()->isStop()){
            $props = $this->props;
            foreach ($props as $prop) {
                $level = $this->state->getRoomBase()->getGameLevel();
                $drop = $level->getEntity($prop);
                if($drop != null){
                    $drop->close();
                }
            }
            $this->getHandler()->cancel();
        }
        $this->refresh--;
        if($this->refresh <= 0){
            $props = $this->state->getRoomBase()->getProps();
            foreach ($props as $prop){
                $level = $this->state->getRoomBase()->getGameLevel();
                $rand = $prop["rand"];
                $rand_number = mt_rand(1,100);
                if($rand >= $rand_number) {
                    $item = Item::get($prop["item"]);
                    if($item->getId() == 299){
                        if($item instanceof LeatherTunic){
                            $item->setCustomColor(new Color(0,255,0));
                        }
                    }
                    $drop = $level->dropItem(new Vector3($prop["prop-x"], $prop["prop-y"], $prop["prop-z"]), $item);
                    array_push($this->props, $drop->getId());
                }
            }
            $this->refresh = $this->getRefreshTime();
        }
    }

    public function getRefreshTime():int
    {
        return SHotPotato::getApi()->getRoom($this->state->getRoomName())->getPropResetTime();
    }
}