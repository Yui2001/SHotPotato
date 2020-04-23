<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/7
 * Time: 17:47
 */

namespace net\mcpes\summit\yui;

use net\mcpes\summit\yui\entity\FireWork;
use net\mcpes\summit\yui\entity\Lightning;
use net\mcpes\summit\yui\gameConfig\ConfigBase;
use net\mcpes\summit\yui\listen\PlayerListen;
use net\mcpes\summit\yui\listen\SignListen;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SHotPotato extends PluginBase
{
    private static $api;
    private static $instance;
    private static $configBase;
    private static $dataManager;

    public static $dataFolder;
    public static $DEFAULT_TITLE = "§l§e[§6S§eH§do§3t§3P§ao§4t§5a§6t§ao§e] ";

    public static function getInstance(): SHotPotato
    {
        return self::$instance;
    }

    public static function getApi(): SHotPotatoApi
    {
        return self::$api;
    }

    public static function getDataManager(): DataManager
    {
        return self::$dataManager;
    }

    public static function getConfigBase(): ConfigBase
    {
        return self::$configBase;
    }

    public static function callLightning(Player $player)
    {
        $a = new Lightning($player->getLevel(), self::getNBT($player));
        $a->spawnToAll();
        $player->getLevel()->addSound(new EndermanTeleportSound($player->getMotion()));
    }

    public static function getNBT(Player $player): CompoundTag
    {
        $pos = $player->getLocation();
        $nbt = new CompoundTag("", [
            "Pos" => new ListTag("Pos", [
                new DoubleTag("", $pos->getX()),
                new DoubleTag("", $pos->getY()),
                new DoubleTag("", $pos->getZ())
            ]),
            "Motion" => new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0)
            ]),
            "Rotation" => new ListTag("Rotation", [
                new FloatTag("", 0),
                new FloatTag("", 0)
            ]),
        ]);
        return $nbt;
    }

    public function onLoad()
    {
        self::$instance = $this;
        self::$api = new SHotPotatoApi();
        self::$configBase = new ConfigBase();
        self::$dataManager = new DataManager();
        self::$dataFolder = $this->getDataFolder();
        Entity::registerEntity(Lightning::class);
        Entity::registerEntity(FireWork::class);
        $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6成功加载！");
    }

    public function onEnable()
    {
        $this->createData();
        $this->prepareData();
        $this->registerEvents();
        $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6成功启动！");
    }

    public function onDisable()
    {
        $rooms = self::getDataManager()->getRoomsData();
        foreach ($rooms as $room) {
            $state = self::getApi()->getGameStateByArray($room);
            $state->gameStop();
            if ($state->getRoomData()->isStart()) {
                $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6强制停止房间" . $state->getRoomName() . "成功！");
            }
            $state->getRoomBase()->saveAll();
            $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6卸载房间" . $state->getRoomName() . "成功！");
        }
        $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6卸载成功！");
    }

    private function registerEvents()
    {
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListen(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SignListen(), $this);
    }

    private function createData()
    {
        if (!file_exists(self::$dataFolder)) {
            @mkdir(self::$dataFolder);
        }

        if (!file_exists(self::$dataFolder . "/rooms")) {
            @mkdir(self::$dataFolder . "/rooms");
        }
        $this->saveResource("gameConfig.yml");
    }

    private function prepareData()
    {
        $configBase = new Config(self::$dataFolder . "/gameConfig.yml", Config::YAML);
        if (!$configBase->exists("command_use")) {
            $configBase->set("command_use", false);
        }
        if (!$configBase->exists("command_canUse")) {
            $configBase->set("command_canUse", array());
        }
        $configBase->save();
        self::getConfigBase()->setConfig($configBase->getAll());
        $handler = opendir(self::$dataFolder . "/rooms");
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                $roomName = explode(".", $filename);
                $config = new Config(self::$dataFolder . "/rooms/" . $roomName[0] . ".yml", Config::YAML);
                if (!$config->exists("winner-cmd")) {
                    $config->set("winner-cmd", array());
                    $config->save();
                }
                self::getApi()->getNewRoom($roomName[0])->add($config->getAll());
                self::getApi()->getNewRoomData($roomName[0])->add();
                $room = self::getApi()->getRoom($roomName[0]);
                $levelName = $room->getSignLevel();
                if (!$this->getServer()->isLevelLoaded($levelName)) {
                    $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6木牌所在地图“ " . $levelName . " ”未加载，正在加载此地图！");
                    $this->getServer()->generateLevel($levelName);
                    $ok = $this->getServer()->loadLevel($levelName);
                    if ($ok === false) {
                        $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§4木牌所在地图“ " . $levelName . " ”加载失败！");
                    }
                }
                self::getDataManager()->addSign($room->getSignLocation()->__toString(), $roomName[0]);
                self::getApi()->getGameState($roomName[0])->resetGame();
                $this->getServer()->getLogger()->info(self::$DEFAULT_TITLE . "§6加载房间“ " . $roomName[0] . " ”成功！");
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, $s, array $args): bool
    {
        if ($command != "hotpotato") return false;
        if (!$sender instanceof Player) {
            $sender->sendMessage(self::$DEFAULT_TITLE . "§6请在游戏内运行此指令");
            return false;
        }
        if (!isset($args[0])) {
            return false;
        }
        $playerName = $sender->getName();
        switch ($args[0]) {
            case "加入":
            case "join":
                if (!isset($args[1])) {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6请填写房间名称");
                    return false;
                }
                if (self::getDataManager()->hasRoomData($args[1])) {
                    self::getApi()->getGameState($args[1])->onJoin($playerName);
                } else {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§4没有此房间！");
                }
                break;
            case "退出":
            case "exit":
                if (!isset($args[1])) {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6请填写房间名称");
                    return false;
                }
                if (self::getDataManager()->isInGame($playerName)) {
                    $roomName = self::getDataManager()->getPlayerRoomName($playerName);
                    $state = self::getApi()->getGameState($roomName);
                    $state->onQuit($playerName);
                } else {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§4你并没有加入游戏!");
                }
                break;
            case "新建":
            case "setup":
                if (!$sender->isOp()) {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6你不是op");
                    return false;
                }
                if (!isset($args[1])) {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6请填写房间名称");
                    return false;
                }
                $roomName = $args[1];
                if (!self::getDataManager()->hasRoomBase($roomName)) {
                    self::getApi()->getNewRoom($roomName)->addNewRoom();
                    self::getApi()->getNewRoomData($roomName)->add();
                } else {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6已有此房间");
                    return false;
                }
                $sender->sendMessage(self::$DEFAULT_TITLE . "§6成功创建新的游戏房间" . $roomName);
                break;
            /*case "print":
                print_r(self::getDataManager()->getRoomsBase());
                print_r(self::getDataManager()->getRoomsData());
                print_r(self::getDataManager()->getGamePlayers());
                break;*/
            case "设置":
            case "set":
                if (!$sender->isOp()) {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6你不是op");
                    return false;
                }
                if (!isset($args[1])) {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6请填写房间名称");
                    return false;
                }
                $location = $sender->getPosition();
                $roomName = $args[1];
                if (self::getDataManager()->hasRoomBase($roomName)) {
                    $room = self::getApi()->getRoom($roomName);
                } else {
                    $sender->sendMessage(self::$DEFAULT_TITLE . "§6无此房间");
                    return false;
                }
                switch ($args[2]) {
                    case "等待地点":
                    case "waitPos":
                        $room->setWaitPlace($location);
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6成功设置等待地点");
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6接下来请设置游戏地点");
                        break;
                    case "游戏地点":
                    case "gamePos":
                        $room->setGamePlace($location);
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6成功设置游戏地点");
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6接下来请设置观战地点");
                        break;
                    case "观战地点":
                    case "watchPos":
                        $room->setLookPlace($location);
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6成功设置观战地点");
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6接下来请设置退出地点");
                        break;
                    case "退出地点":
                    case "exitPos":
                        $room->setOverPlace($location);
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6成功设置退出地点");
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6接下来请保存游戏信息");
                        break;
                    case "保存":
                    case "save":
                        $room->saveAll();
                        $sender->sendMessage(self::$DEFAULT_TITLE . "§6保存游戏房间" . $roomName . "的信息,还有一些基础信息需要在配置文件中修改");
                        break;
                    case "道具":
                    case "tool":
                        if (!isset($args[3])) {
                            return false;
                        }
                        if ($sender->getLevel()->getFolderName() == $room->getGameLevel()->getFolderName()) {
                            $position = $sender->getPosition();
                            $location = array(
                                "prop-x" => $position->getFloorX(),
                                "prop-y" => $position->getFloorY(),
                                "prop-z" => $position->getFloorZ(),
                                "rand" => 100,
                                "item" => $args[3]
                            );
                            $room->setProps($location);
                            $sender->sendMessage(self::$DEFAULT_TITLE . "成功设置道具地点");
                        } else {
                            $sender->sendMessage(self::$DEFAULT_TITLE . "设置道具的世界要和游戏世界一样");
                        }
                        break;
                }
                break;
        }
        return false;
    }
}