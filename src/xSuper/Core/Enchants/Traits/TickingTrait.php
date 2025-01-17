<?php

declare(strict_types=1);

namespace xSuper\Core\Enchants\Traits;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use xSuper\Core\Loader;

trait TickingTrait
{
    /** @var Loader */
    protected $plugin;

    public function canTick(): bool
    {
        return true;
    }

    public function getTickingInterval(): int
    {
        return 1;
    }

    public function onTick(Player $player, Item $item, Inventory $inventory, int $slot, int $level): void
    {
        $perWorldDisabledEnchants = $this->plugin->getConfig()->get("per-world-disabled-enchants");
        if (isset($perWorldDisabledEnchants[$player->getLevel()->getFolderName()]) && in_array(strtolower($this->name), $perWorldDisabledEnchants[$player->getLevel()->getFolderName()])) return;
        if ($this->getCooldown($player) > 0) return;
        $this->tick($player, $item, $inventory, $slot, $level);
    }

    public function tick(Player $player, Item $item, Inventory $inventory, int $slot, int $level): void
    {

    }

    public function supportsMultipleItems(): bool
    {
        return false;
    }
}
