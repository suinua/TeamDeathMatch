<?php


namespace tdm;


use bossbar_system\model\BossBarType;

class BossBarTypeList
{
    static function TeamDeathMatch(): BossBarType {
        return new BossBarType("TeamDeathMatch");
    }
}