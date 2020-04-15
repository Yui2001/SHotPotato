<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 18:59
 */

namespace net\mcpes\summit\yui\gameManager;


use net\mcpes\summit\yui\SHotPotato;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;

class RoomManager //房间基础数据
{
    private $roomName;

    function __construct(string $roomName)
    {
        $this->roomName = $roomName;
    }

    private function getRoom(){
        return SHotPotato::getDataManager()->getRoomBase($this->roomName);
    }

    public function add(array $room){
        SHotPotato::getDataManager()->setRoomBase($this->roomName,$room);
    }

    public function remove()
    {
        SHotPotato::getDataManager()->removeRoomBase($this->roomName);
    }

    public function addNewRoom(){
        SHotPotato::getDataManager()->setRoomBase($this->roomName,array(
            "min-player" => 3,
            "max-player" => 20,
            "wait-time" => 20,
            "stop-time" => 300,
            "prop-reset-time" => 30,
            "potato-boom-time" => 10,
            "sign" => array(
                "sign-x" => null,
                "sign-y" => null,
                "sign-z" => null,
                "sign-level" => null
            ),
            "wait" => array(
                "wait-x" => null,
                "wait-y" => null,
                "wait-z" => null,
                "wait-level" => null
            ),
            "game" => array(
                "game-x" => null,
                "game-y" => null,
                "game-z" => null,
                "game-level" => null
            ),
            "look" => array(
                "look-x" => null,
                "look-y" => null,
                "look-z" => null,
                "look-level" => null
            ),
            "over" => array(
                "over-x" => null,
                "over-y" => null,
                "over-z" => null,
                "over-level" => null
            ),
            "props" => array(),
            "winner-cmd" => array()
        ));
    }

    public function getPropResetTime():int
    {
        return $this->getRoom()["prop-reset-time"];
    }

    public function getProps():array
    {
        if(!isset($this->getRoom()["props"])){
            $data = $this->getRoom();
            $data["props"] = array();
            $this->save($data);
        }
        return $this->getRoom()["props"];
    }

    public function getWaitTime():int
    {
        return $this->getRoom()["wait-time"];
    }

    public function getStopTime():int
    {
        return $this->getRoom()["stop-time"];
    }

    public function getPotatoBoomTime():int
    {
        return $this->getRoom()["potato-boom-time"];
    }

    public function getPropsCount():int
    {
        $props = $this->getProps();
        if($props == null){
            return 0;
        }else {
            return count($props);
        }
    }

    public function setProps(array $props)
    {
        $data = $this->getRoom();
        $data["props"][$this->getPropsCount()+1] = $props;
        $this->save($data);
    }

    public function setPropResetTime(int $time)
    {
        $data = $this->getRoom();
        $data["prop-reset-time"] = $time;
        $this->save($data);
    }

    public function setWaitTime(int $time)
    {
        $data = $this->getRoom();
        $data["wait-time"] = $time;
        $this->save($data);
    }

    public function setStopTime(int $time)
    {
        $data = $this->getRoom();
        $data["stop-time"] = $time;
        $this->save($data);
    }

    public function setPotatoBoomTime(int $time)
    {
        $data = $this->getRoom();
        $data["potato-boom-time"] = $time;
        $this->save($data);
    }

    public function setMinPlayer(int $number)
    {
        $data = $this->getRoom();
        $data["min-player"] = $number;
        $this->save($data);
    }

    public function setMaxPlayer(int $number)
    {
        $data = $this->getRoom();
        $data["max-player"] = $number;
        $this->save($data);
    }

    public function getMinPlayer():int
    {
        return $this->getRoom()["min-player"];
    }

    public function getMaxPlayer():int
    {
        return $this->getRoom()["max-player"];
    }

    public function getSignLocation():Position
    {
        $x = $this->getRoom()["sign"]["sign-x"];
        $y = $this->getRoom()["sign"]["sign-y"];
        $z = $this->getRoom()["sign"]["sign-z"];
        return new Position($x,$y,$z,Server::getInstance()->getLevelByName($this->getSignLevel()));
    }

    public function getSignLevel():string
    {
        return $this->getRoom()["sign"]["sign-level"];
    }

    public function setSignLocation(Position $location)
    {
        $data = $this->getRoom();
        $data["sign"] = array(
            "sign-x" => $location->getFloorX(),
            "sign-y" => $location->getFloorY(),
            "sign-z" => $location->getFloorZ(),
            "sign-level" => $location->getLevel()->getFolderName()
        );
        $this->save($data);
        SHotPotato::getDataManager()->addSign($location->__toString(),$this->roomName);
    }

    public function setOverPlace(Position $location)
    {
        $data = $this->getRoom();
        $data["over"] = array(
            "over-x" => $location->getFloorX(),
            "over-y" => $location->getFloorY(),
            "over-z" => $location->getFloorZ(),
            "over-level" => $location->getLevel()->getFolderName()
        );
        $this->save($data);
    }

    public function getOverPlace():Vector3
    {
        $x = $this->getRoom()["over"]["over-x"];
        $y = $this->getRoom()["over"]["over-y"];
        $z = $this->getRoom()["over"]["over-z"];
        return new Vector3($x,$y,$z);
    }

    public function getOverLevel():Level
    {
        return Server::getInstance()->getLevelByName($this->getRoom()["over"]["over-level"]);
    }

    public function setLookPlace(Position $location)
    {
        $data = $this->getRoom();
        $data["look"] = array(
            "look-x" => $location->getFloorX(),
            "look-y" => $location->getFloorY(),
            "look-z" => $location->getFloorZ(),
            "look-level" => $location->getLevel()->getFolderName()
        );
        $this->save($data);
    }

    public function getLookPlace():Vector3
    {
        $x = $this->getRoom()["look"]["look-x"];
        $y = $this->getRoom()["look"]["look-y"];
        $z = $this->getRoom()["look"]["look-z"];
        return new Vector3($x,$y,$z);
    }

    public function getLookLevel():Level
    {
        return Server::getInstance()->getLevelByName($this->getRoom()["look"]["look-level"]);
    }

    public function setGamePlace(Position $location)
    {
        $data = $this->getRoom();
        $data["game"] = array(
            "game-x" => $location->getFloorX(),
            "game-y" => $location->getFloorY(),
            "game-z" => $location->getFloorZ(),
            "game-level" => $location->getLevel()->getFolderName()
        );
        $this->save($data);
    }

    public function getGamePlace():Vector3
    {
        $x = $this->getRoom()["game"]["game-x"];
        $y = $this->getRoom()["game"]["game-y"];
        $z = $this->getRoom()["game"]["game-z"];
        return new Vector3($x,$y,$z);
    }

    public function getGameLevel():Level
    {
        return Server::getInstance()->getLevelByName($this->getRoom()["game"]["game-level"]);
    }

    public function setWaitPlace(Position $location)
    {
        $data = $this->getRoom();
        $data["wait"] = array(
            "wait-x" => $location->getFloorX(),
            "wait-y" => $location->getFloorY(),
            "wait-z" => $location->getFloorZ(),
            "wait-level" => $location->getLevel()->getFolderName()
        );
        $this->save($data);
    }

    public function getWaitPlace():Vector3
    {
        $x = $this->getRoom()["wait"]["wait-x"];
        $y = $this->getRoom()["wait"]["wait-y"];
        $z = $this->getRoom()["wait"]["wait-z"];
        return new Vector3($x,$y,$z);
    }

    public function getWaitLevel():Level
    {
        return Server::getInstance()->getLevelByName($this->getRoom()["wait"]["wait-level"]);
    }

    public function getWinnerCmd():array
    {
        return $this->getRoom()["winner-cmd"];
    }

    public function save(array $data)
    {
        SHotPotato::getDataManager()->setRoomBase($this->roomName,$data);
    }

    public function saveAll()
    {
        $config = new Config(SHotPotato::$dataFolder."/rooms/".$this->roomName.".yml",Config::YAML);
        $config->setAll($this->getRoom());
        $config->save();
    }
}