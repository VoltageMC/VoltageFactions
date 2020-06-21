<?php

namespace xSuper\Core\Commands\EnergyCommands;

use xSuper\Core\Loader;
use pocketmine\{command\CommandSender, command\PluginCommand, item\Item, plugin\Plugin, utils\TextFormat};

class EnergyCommand extends PluginCommand
{

    public function __construct(Plugin $owner)
    {
        parent::__construct("energy", $owner);
        $this->setPermission("energy.command");
        $this->setDescription("<player> <amount>");
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if (!$this->testPermission($player)) return false;

        /** @var Loader $plugin */
        $plugin = $this->getPlugin();
        if (!isset($args[0])) {
            return false;
        }
        if (($target = $plugin->getServer()->getPlayer($args[0])) === null) {
            return false;
        }
        if (!isset($args[1])) {
            return false;
        }

        $energy = (int)$args[1];

        $item = Loader::getInstance()->giveEnergyOrb($energy, $target);

        foreach ($target->getInventory()->addItem($item) as $drop) {
            $target->getLevel()->dropItem($target, $drop);
        }
        return true;
    }
}