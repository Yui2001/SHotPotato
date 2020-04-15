<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 21:27
 */

namespace net\mcpes\summit\yui\gameManager;


use net\mcpes\summit\yui\SHotPotato;
use pocketmine\item\Item;
use pocketmine\item\LeatherCap;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\Player;
use pocketmine\utils\Color;

class PlayerManager
{
    private $items;
    private $scale;
    private $player;
    private $effects;
    private $playerName;
    private $equipment;

    function __construct(Player $player)
    {
        $this->player = $player;
        $this->playerName = $player->getName();
    }

    public function setPotato()
    {
        $helmet = Item::get(298);
        $item = $this->player->getItemInHand();
        $this->player->getInventory()->setItemInHand(Item::get(393));
        if($item->getId() != 0){
            $this->player->getInventory()->addItem($item);
        }
        if($helmet instanceof LeatherCap){
            $helmet->setCustomColor(new Color(0,255,0));
        }
        if($this->getGame()->hasPotatoPlayer()) {
            $this->getGame()->getPotatoPlayer()->getInventory()->removeItem(Item::get(393));
            $this->setHelmet($this->getGame()->getPotatoPlayer(),Item::get(0));
        }
        $this->getGame()->setPotatoPlayer($this->player);
        $this->player->getInventory()->setItemInHand(Item::get(393));
        $this->setHelmet($this->player,$helmet);
        SHotPotato::callLightning($this->player);
    }

    public function saveEffects(){
        $this->effects = $this->player->getEffects();
    }

    public function giveEffects(){
        foreach ($this->effects as $effect){
            $this->player->addEffect($effect);
        }
    }

    public function saveScale(){
        $this->scale = $this->player->getScale();
    }

    public function setScale(){
        $this->player->setScale($this->scale);
    }

    public function saveItems(){
        $this->items = $this->player->getInventory()->getContents();
        $this->equipment = $this->player->getInventory()->getArmorContents();
    }

    public function giveItems()
    {
        $this->player->getInventory()->setContents($this->items);
        $this->player->getInventory()->setArmorContents($this->equipment);
    }

    public function getPlayer():Player
    {
        return $this->player;
    }

    public function getRoomName():string
    {
        return SHotPotato::getDataManager()->getPlayerRoomName($this->playerName);
    }

    public function getGame():RoomsData
    {
        return SHotPotato::getApi()->getRoomData($this->getRoomName());
    }

    public function isAlive():bool
    {
        return in_array($this->playerName,$this->getGame()->getAlivePlayers());
    }

    public function isPotatoPlayer():bool
    {
        return ($this->getGame()->getPotatoPlayer()->getName() == $this->player->getName());
    }

    public function setHelmet(Player $player,Item $item)
    {
        $pk = new MobArmorEquipmentPacket();
        $pk->eid = $player->getId();
        $pk->slots = [
            $item,
            Item::get(0,0),
            Item::get(0,0),
            Item::get(0,0)
        ];
        $pk->encode();
        foreach($player->getLevel()->getPlayers() as $players){
            $players->dataPacket($pk);
        }
    }
}