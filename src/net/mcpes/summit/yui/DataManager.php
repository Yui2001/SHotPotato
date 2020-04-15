<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/8
 * Time: 19:24
 */

namespace net\mcpes\summit\yui;

use net\mcpes\summit\yui\gameFunction\GameState;

class DataManager
{
    private $signs = array();
    private $roomsData = array();
    private $roomsBase = array();
    private $gamePlayers = array();

    public function getRoomsBase():array
    {
        return $this->roomsBase;
    }

    public function getRoomsData():array
    {
        return $this->roomsData;
    }

    public function getGamePlayers():array
    {
        return $this->gamePlayers;
    }

    public function addSign(string $location,string $roomName)
    {
        array_push($this->signs,$location);
        $this->signs[$location] = $roomName;
    }

    public function setState(string $roomName,GameState $state)
    {
        $this->roomsData[$roomName]["gameState"] = $state;
    }

    public function removeSign(string $location)
    {
        unset($this->signs[$location]);
    }

    public function getRoomNameBySign(string $location):string
    {
        return $this->signs[$location];
    }

    public function addGamePlayer(string $playerName,string $roomName)
    {
        $this->gamePlayers[$playerName] = $roomName;
    }

    public function removeGamePlayer(string $playerName)
    {
        unset($this->gamePlayers[$playerName]);
    }

    public function getPlayerRoomName(string $playerName):string
    {
        return $this->gamePlayers[$playerName];
    }

    public function setRoomData(string $roomName,array $data)
    {
        $this->roomsData[$roomName] = $data;
    }

    public function removeRoomData(string $roomName)
    {
        unset($this->roomsData[$roomName]);
    }

    public function getRoomData(string $roomName):array
    {
        return $this->roomsData[$roomName];
    }

    public function hasRoomData(string $roomName):bool
    {
        return array_key_exists($roomName,$this->getRoomsData());
    }

    public function setRoomBase(string $roomName,array $data)
    {
        $this->roomsBase[$roomName] = $data;
    }

    public function removeRoomBase(string $roomName)
    {
        unset($this->roomsBase[$roomName]);
    }

    public function getRoomBase(string $roomName):array
    {
        return $this->roomsBase[$roomName];
    }

    public function hasRoomBase(string $roomName):bool
    {
        return array_key_exists($roomName,$this->getRoomsBase());
    }

    public function isInGame(string $playerName):bool
    {
        return array_key_exists($playerName,$this->gamePlayers);
    }

    public function isGameSign(string $location){
        return array_key_exists($location,$this->signs);
    }

}