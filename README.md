# まえがき
ライブラリを使って１時間ぐらいで、サーッとチームデスマッチつくります。
PHPStormのフォーマットを利用します。

# 前提

# 環境

# ライブラリのインストール
使用するライブラリは[team_game_system](https://github.com/MineDeepRock/team_game_system)  

team_game_systemの依存関係で  
[form_builder](https://github.com/MineDeepRock/form_builder)
[slot_menu_system](https://github.com/MineDeepRock/slot_menu_system) も導入します。

スコアボードのAPIで [scoreboard_system](https://github.com/MineDeepRock/scoreboard_system) を使用します
ボスバーのAPIで [bossbar_system](https://github.com/MineDeepRock/bossbar_system) を使用します

コマンドプロンプトでpluginsフォルダに移動して

```shell-session
git clone https://github.com/MineDeepRock/team_game_system
git clone https://github.com/MineDeepRock/form_builder
git clone https://github.com/MineDeepRock/slot_menu_system
git clone https://github.com/MineDeepRock/scoreboard_system
git clone https://github.com/MineDeepRock/bossbar_system
```

これでOK

# プロジェクトの作成
`TeamDeathMatch`というフォルダを作成します。

```yaml:plugin.yml
name: TeamDeathMatch
main: TeamDeathMatch\Main
version: 1.0.0
api: 3.0.0

depend:
  - TeamGameSystem
  - FormBuilder
  - SlotMenuSystem
```


# Composerでの補完
Composerで補完したい人だけ見てください

```json:composer.json
{
  "name": "あなたの名前/TeamDeathMatch",
  "authors": [
    {
      "name": "あなたの名前"
    }
  ],
  "autoload": {
    "psr-4": {
      "": [
        "src/"
      ]
    }
  },
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/pmmp/PocketMine-MP"
    },
    {
      "type": "git",
      "name": "suinua/form_builder",
      "url": "https://github.com/MineDeepRock/form_builder"
    },
    {
      "type": "git",
      "name": "suinua/slot_menu_system",
      "url": "https://github.com/MineDeepRock/slot_menu_system"
    },
    {
      "type": "git",
      "name": "suinua/scoreboard_system",
      "url": "https://github.com/MineDeepRock/scoreboard_system"
    },
    {
      "type": "git",
      "name": "suinua/bossbar_system",
      "url": "https://github.com/MineDeepRock/bossbar_system"
    },
    {
      "type": "git",
      "name": "suinua/team_game_system",
      "url": "https://github.com/MineDeepRock/team_game_system"
    }
  ],
  "require": {
    "pocketmine/pocketmine-mp": "3.14.2",
    "suinua/form_builder": "dev-master",
    "suinua/slot_menu_system": "dev-master",
    "suinua/team_game_system": "dev-master",
    "suinua/scoreboard_system": "dev-master",
    "suinua/bossbar_system": "dev-master"
  }
}
```

# コードを書く
## Mainクラスの作成

```php:src/Main.php
<?php

namespace TeamDeathMatch;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{

}
```

## Formからゲームを作成する
ゲームを作成するフォームを作ります

下準備として`src/GameTypeList.php`を作成します

```php:src/GameTypeList
<?php


namespace TeamDeathMatch;


use team_game_system\model\GameType;

class GameTypeList
{
    static function TeamDeathMatch(): GameType {
        return new GameType("TeamDeathMatch");
    }
}
```

`src/CreateTeamDeathMatchForm`を作成します

```php:src/CreateTeamDeathMatchForm.php
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
```

`/create`コマンド時に表示されるようにします。

```php:src/Main.php
//src/Main.php 15行目
        if ($sender instanceof Player) {
            switch ($label) {
                case "create":
                    $sender->sendForm(new CreateTeamDeathMatchForm());
                    return true;
            }
        }
```

## Formからゲームに参加する
```php:src/TeamDeathMatchListForm.php
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
```


```php:src/Main.php
//src/Main.php 18行目
                case "join":
                    $sender->sendForm(new TeamDeathMatchListForm());
                    return true;
```


## コマンドからフォームを呼び出す

`plugin.yml`に以下を付け足します

```php:plugin.yml
commands:
  create:
    usage: "/create"
    description: ""
  join:
    usage: "/join"
    description: ""
```

`src/Main.php`を編集します

```php:src/Main.php
<?php

namespace TeamDeathMatch;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($sender instanceof Player) {
            switch ($label) {
                case "create":
                    $sender->sendForm(new CreateTeamDeathMatchForm());
                    return true;
                case "join":
                    $sender->sendForm(new TeamDeathMatchListForm());
                    return true;
            }
        }

        return false;
    }
}
```

## スコアボードの作成

`src/TeamDeathMatchScoreboard.php`を作成します

```php:src/TeamDeathMatchScoreboard.php
<?php


namespace TeamDeathMatch;


use pocketmine\Player;
use scoreboard_system\models\Score;
use scoreboard_system\models\Scoreboard;
use scoreboard_system\models\ScoreboardSlot;
use scoreboard_system\models\ScoreSortType;
use team_game_system\model\Game;

class TeamDeathMatchScoreboard extends Scoreboard
{
    private static function create(Game $game): Scoreboard {
        $slot = ScoreboardSlot::sideBar();
        $scores = [
            new Score($slot, "====TeamDeathMatch====", 0, 0),
            new Score($slot, "Map:" . $game->getMap()->getName(), 1, 1),
        ];

        $index = count($scores) - 1;
        foreach ($game->getTeams() as $team) {
            $scores[] = new Score($slot, $team->getTeamColorFormat() . $team->getName() . ":" . $team->getScore()->getValue(), $index, $index);
            $index++;
        }

        return parent::__create($slot, "Server Name", $scores, ScoreSortType::smallToLarge());
    }

    static function send(Player $player, Game $game) {
        $scoreboard = self::create($game);
        parent::__send($player, $scoreboard);
    }

    static function update(Player $player, Game $game) {
        $scoreboard = self::create($game);
        parent::__update($player, $scoreboard);
    }
}
```

## ボスバーの下準備

`src/BossBarTypeList.php`を作成します

```php:src/BossBarTypeList.php
<?php


namespace TeamDeathMatch;


use bossbar_system\model\BossBarType;

class BossBarTypeList
{
    static function TeamDeathMatch(): BossBarType {
        return new BossBarType("TeamDeathMatch");
    }
}
```

## ゲーム参加時にロビーにいた人に知らせる

`src/Main.php`を編集します

```php:src/Main.php
    public function onJoinGame(PlayerJoinedGameEvent $event) {
        $player = $event->getPlayer();

        $level = Server::getInstance()->getLevelByName("lobby");
        foreach ($level->getPlayers() as $lobbyPlayer) {
            $lobbyPlayer->sendMessage($player->getName() . "がチームデスマッチに参加しました");
        }
    }
```

## 試合開始時の処理を書く

`src/Main.php`を編集します

```php:src/Main.php
    public function onStartedGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $game = TeamGameSystem::getGame($gameId);
        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

        $playersData = TeamGameSystem::getGamePlayersData($gameId);

        foreach ($playersData as $playerData) {
            $player = $this->getServer()->getPlayer($playerData->getName());

            //スポーン地点をセット
            TeamGameSystem::setSpawnPoint($player);

            //テレポート
            $player->teleport($player->getSpawn());

            //通知
            $player->sendTitle("チームデスマッチ スタート");
            $team = TeamGameSystem::getTeam($gameId, $playerData->getTeamId());
            $player->sendMessage("あなたは" . $team->getTeamColorFormat() . $team->getName() . TextFormat::RESET . "です");

            //Scoreboardのセット
            TeamDeathMatchScoreBoard::send($player, $game);
            //ボスバーをセット
            $bossBar = new BossBar($player, BossBarTypeList::TeamDeathMatch(), "TeamDeathMatch", 0);
            $bossBar->send();

            //アイテムをセット
            $player->getInventory()->setContents([
                ItemFactory::get(ItemIds::WOODEN_SWORD, 0, 1),
                ItemFactory::get(ItemIds::APPLE, 0, 10),
            ]);
        }
    }
```

## 相手を倒したときにスコアが入るように

`src/Main.php`を編集します

```php:src/Main.php
    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event): void {
        $attacker = $event->getAttacker();
        $attackerData = TeamGameSystem::getPlayerData($attacker);

        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        $game = TeamGameSystem::getGame($attackerData->getGameId());
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

        TeamGameSystem::addScore($attackerData->getGameId(), $attackerData->getTeamId(), new Score(1));
    }
```

## スコア追加時にスコアボードを更新するように

`src/Main.php`を編集します

```php:src/Main.php
    public function onAddedScore(AddedScoreEvent $event): void {
        $gameId = $event->getGameId();
        $game = TeamGameSystem::getGame($gameId);

        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

        $playersData = TeamGameSystem::getGamePlayersData($gameId);

        foreach ($playersData as $playerData) {
            $player = $this->getServer()->getPlayer($playerData->getName());
            TeamDeathMatchScoreBoard::update($player, $game);
        }
    }
```

## 試合終了後に参加者をロビーに送る

`src/Main.php`を編集します

```php:src/Main.php
    public function onFinishedGame(FinishedGameEvent $event): void {
        $playersData = $event->getPlayersData();

        //lobbyに送り返す
        $server = Server::getInstance();
        $level = $server->getLevelByName("lobby");
        foreach ($playersData as $playerData) {
            $player = $server->getPlayer($playerData->getName());
            $player->getInventory()->setContents([]);
            $player->teleport($level->getSpawnLocation());
        }
    }
```

## リスポーン時にアイテムをセットする

`src/Main.php`を編集します

```php:src/Main.php
    public function onRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $playerData = TeamGameSystem::getPlayerData($player);

        if ($playerData->getGameId() === null) return;
        
        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        $game = TeamGameSystem::getGame($playerData->getGameId());
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;
        
        $player->getInventory()->setContents([
            ItemFactory::get(ItemIds::WOODEN_SWORD, 0, 1),
            ItemFactory::get(ItemIds::APPLE, 0, 10),
        ]);
    }
```

## 死亡時のアイテムドロップを消す

`src/Main.php`を編集します

```php:src/Main.php
    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $playerData = TeamGameSystem::getPlayerData($player);

        if ($playerData->getGameId() === null) return;

        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        $game = TeamGameSystem::getGame($playerData->getGameId());
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;
        
        $event->setDrops([]);
    }
```

## 試合時間経過時にボスバーを更新する
```php:src/Main.php
    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event): void {
        $gameId = $event->getGameId();
        $game = TeamGameSystem::getGame($gameId);
        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

        $playersData = TeamGameSystem::getGamePlayersData($gameId);
        $timeLimit = $event->getTimeLimit();
        $elapsedTime = $event->getElapsedTime();

        foreach ($playersData as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $bossBar = BossBar::findByType($player, BossBarTypeList::TeamDeathMatch());

            //制限時間がなかったら
            if ($timeLimit === null) {
                $bossBar->updateTitle("経過時間:" . $elapsedTime);
                continue;
            }

            $bossBar->updatePercentage($elapsedTime / $timeLimit);
            $bossBar->updateTitle($elapsedTime . "/" . $timeLimit);
        }
    }
```