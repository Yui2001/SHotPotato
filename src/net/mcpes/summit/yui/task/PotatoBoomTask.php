<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/9
 * Time: 21:50
 */

namespace net\mcpes\summit\yui\task;


use net\mcpes\summit\yui\gameFunction\GameState;
use net\mcpes\summit\yui\SHotPotato;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\scheduler\PluginTask;

class PotatoBoomTask extends PluginTask
{

    private $state;
    private static $boom;

    public function __construct(GameState $state,SHotPotato $owner){
        parent::__construct($owner);
        $this->state = $state;
        self::$boom = $this->getBoomTime();
    }

    public function onRun($currentTick)
    {
        if($this->state->getRoomData()->isStop()){
            $this->getHandler()->cancel();
        }
        self::$boom--;
        if(!$this->state->getRoomData()->hasPotatoPlayer()) {
            $this->state->sendTipToAll(SHotPotato::$DEFAULT_TITLE."§6烫手的山芋还有".self::$boom."秒随机发放到一人手上");
        }else{
            $this->state->sendTipToAll(SHotPotato::$DEFAULT_TITLE."§4烫手的山芋§6还有 ".self::$boom." 秒BOOM");
        }
        if(self::$boom == 1){
            if($this->state->getRoomData()->hasPotatoPlayer()) {
                $potato = $this->state->getRoomData()->getPotatoPlayer();
                $potato->getLevel()->addSound(new TNTPrimeSound($potato->getLocation()));
            }
        }
        if(self::$boom <= 5){
            if(self::$boom == 0){
                $this->state->addSoundToAll(2);
            }else{
                $this->state->addSoundToAll(1);
            }
        }
        if(self::$boom <= 0){
            if($this->state->getRoomData()->hasPotatoPlayer()) {
                $this->state->setPotatoPlayerBoom();
            }
            if($this->state->getRoomData()->getAlivePlayersCount() <= 1){
                return;
            }
            $this->state->randomPotatoPlayer();
            self::$boom = $this->getBoomTime();
        }
    }

    public static function setBoomTimeNow(int $time)
    {
        self::$boom = $time;
    }

    public static function getBoomTimeNow():int
    {
        return self::$boom;
    }

    private function getBoomTime():int
    {
        return SHotPotato::getApi()->getRoom($this->state->getRoomName())->getPotatoBoomTime();
    }
}