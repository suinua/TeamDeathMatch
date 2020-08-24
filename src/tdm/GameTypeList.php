<?php


namespace tdm;


use team_game_system\model\GameType;

class GameTypeList
{
    static function TeamDeathMatch(): GameType {
        return new GameType("TeamDeathMatch");
    }
}