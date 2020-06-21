<?php

declare(strict_types=1);

namespace xSuper\Core\Tasks;

use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xSuper\Core\Enchants\CustomEnchant;
use xSuper\Core\Enchants\TickingEnchantment;
use xSuper\Core\Loader;
use xSuper\Core\Utils\EnchantUtils;

class TickEnchantmentsTask extends Task
{
    /** @var Loader */
    private $plugin;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $successfulEnchantments = [];
            foreach ($player->getInventory()->getContents() as $slot => $content) {
                if ($content->getId() === Item::BOOK) {
                    if (count($content->getEnchantments()) > 0) {
                        $enchantedBook = Item::get(Item::ENCHANTED_BOOK, 0, $content->getCount());
                        $enchantedBook->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Enchanted Book");
                        $enchantedBook->setNamedTagEntry($content->getNamedTagEntry(Item::TAG_ENCH));
                        $player->getInventory()->setItem($slot, $enchantedBook);
                        continue;
                    }
                }
                if ($content->getNamedTagEntry("PiggyCEItemVersion") === null && count($content->getEnchantments()) > 0) $player->getInventory()->setItem($slot, $this->cleanOldItems($content));
                foreach ($content->getEnchantments() as $enchantmentInstance) {
                    /** @var TickingEnchantment $enchantment */
                    $enchantment = $enchantmentInstance->getType();
                    if ($enchantment instanceof CustomEnchant && $enchantment->canTick()) {
                        if (!in_array($enchantment, $successfulEnchantments) || $enchantment->supportsMultipleItems()) {
                            if ((
                                $enchantment->getUsageType() === CustomEnchant::TYPE_ANY_INVENTORY ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_INVENTORY ||
                                ($enchantment->getUsageType() === CustomEnchant::TYPE_HAND && $slot === $player->getInventory()->getHeldItemIndex())
                            )) {
                                if ($currentTick % $enchantment->getTickingInterval() === 0) {
                                    $enchantment->onTick($player, $content, $player->getInventory(), $slot, $enchantmentInstance->getLevel());
                                    $successfulEnchantments[] = $enchantment;
                                }
                            }
                        }
                    }
                }
            }
            foreach ($player->getArmorInventory()->getContents() as $slot => $content) {
                if ($content->getNamedTagEntry("PiggyCEItemVersion") === null && count($content->getEnchantments()) > 0) $player->getArmorInventory()->setItem($slot, $this->cleanOldItems($content));
                foreach ($content->getEnchantments() as $enchantmentInstance) {
                    /** @var TickingEnchantment $enchantment */
                    $enchantment = $enchantmentInstance->getType();
                    if ($enchantment instanceof CustomEnchant && $enchantment->canTick()) {
                        if (!in_array($enchantment, $successfulEnchantments) || $enchantment->supportsMultipleItems()) {
                            if ((
                                $enchantment->getUsageType() === CustomEnchant::TYPE_ANY_INVENTORY ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_ARMOR_INVENTORY ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_HELMET && EnchantUtils::isHelmet($content) ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_CHESTPLATE && EnchantUtils::isChestplate($content) ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_LEGGINGS && EnchantUtils::isLeggings($content) ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_BOOTS && EnchantUtils::isBoots($content)
                            )) {
                                if ($currentTick % $enchantment->getTickingInterval() === 0) {
                                    $enchantment->onTick($player, $content, $player->getArmorInventory(), $slot, $enchantmentInstance->getLevel());
                                    $successfulEnchantments[] = $enchantment;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function cleanOldItems(Item $item): Item
    {
        foreach ($item->getEnchantments() as $enchantmentInstance) {
            $enchantment = $enchantmentInstance->getType();
            if ($enchantment instanceof CustomEnchant) {
                $item->setCustomName(str_replace("\n" . $enchantment->getName() . " " . EnchantUtils::getRomanNumeral($enchantmentInstance->getLevel()), "", $item->getCustomName()));
                $lore = $item->getLore();
                if (($key = array_search($enchantment->getName() . " " . EnchantUtils::getRomanNumeral($enchantmentInstance->getLevel()), $lore))) {
                    unset($lore[$key]);
                }
                $item->setLore($lore);
            }
        }
        $item->setNamedTagEntry(new IntTag("PiggyCEItemVersion", 0));
        return $item;
    }
}

