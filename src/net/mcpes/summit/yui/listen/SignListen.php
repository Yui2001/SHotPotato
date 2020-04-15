<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 21:30
 */

namespace net\mcpes\summit\yui\listen;


use net\mcpes\summit\yui\SHotPotato;
use pocketmine\block\SignPost;
use pocketmine\block\WallSign;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;

class SignListen implements Listener
{
    private $inBreak = [];

    public function onSignChange(SignChangeEvent $event)
    {
        $player = $event->getPlayer();
        if ($event->getLine(0) == "HotPotato")
        {
            if (!$player->isOp())
            {
                $player->sendMessage("你没有权限创建游戏牌!");
                return;
            }
            if($event->getLine(1) !== null)
            {
                $roomName = $event->getLine(1);
                if(SHotPotato::getDataManager()->hasRoomBase($roomName))
                {
                    $api = SHotPotato::getApi();
                    $room = $api->getRoom($roomName);
                    $block = $event->getBlock();
                    $location = new Position($block->getX(),$block->getY(),$block->getZ(),$player->getLevel());
                    $player->sendMessage($location->getLevel()->getName());
                    $room->setSignLocation($location);
                    $room->saveAll();
                    $event->setCancelled();
                    $api->getGameState($roomName)->resetGame();
                    $player->sendMessage("成功设置房间".$roomName."的入口牌！");
                }
            }
        }
    }

    public function onTouch(PlayerInteractEvent $event)
    {
        $block = $event->getBlock();
        if(in_array($event->getPlayer()->getName(),$this->inBreak)){
            $playerName = $event->getPlayer()->getName();
            unset($this->inBreak[array_search($playerName,$this->inBreak)]);
            $event->getPlayer()->sendMessage(SHotPotato::$DEFAULT_TITLE . "§6成功取消操作！");
            return;
        }
        if($block instanceof SignPost || $block instanceof WallSign){
            $dataManager = SHotPotato::getDataManager();
            $location = (new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()))->__toString();
            if($dataManager->isGameSign($location)){
                $roomName = $dataManager->getRoomNameBySign($location);
                $event->setCancelled();
                $state = SHotPotato::getApi()->getGameState($roomName);
                $state->onJoin($event->getPlayer()->getName());
            }
        }
    }

    public function onSignBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        if ($block instanceof SignPost || $block instanceof WallSign) {
            $dataManager = SHotPotato::getDataManager();
            $location = (new Position($block->getX(), $block->getY(), $block->getZ(), $block->getLevel()))->__toString();
            if ($dataManager->isGameSign($location)) {
                $player = $event->getPlayer();
                if($player->isOp()) {
                    if(in_array($player->getName(),$this->inBreak)){
                        unset($this->inBreak[array_search($player->getName(),$this->inBreak)]);
                        $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "§6成功破坏游戏牌！");
                    }else {
                        array_push($this->inBreak, $player->getName());
                        $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "§6你确定要破坏游戏牌吗?");
                        $player->sendMessage("§5确定请再次破坏");
                        $player->sendMessage("§b失误则点击任意方块取消操作。");
                        $event->setCancelled();
                    }
                }else{
                    $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "§4你没有权限破坏游戏牌！");
                }
            }
        }
    }
}