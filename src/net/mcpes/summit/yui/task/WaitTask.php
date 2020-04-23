<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/9
 * Time: 18:58
 */

namespace net\mcpes\summit\yui\task;


use net\mcpes\summit\yui\gameFunction\GameState;
use net\mcpes\summit\yui\SHotPotato;
use pocketmine\scheduler\Task;

class WaitTask extends Task
{
    private $state;
    private $wait;

    public function __construct(GameState $state){
        $this->state = $state;
        $this->wait = SHotPotato::getApi()->getRoom($state->getRoomName())->getWaitTime();
    }

    public function onRun($currentTick)
    {
        $playerCount = $this->state->getRoomData()->getPlayersCount();
        if($playerCount < 2){
            $this->getHandler()->cancel();
            $this->state->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§c游戏人数不足，暂停开始游戏");
            $this->state->suspendGame();
            return;
        }
        if($playerCount < $this->state->getRoomBase()->getMinPlayer()){
            $this->getHandler()->cancel();
            $this->state->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§c游戏人数不足，暂停开始游戏");
            $this->state->suspendGame();
            return;
        }
        if($playerCount >= $this->state->getRoomBase()->getMaxPlayer() && $this->wait > 5){
            $this->state->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§6游戏已达到最大人数，即将开始游戏");
            $this->wait = 5;
            return;
        }
        switch ($this->wait){
            default:
                $this->state->sendTipToAll(SHotPotato::$DEFAULT_TITLE."§6距离游戏开始还有 §4".$this->wait."§e 秒");
                break;
            case 10:
            case 9:
            case 8:
            case 7:
            case 6:
            case 5:
            case 4:
            case 3:
            case 2:
            case 1:
                $this->state->sendTitleToAll(SHotPotato::$DEFAULT_TITLE,"§l§3".$this->wait,0, 20, 0);
                $this->state->addSoundToAll(1);
                break;
            case 0:
                $this->state->sendTitleToAll(SHotPotato::$DEFAULT_TITLE,"§l§e游戏开始，Go！",0, 20, 0);
                if($this->state->getRoomData()->isStop()) {
                    $this->state->gameStart();
                    $this->getHandler()->cancel();
                }
                break;
        }
        $this->wait--;
    }
}