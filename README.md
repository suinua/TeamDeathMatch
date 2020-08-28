# 使い方

pluginsフォルダに移動して
```shell script
git clone https://github.com/MineDeepRock/team_game_system
git clone https://github.com/MineDeepRock/form_builder
git clone https://github.com/MineDeepRock/slot_menu_system
git clone https://github.com/MineDeepRock/scoreboard_system
git clone https://github.com/MineDeepRock/bossbar_system
```

続いてMapの設定
1,まず登録したいマップにテレポートします  
2,`/map`コマンドを打ちます  
3,`Create`を選択  
4,マップ名を入力し送信  
5,スポーン地点グループを2つ追加(0と1ができる)  
6,0を選択しいくつかスポーン地点を登録 1も同様に行う  
7,終わり  

`src\tdm\CreateTeamDeathMatchForm.php`の54行目の`mapname`を登録したマップ名に変更

```php:src\tdm\CreateTeamDeathMatchForm.php
$map = TeamGameSystem::selectMap("city", $teams);
```