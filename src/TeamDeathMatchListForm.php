<?php


namespace TeamDeathMatch;


use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use team_game_system\TeamGameSystem;

class TeamDeathMatchListForm extends SimpleForm
{

    public function __construct() {
        $buttons = [];

        foreach (TeamGameSystem::findGamesByType(GameTypeList::TeamDeathMatch()) as $game) {
            $gameId = $game->getId();
            $map = $game->getMap();

            $maxScore = $game->getMaxScore() === null ? "無し" : $game->getMaxScore()->getValue();

            $timeLimit = $game->getTimeLimit() === null ? "無し" : $game->getTimeLimit() . "秒";

            $participantsCount = count(TeamGameSystem::getGamePlayersData($gameId));
            $participants = $game->getMaxPlayersCount() === null ? $participantsCount : "{$participantsCount}/{$game->getMaxPlayersCount()}";

            $buttons[] = new SimpleFormButton(
                "ゲームモード:" . TextFormat::BOLD . strval($game->getType()) . TextFormat::RESET .
                ",マップ:" . TextFormat::BOLD . $map->getName() . TextFormat::RESET .
                "\n勝利判定:" . TextFormat::BOLD . $maxScore . TextFormat::RESET .
                ",時間制限:" . TextFormat::BOLD . $timeLimit . TextFormat::RESET .
                ",参加人数:" . TextFormat::BOLD . $participants . TextFormat::RESET,
                null,
                function (Player $player) use ($gameId) {
                    TeamGameSystem::joinGame($player, $gameId);
                }
            );
        }

        parent::__construct("チームデスマッチ一覧", "", $buttons);
    }

    function onClickCloseButton(Player $player): void { }
}