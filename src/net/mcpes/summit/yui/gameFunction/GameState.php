<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 21:28
 */

namespace net\mcpes\summit\yui\gameFunction;


use net\mcpes\summit\yui\gameManager\RoomManager;
use net\mcpes\summit\yui\gameManager\RoomsData;
use net\mcpes\summit\yui\SHotPotato;
use net\mcpes\summit\yui\task\PotatoBoomTask;
use net\mcpes\summit\yui\task\PropResetTask;
use net\mcpes\summit\yui\task\StopTask;
use net\mcpes\summit\yui\task\WaitTask;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Sign;

class GameState
{
    private $api;
    private $roomBase;
    private $roomData;
    private $roomName;
    private $dataManager;

    public function __construct(string $roomName)
    {
        $this->roomName = $roomName;
        $this->api = SHotPotato::getApi();
        $this->dataManager = SHotPotato::getDataManager();
        $this->roomBase = $this->api->getRoom($roomName);
        $this->roomData = $this->api->getRoomData($roomName);
    }

    public function onJoin(string $playerName)
    {
        $player = Server::getInstance()->getPlayerExact($playerName);
        if($this->getRoomData()->isStart()){
            $player->sendMessage(SHotPotato::$DEFAULT_TITLE."§4游戏已开始，请静待下一轮游戏。");
            return;
        }
        if($this->roomBase->getWaitLevel() == null){
            $player->sendMessage(SHotPotato::$DEFAULT_TITLE."§4等待地点世界未加载，请联系服主");
            return;
        }
        if($player->isCreative()){
            $player->sendMessage(SHotPotato::$DEFAULT_TITLE."§4请用生存模式进入游戏");
            return;
        }
        if($player->getAllowFlight()){
            $player->sendMessage(SHotPotato::$DEFAULT_TITLE."§4请先取消飞行模式再进入游戏");
            return;
        }
        if($this->dataManager->isInGame($player->getName())){
            $player->sendMessage(SHotPotato::$DEFAULT_TITLE."§4你已经加入游戏了！");
            return;
        }
        if($player->getInventory()->getHeldItemIndex() == 8){
            $player->getInventory()->setHeldItemIndex(0);
        }
        $player->teleport($this->roomBase->getWaitLevel()->getSafeSpawn());
        $player->teleport($this->roomBase->getWaitPlace());
        $this->roomData->addPlayer($player);
        $this->dataManager->addGamePlayer($playerName,$this->roomName);
        $playerManager = $this->roomData->getPlayerManager($playerName);
        $playerManager->saveEffects();
        $player->removeAllEffects();
        $playerManager->saveScale();
        $player->setScale(1);
        $playerManager->saveItems();
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(8,Item::get(324)->setCustomName("§l§e双击离开游戏房间"));
        $this->updateSign();
        $this->addFloatingText($player);
        $player->addTitle(SHotPotato::$DEFAULT_TITLE,"§e欢迎加入游戏", 20, 30, 20);
        $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."玩家".$playerName."加入了游戏");
        $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§6游戏当前人数:".$this->roomData->getPlayersCount());
        if($this->roomData->getPlayersCount() == $this->roomBase->getMinPlayer()) {
            if ($this->roomData->getMode() !== 1) {
                if($this->roomBase->getGameLevel() !== null) {
                    $this->roomData->setMode(1);
                    $this->updateSign();
                    $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE . "§6已经达到标准人数啦！游戏即将开始");
                    SHotPotato::getInstance()->getScheduler()->scheduleRepeatingTask(new WaitTask($this), 20);
                    return;
                }else{
                    $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE . "§4游戏世界未加载，请联系服主");
                }
            }
        }
        if($this->roomData->getPlayersCount() >= $this->roomBase->getMaxPlayer()){
            $this->roomData->setMode(2);
            $this->updateSign();
        }
    }

    public function onDeath(string $playerName)
    {
        if($this->roomData->isAlive($playerName)) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            $this->roomData->removeAlivePlayer($playerName);
            $player->teleport($this->roomBase->getLookLevel()->getSafeSpawn());
            $player->teleport($this->roomBase->getLookPlace());
            $player->addTitle(SHotPotato::$DEFAULT_TITLE, "§e你已进入观战模式", 20, 30, 20);
            $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE . "玩家" . $playerName . "死亡");
            $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE . "游戏剩余玩家：" . $this->roomData->getAlivePlayersCount() . "人");
        }
    }

    public function onQuit(string $playerName)
    {
        $player = Server::getInstance()->getPlayerExact($playerName);
        $playerManager = $this->roomData->getPlayerManager($playerName);
        $playerManager->setHelmet($player,Item::get(0));
        $playerManager->giveEffects();
        $playerManager->setScale();
        $player->teleport($this->roomBase->getOverLevel()->getSafeSpawn());
        $player->teleport($this->roomBase->getOverPlace());
        $this->roomData->removePlayer($playerName);
        if($this->getRoomData()->isStart()) {
            if ($playerManager->getGame()->hasPotatoPlayer()) {
                if ($playerManager->isPotatoPlayer()) {
                    $this->randomPotatoPlayer();
                    PotatoBoomTask::setBoomTimeNow($this->getRoomBase()->getPotatoBoomTime());
                }
            }
        }
        $this->dataManager->removeGamePlayer($playerName);
        $player->getInventory()->clearAll();
        $playerManager->giveItems();
        $this->updateSign();
        $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."玩家".$playerName."退出了游戏");
        if($this->roomData->getMode() == 2){
            if($this->roomData->getPlayersCount() == 0){
                Server::getInstance()->getLogger()->info(SHotPotato::$DEFAULT_TITLE."本局游戏无一生存玩家，重设游戏中....");
                $this->resetGame();
            }
        }
    }

    public function kickAllPlayer()
    {
        $players = array_keys($this->roomData->getPlayers());
        foreach ($players as $playerName)
        {
            $this->onQuit($playerName);
        }
    }

    public function resetGame()
    {
        $this->roomData->reset();
        $this->updateSign();
    }

    public function gameStart()
    {
        $this->roomData->setMode(2);
        $this->roomData->setStart();
        $this->updateSign();
        $players = array_keys($this->roomData->getPlayers());
        foreach ($players as $playerName)
        {
            $player = Server::getInstance()->getPlayerExact($playerName);
            //$player->getInventory()->clearAll();
            $player->teleport($this->roomBase->getGameLevel()->getSafeSpawn());
            $player->teleport($this->roomBase->getGamePlace());
        }
        $this->addSoundToAll(2);
        $this->sendMessageToAll("§6游戏开始咯!");
        SHotPotato::getInstance()->getScheduler()->scheduleRepeatingTask(new PropResetTask($this),20);
        SHotPotato::getInstance()->getScheduler()->scheduleRepeatingTask(new PotatoBoomTask($this),20);
        SHotPotato::getInstance()->getScheduler()->scheduleRepeatingTask(new StopTask($this),20);
    }

    public function suspendGame()
    {
        $players = array_keys($this->roomData->getPlayers());
        foreach ($players as $playerName)
        {
            $player = Server::getInstance()->getPlayerExact($playerName);
            $player->teleport($this->roomBase->getWaitLevel()->getSafeSpawn());
            $player->teleport($this->roomBase->getWaitPlace());
            $item = Item::get(324);
            $player->getInventory()->setItem(8,$item->setCustomName("§l§e双击离开游戏房间"));
        }
        $this->roomData->setMode(0);
        $this->updateSign();
    }

    public function gameStop()
    {
        Server::getInstance()->getLogger()->info(SHotPotato::$DEFAULT_TITLE."§6房间".$this->roomName."游戏结束,正在重置.");
        $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§6游戏已经结束啦！");
        $this->roomData->setStop();
        $this->kickAllPlayer();
        $this->resetGame();
    }

    public function setPotatoPlayerBoom()
    {
        $player = $this->roomData->getPotatoPlayer();
        $location = $player->getLocation();
        $player->getLevel()->addParticle(new ExplodeParticle($location));
        $player->getLevel()->addParticle(new HugeExplodeParticle($location));
        $player->getLevel()->addParticle(new HugeExplodeSeedParticle($location));
        $player->getLevel()->broadcastLevelSoundEvent($location,LevelSoundEventPacket::SOUND_EXPLODE);
        $this->onDeath($player->getName());
        $player->getInventory()->clearAll();
        $item = Item::get(324);
        $player->getInventory()->setItem(8,$item->setCustomName("§l§e双击离开游戏房间"));
        SHotPotato::callLightning($player);
    }

    public function addSoundToAll(int $type,bool $alive = false)
    {
        if($alive){
            $players = $this->roomData->getAlivePlayers();
        }else {
            $players = array_keys($this->roomData->getPlayers());
        }
        foreach ($players as $playerName){

            $player = Server::getInstance()->getPlayerExact($playerName);
            switch ($type){
                case 1:
                    $player->getLevel()->addSound(new ClickSound($player->getLocation()),array($player));
                    break;
                case 2:
                    $player->getLevel()->addSound(new AnvilUseSound($player->getLocation()),array($player));
                    break;
            }
        }
    }

    public function sendTitleToAll(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1,bool $alive = false)
    {
        if($alive){
            $players = $this->roomData->getAlivePlayers();
        }else {
            $players = array_keys($this->roomData->getPlayers());
        }
        foreach ($players as $playerName){
            $player = Server::getInstance()->getPlayerExact($playerName);
            $player->addTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
        }
    }

    public function sendMessageToAll(string $message,bool $alive = false)
    {
        if($alive){
            $players = $this->roomData->getAlivePlayers();
        }else {
            $players = array_keys($this->roomData->getPlayers());
        }
        foreach ($players as $playerName){
            $player = Server::getInstance()->getPlayerExact($playerName);
            $player->sendMessage($message);
        }
    }

    public function sendTipToAll(string $message,bool $alive = false)
    {
        if($alive){
            $players = $this->roomData->getAlivePlayers();
        }else {
            $players = array_keys($this->roomData->getPlayers());
        }
        foreach ($players as $playerName){
            $player = Server::getInstance()->getPlayerExact($playerName);
            $player->sendTip($message);
        }
    }

    public function updateSign()
    {
        $location = $this->roomBase->getSignLocation();
        $sign = Server::getInstance()->getLevelByName($this->roomBase->getSignLevel())->getTile($location);
        $playersNumber = $this->roomData->getPlayersCount();
        $maxPlayerNumber = $this->roomBase->getMaxPlayer();
        $mode = $this->roomData->getMode();
        if ($sign instanceof Sign) {
            switch ($mode) {
                case 0:
                    $sign->setText(SHotPotato::$DEFAULT_TITLE, "§6" . $playersNumber . "§l§d/§l§4" . $maxPlayerNumber, "§b点击进入游戏");
                    break;
                case 1:
                    $sign->setText(SHotPotato::$DEFAULT_TITLE, "§l§4游戏即将开始",  "§l§4有" . $playersNumber . "个玩家正在游戏中");
                    break;
                case 2:
                    $sign->setText(SHotPotato::$DEFAULT_TITLE, "§l§4有" . $playersNumber . "个玩家正在游戏中","§l§6请喝一杯咖啡","§l§b静待下一轮游戏");
                    break;
                case 3:
                    $sign->setText(SHotPotato::$DEFAULT_TITLE, "§l§4游戏结束",  "§l§4正在重置房间");
                    break;
                default:
                    $sign->setText(SHotPotato::$DEFAULT_TITLE, "§4未知游戏状态");
            }
        }
    }

    public function randomPotatoPlayer()
    {
        $alivePlayers = $this->getRoomData()->getAlivePlayers();
        $randomPlayer = array_rand($alivePlayers,1);
        $player = Server::getInstance()->getPlayerExact($alivePlayers[$randomPlayer]);
        $this->api->getPlayerManager($player)->setPotato();
        $this->sendMessageToAll(SHotPotato::$DEFAULT_TITLE."§6烫手的山芋落在了玩家§4".$player->getName()."§e的手上");
    }

    public function addFloatingText(Player $player){
        $pos = $this->roomBase->getWaitPlace();
        $v3 = new Vector3($pos->getX(),$pos->getY()+3.9,$pos->getZ());
        $text = SHotPotato::$DEFAULT_TITLE." \n
        §b玩法简介：游戏开始时，有随机一个玩家得到烫手的山芋 \n
        §6山芋玩家一定在规定时间内必须点击其他玩家，将山芋丢出 \n
        §d时间过后手持山芋的玩家将会BOOM \n
        §f当然普通玩家也必须要避开山芋玩家哦！";
        $particle = new FloatingTextParticle($v3,"",$text);
        $player->getLevel()->addParticle($particle,array($player));
    }

    public function outputCmd(string $name)
    {
        $cmds = $this->roomBase->getWinnerCmd();
        foreach ($cmds as $cmd){
            if(strstr($cmd,"@winner")){
                $cmd = str_replace("@winner",$name,$cmd);
            }
            //Server::getInstance()->getLogger()->info($cmd);
            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(),$cmd);
        }
    }

    public function getRoomName():string
    {
        return $this->roomName;
    }

    /**
     * @return RoomsData
     */
    public function getRoomData(): RoomsData
    {
        return $this->roomData;
    }

    /**
     * @return RoomManager
     */
    public function getRoomBase(): RoomManager
    {
        return $this->roomBase;
    }
}