<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/8
 * Time: 10:13
 */

namespace net\mcpes\summit\yui\gameManager;


use net\mcpes\summit\yui\gameFunction\GameState;
use net\mcpes\summit\yui\SHotPotato;
use pocketmine\Player;

class RoomsData //房间游戏数据
{
    private $roomName;

    function __construct(string $roomName)
    {
        $this->roomName = $roomName;
    }

    private function getRoom():array
    {
        return SHotPotato::getDataManager()->getRoomData($this->roomName);
    }

    public function add()
    {
        SHotPotato::getDataManager()->setRoomData($this->roomName,array(
            "mode" => 0,
            "start" => false,
            "potato" => null,
            "manager" => $this,
            "players" => array(),
            "alive-players" => array(),
            "baseManager" => SHotPotato::getApi()->getNewRoom($this->roomName),
            "gameState" => null
        ));
        SHotPotato::getDataManager()->setState($this->roomName,new GameState($this->roomName));
    }

    public function reset()
    {
        $data = $this->getRoom();
        $data["mode"] = 0;
        $data["start"] = false;
        $data["potato"] = null;
        $data["players"] = array();
        $data["alive-players"] = array();
        $this->save($data);
    }

    public function setMode(int $mode)
    {
        $data = $this->getRoom();
        $data["mode"] = $mode;
        $this->save($data);
    }

    public function getMode():int
    {
        return $this->getRoom()["mode"];
    }

    public function getBaseManager():RoomManager
    {
        return $this->getRoom()["baseManager"];
    }

    public function remove(){
        SHotPotato::getDataManager()->removeRoomData($this->roomName);
    }

    public function addPlayer(Player $player){
        $data = $this->getRoom();
        $data["players"][$player->getName()] = new PlayerManager($player);
        $this->save($data);
        $this->addAlivePlayers($player->getName());
    }

    public function addAlivePlayers(string $playerName)
    {
        $data = $this->getRoom();
        array_push($data["alive-players"],$playerName);
        $this->save($data);
    }

    public function setStart()
    {
        $data = $this->getRoom();
        $data["start"] = true;
        $this->save($data);
    }

    public function setStop()
    {
        $this->setMode(3);
        $data = $this->getRoom();
        $data["start"] = false;
        $this->save($data);
    }

    public function setPotatoPlayer(Player $player)
    {
        $data = $this->getRoom();
        $data["potato"] = $player;
        $this->save($data);
    }

    public function removePlayer(string $playerName)
    {
        $data = $this->getRoom();
        unset($data["players"][$playerName]);
        $this->save($data);
        $this->removeAlivePlayer($playerName);
    }

    public function removeAlivePlayer(string $playerName)
    {
        $data = $this->getRoom();
        unset($data["alive-players"][array_search($playerName,$data["alive-players"])]);
        $this->save($data);
    }

    public function getPotatoPlayer():Player
    {
        return $this->getRoom()["potato"];
    }

    public function getPlayerManager(string $playerName):PlayerManager
    {
        return $this->getPlayers()[$playerName];
    }

    public function getPlayersCount():int
    {
        return count($this->getPlayers());
    }

    public function getAlivePlayersCount():int
    {
        return count($this->getAlivePlayers());
    }

    public function getPlayers():array
    {
        return $this->getRoom()["players"];
    }

    public function getAlivePlayers():array
    {
        return $this->getRoom()["alive-players"];
    }

    public function hasPotatoPlayer():bool
    {
        return isset($this->getRoom()["potato"]);
    }

    public function isStart():bool
    {
        return $this->getRoom()["start"];
    }

    public function isStop():bool
    {
        return !$this->isStart();
    }

    public function isAlive(string $playerName):bool
    {
        return in_array($playerName,$this->getAlivePlayers());
    }

    public function save(array $data){
        SHotPotato::getDataManager()->setRoomData($this->roomName,$data);
    }
}