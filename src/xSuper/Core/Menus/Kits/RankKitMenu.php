<?php

declare(strict_types=1);

namespace xSuper\Core\Menus\Kits;

use muqsit\invmenu\session\PlayerManager;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xSuper\Core\Loader;
use pocketmine\item\ItemIds;
use xSuper\Core\Menus\ChestMenu;

class RankKitMenu extends ChestMenu
{
    /**
     * @var TaskHandler
     */
    private $taskHandler;

    public function __construct(Player $player)
    {
        $this->taskHandler = Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            $player = $this->player;
            if (!$player->isClosed()) {
                $this->render();
            }
        }), 20);
        parent::__construct($player);
    }

    public function handle(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool
    {
        $menu = PlayerManager::get($this->player)->getCurrentMenu();
        switch ($action->getSlot()) {
            case 10:
                $kit = Loader::getInstance()->getKit("starter");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $kit->handleRequest($this->player);
                    $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                }
                break;
            case 11:
                $kit = Loader::getInstance()->getKit("mortal");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $kit->handleRequest($this->player);
                    $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                }
                break;
            case 12:
                $kit = Loader::getInstance()->getKit("demigod");
                if ($this->player->hasPermission("kit.demigod")) {
                    if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                        $kit->handleRequest($this->player);
                        $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                    }
                }
                break;
            case 13:
                $kit = Loader::getInstance()->getKit("god");
                if ($this->player->hasPermission("kit.god")) {
                    if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                        $kit->handleRequest($this->player);
                        $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                    }
                }
                break;
            case 14:
                $kit = Loader::getInstance()->getKit("hades");
                if ($this->player->hasPermission("kit.hades")) {
                    if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                        $kit->handleRequest($this->player);
                        $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                    }
                }
                break;
            case 15:
                $kit = Loader::getInstance()->getKit("kronos");
                if ($this->player->hasPermission("kit.kronos")) {
                    if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                        $kit->handleRequest($this->player);
                        $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                    }
                }
                break;
            case 16:
                $kit = Loader::getInstance()->getKit("zeus");
                if ($this->player->hasPermission("kit.zeus")) {
                    if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                        $kit->handleRequest($this->player);
                        $this->player->removeWindow($menu->getInventoryForPlayer($this->player));
                    }
                }
                break;
        }
        return false;
    }

    public function render(): void
    {
        $player = $this->player;
        $newPlayer = (string) $player;
        $target = Server::getInstance()->getPlayer($newPlayer);
        if (!$target instanceof Player && !$player->isClosed()) {
            $this->setName(TextFormat::BOLD . TextFormat::AQUA . "> " . TextFormat::RESET . TextFormat::GRAY . "Rank Kits" . TextFormat::BOLD . TextFormat::AQUA . " <");
            $kit = Loader::getInstance()->getKit("starter");
            if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                $starter = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::BOLD . TextFormat::GREEN . "Starter");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $starter->setLore($lore);
            } else {
                $starter = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::BOLD . TextFormat::GREEN . "Starter");
                $min = $kit->getCooldownLeft($this->player);
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                ];
                $starter->setLore($lore);
            }
            $kit = Loader::getInstance()->getKit("mortal");
            if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                $mortal = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::GRAY . "Mortal");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $mortal->setLore($lore);
            } else {
                $mortal = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::GRAY . "Mortal");
                $min = $kit->getCooldownLeft($this->player);
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                ];
                $mortal->setLore($lore);
            }
            if ($this->player->hasPermission("kit.demigod")) {
                $kit = Loader::getInstance()->getKit("demigod");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $demigod = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::BOLD . TextFormat::GRAY . "Demigod");
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                    ];
                    $demigod->setLore($lore);
                } else {
                    $demigod = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::BOLD . TextFormat::GRAY . "Demigod");
                    $min = $kit->getCooldownLeft($this->player);
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                    ];
                    $demigod->setLore($lore);
                }
            } else {
                $demigod = ItemFactory::get(ItemIds::WOOL, 8)->setCustomName(TextFormat::BOLD . TextFormat::GRAY . "Demigod");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "This kit is " . TextFormat::RED . "LOCKED" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $demigod->setLore($lore);
            }
            if ($this->player->hasPermission("kit.god")) {
                $kit = Loader::getInstance()->getKit("god");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $god = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::BOLD . TextFormat::GOLD . "God");
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                    ];
                    $god->setLore($lore);
                } else {
                    $god = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::BOLD . TextFormat::GOLD . "God");
                    $min = $kit->getCooldownLeft($this->player);
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                    ];
                    $god->setLore($lore);
                }
            } else {
                $god = ItemFactory::get(ItemIds::WOOL, 8)->setCustomName(TextFormat::BOLD . TextFormat::GOLD . "God");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "This kit is " . TextFormat::RED . "LOCKED" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $god->setLore($lore);
            }
            if ($this->player->hasPermission("kit.hades")) {
                $kit = Loader::getInstance()->getKit("hades");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $hades = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::BOLD . TextFormat::RED . "Hades");
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                    ];
                    $hades->setLore($lore);
                } else {
                    $hades = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::BOLD . TextFormat::RED . "Hades");
                    $min = $kit->getCooldownLeft($this->player);
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                    ];
                    $hades->setLore($lore);
                }
            } else {
                $hades = ItemFactory::get(ItemIds::WOOL, 8)->setCustomName(TextFormat::BOLD . TextFormat::RED . "Hades");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "This kit is " . TextFormat::RED . "LOCKED" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $hades->setLore($lore);
            }
            if ($this->player->hasPermission("kit.kronos")) {
                $kit = Loader::getInstance()->getKit("kronos");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $kronos = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Kronos");
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                    ];
                    $kronos->setLore($lore);
                } else {
                    $kronos = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Kronos");
                    $min = $kit->getCooldownLeft($this->player);
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                    ];
                    $kronos->setLore($lore);
                }
            } else {
                $kronos = ItemFactory::get(ItemIds::WOOL, 8)->setCustomName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Kronos");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "This kit is " . TextFormat::RED . "LOCKED" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $kronos->setLore($lore);
            }
            if ($this->player->hasPermission("kit.zeus")) {
                $kit = Loader::getInstance()->getKit("zeus");
                if (!isset($kit->coolDowns[$this->player->getLowerCaseName()])) {
                    $zeus = ItemFactory::get(ItemIds::WOOL, 5)->setCustomName(TextFormat::BOLD . TextFormat::AQUA . "Zeus");
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available " . TextFormat::GREEN . "NOW" . TextFormat::RESET . TextFormat::GRAY . "!"
                    ];
                    $zeus->setLore($lore);
                } else {
                    $zeus = ItemFactory::get(ItemIds::WOOL, 14)->setCustomName(TextFormat::BOLD . TextFormat::AQUA . "Zeus");
                    $min = $kit->getCooldownLeft($this->player);
                    $lore = [
                        TextFormat::RESET . TextFormat::GRAY . "Available in " . TextFormat::AQUA . $min . TextFormat::GRAY . "!"
                    ];
                    $zeus->setLore($lore);
                }
            } else {
                $zeus = ItemFactory::get(ItemIds::WOOL, 8)->setCustomName(TextFormat::BOLD . TextFormat::AQUA . "Zeus");
                $lore = [
                    TextFormat::RESET . TextFormat::GRAY . "This kit is " . TextFormat::RED . "LOCKED" . TextFormat::RESET . TextFormat::GRAY . "!"
                ];
                $zeus->setLore($lore);
            }
            $this->getInventory()->setContents([
                10 => $starter,
                11 => $mortal,
                12 => $demigod,
                13 => $god,
                14 => $hades,
                15 => $kronos,
                16 => $zeus
            ]);
        }
    }
}
