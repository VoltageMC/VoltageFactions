<?php

namespace xSuper\Core;

use pocketmine\{item\Item, Player, plugin\PluginBase, utils\Config, utils\TextFormat};
use muqsit\invmenu\InvMenuHandler;
use ReflectionException;
use xSuper\Core\API\EnchantAPI;
use xSuper\Core\API\KitAPI;
use xSuper\Core\Commands\EnergyCommands\EnergyCommand;
use xSuper\Core\Commands\KitCommands\KitCommand;
use xSuper\Core\Enchants\ToggleableEnchantment;
use xSuper\Core\Tasks\KitCooldownTask;
use xSuper\Core\Tasks\TickEnchantmentsTask;

class Loader extends PluginBase
{

    /** @var self */
    public static $instance;

    public function onEnable()
    {
        self::$instance = $this;
        foreach (["rarities", "max_levels", "display_names", "descriptions", "extra_data"] as $file) {
            $this->saveResource($file . ".json");
            foreach ((new Config($this->getDataFolder() . $file . ".json"))->getAll() as $enchant => $data) {
                EnchantAPI::getInstance()->enchantmentData[$enchant][$file] = $data;
            }
        }
        $this->saveDefaultConfig();

        try {
            EnchantAPI::init($this);
        } catch (ReflectionException $e) {
        }

        $this->getScheduler()->scheduleRepeatingTask(new TickEnchantmentsTask($this), 1);

        if (!is_dir($this->getDataFolder() . 'cooldowns/')) {
            if (!mkdir($this->getDataFolder() . 'cooldowns/', 0777, true) && !is_dir($this->getDataFolder() . 'cooldowns/')) {
                $this->getLogger()->error('Unable to create cooldowns folder');
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }
        KitAPI::getInstance()->loadKits();
        KitAPI::getInstance()->permissionsMode = $this->getConfig()->get('permissions-mode', true);
        $this->getScheduler()->scheduleDelayedRepeatingTask(new KitCooldownTask($this), 1200, 1200);

        $this->getServer()->getCommandMap()->register("energy", new EnergyCommand($this));
        $this->getServer()->getCommandMap()->register("kit", new KitCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
    }

    public function onDisable(): void
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            foreach ($player->getInventory()->getContents() as $slot => $content) {
                foreach ($content->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $content, $enchantmentInstance, $player->getInventory(), $slot, false);
            }
            foreach ($player->getArmorInventory()->getContents() as $slot => $content) {
                foreach ($content->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $content, $enchantmentInstance, $player->getArmorInventory(), $slot, false);
            }
        }
        foreach (KitAPI::getInstance()->kits as $kit) {
            $kit->save();
        }
        KitAPI::getInstance()->kits = [];
    }

    public function giveEnergyOrb(Int $energy, Player $player) {
        $item = Item::get(Item::DYE, 12)->setCustomName(TextFormat::RESET . TextFormat::BOLD . number_format($energy, 0, ".", ",") . TextFormat::AQUA . " Raw Energy");
        $item->setLore([
            TextFormat::RESET . "",
            TextFormat::RESET . TextFormat::GOLD . "Contains " . TextFormat::WHITE . TextFormat::BOLD . number_format($energy, 0, ".", ",") . TextFormat::AQUA . " Cosmic Energy",
            TextFormat::RESET . TextFormat::GOLD . " that is used for enchanting",
            TextFormat::RESET . "",
            TextFormat::RESET . TextFormat::GRAY . "Hint: Drag and drop onto a pickaxe,",
            TextFormat::RESET . TextFormat::GRAY . " sword, or piece of armor to add",
            TextFormat::RESET . TextFormat::GRAY . " to its energy!",
            TextFormat::RESET . "",
            TextFormat::RESET . TextFormat::GOLD . "Extracted by " . TextFormat::WHITE . $player->getName(),
        ]);
        $item->getNamedTag()->setInt("energyOrb", $energy);
        return $item;
    }

    public static function getInstance(): Loader
    {
        return self::$instance;
    }
}
