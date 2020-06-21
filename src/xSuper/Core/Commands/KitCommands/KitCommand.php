<?php

namespace xSuper\Core\Commands\KitCommands;

use pocketmine\{command\CommandSender, command\PluginCommand, Player, plugin\Plugin};
use xSuper\Core\Menus\Kits\RankKitMenu;

class KitCommand extends PluginCommand
{

    public function __construct(Plugin $owner)
    {
        parent::__construct("kit", $owner);
        $this->setPermission("kit.command");
        $this->setDescription("");
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if (!$this->testPermission($player)) return false;

        if(!($player instanceof Player)){
            $player->sendMessage("in game");
            return true;
        } else {
            new RankKitMenu($player);
        }
        return true;
    }
}
