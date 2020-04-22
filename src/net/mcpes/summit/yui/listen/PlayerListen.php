<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 21:29
 */

namespace net\mcpes\summit\yui\listen;

use net\mcpes\summit\yui\SHotPotato;
use net\mcpes\summit\yui\task\PotatoBoomTask;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\item\Item;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\EnchantmentTableParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use pocketmine\Server;

class PlayerListen implements Listener
{
    private $api;
    private $configBase;
    private $dataManager;
    private $touchPlayers = array();

    public function __construct()
    {
        $this->api = SHotPotato::getApi();
        $this->dataManager = SHotPotato::getDataManager();
        $this->configBase = SHotPotato::getConfigBase();
    }

    public function onTouch(PlayerInteractEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName())) {
            $player = $event->getPlayer();
            $playerManager = $this->api->getPlayerManager($player);
            if ($playerManager->getGame()->isStart()) {
                if ($playerManager->getGame()->hasPotatoPlayer()) {
                    if ($playerManager->isPotatoPlayer()) {
                        if ($this->configBase->useParticle()) {
                            $player->broadcastEntityEvent(ActorEventPacket::FIREWORK_PARTICLES);
                        }
                    }
                }
                $item = $player->getInventory()->getItemInHand();
                switch ($item->getId()) {
                    case 264://钻石 加速
                        $effect = Effect::getEffect(1);
                        $effect->setDuration(60);
                        $effect->setAmplifier(1);
                        $player->addEffect($effect);
                        $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "成功使用加速道具！");
                        $player->getInventory()->removeItem(Item::get(264, 0, 1));
                        break;
                    case 388://绿宝石 隐形
                        $effect = Effect::getEffect(14);
                        $effect->setDuration(20);
                        $effect->setAmplifier(1);
                        $effect->setAmbient(false);
                        $effect->setVisible(false);
                        $player->addEffect($effect);
                        $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "成功使用隐形道具！");
                        $player->getInventory()->removeItem(Item::get(388, 0, 1));
                        break;
                    case 347://时钟 加时
                        if ($playerManager->getGame()->hasPotatoPlayer()) {
                            if ($playerManager->isPotatoPlayer()) {
                                $tick = mt_rand(1, 3);
                                $timeNow = PotatoBoomTask::getBoomTimeNow();
                                PotatoBoomTask::setBoomTimeNow($timeNow + $tick);
                                $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "成功使用加时道具！，爆炸时间已延迟" . $tick . "秒");
                                $player->getInventory()->removeItem(Item::get(347, 0, 1));
                            } else {
                                $player->sendMessage(SHotPotato::$DEFAULT_TITLE . "你还不是山芋玩家,无法使用此道具");
                            }
                        }
                        break;
                    case 299://绿衣服 防御
                        if($player->getArmorInventory()->getChestplate()->getId() == 299){
                            $player->sendMessage(SHotPotato::$DEFAULT_TITLE."你已经穿上防御衣了");
                            return;
                        }
                        $player->getArmorInventory()->setChestplate($item);
                        $player->getInventory()->removeItem(Item::get(299, 0, 1));
                        break;
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $name = $event->getPlayer()->getName();
            $gameState = $this->api->getGameState($this->dataManager->getPlayerRoomName($name));
            $gameState->onQuit($name);
        }
    }

/*    public function onHungerChange(PlayerHungerChangeEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $event->setCancelled();
        }
    }*/

    public function onBlockBreak(BlockBreakEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $event->setCancelled();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $event->setCancelled();
        }
    }

    public function onDropItem(PlayerDropItemEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $event->setCancelled();
        }
    }

    public function onEat(PlayerItemConsumeEvent $event){
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            if($event->getItem()->getId() == 393) {
                $event->setCancelled();
            }
        }
    }

    public function EntityDamage(EntityDamageEvent $event)
    {
        //Server::getInstance()->getLogger()->info("sb333");
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
           // Server::getInstance()->getLogger()->info("sb");
            if ($damager instanceof Player) {
                $entity = $event->getEntity();
                if($entity instanceof Player) {
                    if ($this->dataManager->isInGame($entity->getName())) {
                        $event->setCancelled();
                    }
                    if ($this->dataManager->isInGame($damager->getName())) {
                        //Server::getInstance()->getLogger()->info("sb5");
                        if ($entity instanceof Player) {
                            $event->setCancelled();
                            $damageManager = $this->api->getPlayerManager($entity);//被打
                            //Server::getInstance()->getLogger()->info("sb4");
                            if($damageManager->getGame()->isStart()) {
                                if ($damageManager->getGame()->hasPotatoPlayer()) {
                                    //Server::getInstance()->getLogger()->info("sb3");
                                    if ($this->api->getPlayerManager($damager)->isPotatoPlayer()) {
                                        //Server::getInstance()->getLogger()->info("sb2");
                                        if ($damageManager->isAlive()) {
                                            $v3 = new Vector3($entity->getFloorX(), $entity->getFloorY() + 1, $entity->getFloorZ());
                                            if($entity->getArmorInventory()->getChestplate()->getId() == 299){
                                                $entity->getArmorInventory()->setChestplate(Item::get(0));
                                                $damager->sendMessage(SHotPotato::$DEFAULT_TITLE . $entity->getName(). "§4穿上了防身衣，挡住了你的山芋攻击" );
                                                $entity->sendMessage(SHotPotato::$DEFAULT_TITLE ."你身上的防身衣替你挡了一命");
                                                $particle = new SpellParticle($v3, 0,255,0);
                                                $entity->getLevel()->addParticle($particle);
                                                $entity->getLevel()->addSound(new AnvilUseSound($v3));
                                                return;
                                            }
                                            //Server::getInstance()->getLogger()->info("sb1");
                                            $damageManager->setPotato();
                                            $entity->getLevel()->addParticle(new DestroyBlockParticle($v3, Block::get(152)));
                                            if (SHotPotato::getConfigBase()->useParticle()) {
                                                $particle = new SpellParticle($v3, 248, 36, 35);
                                                $entity->getLevel()->addParticle($particle);
                                            }
                                            $damager->sendMessage(SHotPotato::$DEFAULT_TITLE . "§6你把烫手的山芋丢给了§4" . $entity->getName());
                                            $entity->sendMessage(SHotPotato::$DEFAULT_TITLE . $damager->getName() . "§6把烫手的山芋丢给了你");
                                            $this->api->getGameState($this->dataManager->getPlayerRoomName($entity->getName()))->sendMessageToAll(SHotPotato::$DEFAULT_TITLE . "§6烫手的山芋落在了玩家§4" . $entity->getName() . "§e的手上");
                                            if (PotatoBoomTask::getBoomTimeNow() <= 6) {
                                                PotatoBoomTask::setBoomTimeNow(SHotPotato::getApi()->getRoom($damageManager->getRoomName())->getPotatoBoomTime());
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /*public function onArmorChanger(EntityArmorChangeEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            if($this->dataManager->isInGame($player->getName()))
            {
                $playerManager = $this->api->getPlayerManager($player);
                if($playerManager->getGame()->isStart()) {
                    if ($playerManager->getGame()->hasPotatoPlayer()) {
                        if ($playerManager->isPotatoPlayer()) {
                            $event->setCancelled();
                        }
                    }
                }
            }
        }
    }*/

    public function onModeChange(PlayerGameModeChangeEvent $event){
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $event->setCancelled();
        }
    }

    public function onHeld(PlayerItemHeldEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            $playerName = $event->getPlayer()->getName();
            if($event->getItem()->getId() == 324){
                $event->getPlayer()->getInventory()->setHeldItemIndex(0);
                if(array_key_exists($playerName,$this->touchPlayers)){
                    if(time()-$this->touchPlayers[$playerName] <10) {
                        $this->api->getGameState($this->dataManager->getPlayerRoomName($playerName))->onQuit($playerName);
                        $event->getPlayer()->sendMessage("§6成功退出游戏");
                    }else{
                        unset($this->touchPlayers[$playerName]);
                    }
                    return;
                }else{
                    $this->touchPlayers[$playerName] = time();
                    return;
                }
            }
        }
    }

    public function onChat(PlayerChatEvent $event)
    {
        if($this->configBase->isReceive()) {
            $gamePlayers = array_keys($this->dataManager->getGamePlayers());
            $chatPlayers = $event->getRecipients();
            foreach ($gamePlayers as $gamePlayer) {
                $player = Server::getInstance()->getPlayerExact($gamePlayer);
                unset($chatPlayers[array_search($player, $chatPlayers)]);
            }
            $event->setRecipients($chatPlayers);
        }
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            if(!$this->configBase->isSend()) {
                $player = $event->getPlayer();
                $playerName = $player->getName();
                $message = $event->getMessage();
                $this->api->getGameState($this->dataManager->getPlayerRoomName($playerName))->sendMessageToAll("§7<§b" . $playerName . "§7>§6: " . $message);
                $event->setCancelled();
            }
        }
    }

    public function onCommand(PlayerCommandPreprocessEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName()))
        {
            if($this->configBase->canUseCommand()){
                return;
            }
            $command = $event->getMessage();
            if(substr( $command, 0, 1 ) == "/") {
                $canUseCommands = $this->configBase->canUseCommandList();
                foreach ($canUseCommands as $canUseCommand) {
                    if (strstr($command, $canUseCommand)) {
                        return;
                    }
                }
                $event->setCancelled();
            }
        }
    }

    public function onLevelChanger(EntityLevelChangeEvent $event)
    {
        $player = $event->getEntity();
        if($player instanceof Player){
            $playerName = $player->getName();
            if($this->dataManager->isInGame($playerName))
            {
                $event->setCancelled();
            }
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        if($this->dataManager->isInGame($event->getPlayer()->getName())) {
            $player = $event->getPlayer();
            if($player->hasEffect(14)){
                return;
            }
            $location = $player->getLocation();
            $roomData = $this->api->getRoomData($this->dataManager->getPlayerRoomName($player->getName()));
            if (SHotPotato::getConfigBase()->useParticle()) {
                if ($roomData->hasPotatoPlayer()) {
                    if ($roomData->getPotatoPlayer()->getName() == $player->getName()) {
                        $player->getLevel()->addParticle(new LavaDripParticle($location));
                    } else {
                        $player->getLevel()->addParticle(new WaterDripParticle($location));
                    }
                }
                $player->getLevel()->addParticle(new HappyVillagerParticle($location));
                $player->getLevel()->addParticle(new EnchantmentTableParticle($location));
            }
        }
    }

  /*  public function onSend(PlayerTextPreSendEvent $event)
    {
        $player = $event->getPlayer();
        if($this->dataManager->isInGame($player->getName())) {
            if($event->getType() == PlayerTextPreSendEvent::TIP or $event->getType() == PlayerTextPreSendEvent::POPUP){
                $message = $event->getMessage();
                if(!strstr($message,SHotPotato::$DEFAULT_TITLE)){
                    $event->setCancelled();
                }
            }
        }
    }*/
}