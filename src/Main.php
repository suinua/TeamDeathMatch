<?php

namespace TeamDeathMatch;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use team_game_system\model\Score;
use team_game_system\pmmp\event\AddedScoreEvent;
use team_game_system\pmmp\event\FinishedGameEvent;
use team_game_system\pmmp\event\PlayerJoinedGameEvent;
use team_game_system\pmmp\event\PlayerKilledPlayerEvent;
use team_game_system\pmmp\event\StartedGameEvent;
use team_game_system\TeamGameSystem;

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

    public function onJoinGame(PlayerJoinedGameEvent $event) {
        $player = $event->getPlayer();

        $level = Server::getInstance()->getLevelByName("lobby");
        foreach ($level->getPlayers() as $lobbyPlayer) {
            $lobbyPlayer->sendMessage($player->getName() . "がチームデスマッチに参加しました");
        }
    }

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

            //アイテムをセット
            $player->getInventory()->setContents([
                ItemFactory::get(ItemIds::WOODEN_SWORD, 0, 1),
                ItemFactory::get(ItemIds::APPLE, 0, 10),
            ]);
        }
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event): void {
        $attacker = $event->getAttacker();
        $attackerData = TeamGameSystem::getPlayerData($attacker);

        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        $game = TeamGameSystem::getGame($attackerData->getGameId());
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

        TeamGameSystem::addScore($attackerData->getGameId(), $attackerData->getTeamId(), new Score(1));
    }

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

    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $playerData = TeamGameSystem::getPlayerData($player);

        if ($playerData->getGameId() === null) return;

        //チームデスマッチ以外のゲームに関するものだったら処理を行わない
        $game = TeamGameSystem::getGame($playerData->getGameId());
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

        $event->setDrops([]);
    }
}