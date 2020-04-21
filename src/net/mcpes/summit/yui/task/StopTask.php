<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/9
 * Time: 19:11
 */

namespace net\mcpes\summit\yui\task;


use net\mcpes\summit\yui\gameFunction\GameState;
use net\mcpes\summit\yui\SHotPotato;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class StopTask extends Task
{
    private $state;
    private $stop;

    public function __construct(GameState $state){
        $this->state = $state;
        $this->stop = SHotPotato::getApi()->getRoom($state->getRoomName())->getStopTime();
    }

    public function onRun($currentTick)
    {
        if($this->state->getRoomData()->getAlivePlayersCount() < 1){
            Server::getInstance()->getLogger()->info("房间".$this->state->getRoomName()."无一生存玩家，已强制停止游戏");
            $this->state->gameStop();
            $this->getHandler()->cancel();
            return;
        }
        if($this->state->getRoomData()->getPlayersCount() <= 0){
            Server::getInstance()->getLogger()->info("房间".$this->state->getRoomName()."无一玩家，已强制停止游戏");
            $this->state->gameStop();
            $this->getHandler()->cancel();
            return;
        }
        if($this->state->getRoomData()->getAlivePlayersCount() == 1){
            if($this->state->getRoomData()->getPlayersCount() == 1){
                $this->state->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§l§3本场游戏人数不足，已停止!",true);
                $this->state->gameStop();
                $this->getHandler()->cancel();
                return;
            }
            $winner = array_values($this->state->getRoomData()->getAlivePlayers());
            $this->state->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§l§3恭喜你成功成为本场游戏最终赢家!",true);
            $this->state->sendTitleToAll(SHotPotato::$DEFAULT_TITLE,"§l§3恭喜玩家".$winner[0]."成为本场游戏最终赢家!",10, 40, 10);
            Server::getInstance()->getLogger()->info(SHotPotato::$DEFAULT_TITLE."恭喜玩家".$winner[0]."成为本场游戏最终赢家!");
            $this->state->gameStop();
            $this->state->outputCmd($winner[0]);
            $this->getHandler()->cancel();
            return;
        }
        switch ($this->stop){
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
                $this->state->sendTipToAll(SHotPotato::$DEFAULT_TITLE."游戏即将结束，还有".$this->stop);
                $this->state->addSoundToAll(1);
                break;
            case 0:
                $this->state->sendTitleToAll(SHotPotato::$DEFAULT_TITLE,"§l§e游戏结束，没有人获得最终胜利！",0, 20, 0);
                $this->state->addSoundToAll(2);
                break;
        }
        if ($this->stop < 0) {
            $this->state->gameStop();
            $this->getHandler()->cancel();
            return;
        }
        $this->stop--;
    }
}