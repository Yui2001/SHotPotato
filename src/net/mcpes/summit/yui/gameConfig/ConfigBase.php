<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/23
 * Time: 20:13
 */

namespace net\mcpes\summit\yui\gameConfig;


class ConfigBase
{
    private $config = [];

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function isSend():bool
    {
        return $this->config["chat_send"];
    }

    public function isReceive():bool
    {
        return $this->config["chat_receive"];
    }

    public function useParticle():bool
    {
        return $this->config["particle_use"];
    }

    public function canUseCommand():bool
    {
        return $this->config["command_use"];
    }

    public function canUseCommandList():array
    {
        return $this->config["command_canUse"];
    }
}