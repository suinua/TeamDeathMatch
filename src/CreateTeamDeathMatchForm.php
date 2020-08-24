<?php


namespace TeamDeathMatch;


use form_builder\models\custom_form_elements\Input;
use form_builder\models\custom_form_elements\Label;
use form_builder\models\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use team_game_system\model\Game;
use team_game_system\model\Score;
use team_game_system\model\Team;
use team_game_system\TeamGameSystem;

class CreateTeamDeathMatchForm extends CustomForm
{

    private $timeLimit;
    private $maxPlayersCount;
    private $maxScore;

    public function __construct() {
        $this->maxScore = new Input("勝利判定スコア", "", "20");
        $this->maxPlayersCount = new Input("人数制限", "", "");
        $this->timeLimit = new Input("制限時間(秒)", "", "300");

        parent::__construct("", [
            new Label("無い場合は空白でお願いします"),
            $this->maxScore,
            $this->maxPlayersCount,
            $this->timeLimit,
        ]);
    }

    function onSubmit(Player $player): void {
        $maxScore = $this->maxScore->getResult();
        $maxScore = $maxScore === "" ? null : new Score(intval($maxScore));

        $maxPlayersCount = $this->maxPlayersCount->getResult();
        $maxPlayersCount = $maxPlayersCount === "" ? null : intval($maxPlayersCount);

        $timeLimit = $this->timeLimit->getResult();
        $timeLimit = $timeLimit === "" ? null : intval($timeLimit);

        //チーム
        $teams = [
            Team::asNew("Red", TextFormat::RED),
            Team::asNew("Blue", TextFormat::BLUE),
        ];

        //マップを選択(あとからMinecraft内でマップを登録します)
        $map = TeamGameSystem::selectMap("mapname", $teams);
        //ゲームを作成
        $game = Game::asNew(GameTypeList::TeamDeathMatch(), $map, $teams, $maxScore, $maxPlayersCount, $timeLimit);
        //ゲームを登録
        TeamGameSystem::registerGame($game);
    }

    function onClickCloseButton(Player $player): void { }
}