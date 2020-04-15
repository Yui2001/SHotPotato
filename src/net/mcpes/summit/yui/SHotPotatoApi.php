<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 18:15
 */

namespace net\mcpes\summit\yui;


use net\mcpes\summit\yui\gameFunction\GameState;
use net\mcpes\summit\yui\gameManager\PlayerManager;
use net\mcpes\summit\yui\gameManager\RoomManager;
use net\mcpes\summit\yui\gameManager\RoomsData;
use pocketmine\Player;

class SHotPotatoApi
{
    public function getRoom(string $roomName):RoomManager
    {
        return SHotPotato::getDataManager()->getRoomData($roomName)["baseManager"];
    }

    public function getRoomByArray(array $room):RoomManager
    {
        return $room["baseManager"];
    }

    public function getNewRoom(string $roomName):RoomManager
    {
        return new RoomManager($roomName);
    }

    public function getRoomData(string $roomName):RoomsData
    {
        return SHotPotato::getDataManager()->getRoomData($roomName)["manager"];
    }

    public function getRoomDataByArray(array $room){
        return $room["manager"];
    }

    public function getNewRoomData(string $roomName):RoomsData
    {
        return new RoomsData($roomName);
    }

    public function getPlayerManager(Player $player):PlayerManager
    {
        return new PlayerManager($player);
    }

    public function getNewGameState(string $roomName):GameState
    {
        return new GameState($roomName);
    }

    public function getGameState(string $roomName):GameState
    {
        return SHotPotato::getDataManager()->getRoomData($roomName)["gameState"];
    }

    public function getGameStateByArray(array $room):GameState
    {
        return $room["gameState"];
    }
}