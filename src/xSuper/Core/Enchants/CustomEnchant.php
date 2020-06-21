<?php

declare(strict_types=1);

namespace xSuper\Core\Enchants;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\Player;
use xSuper\Core\Loader;
use xSuper\Core\Utils\EnchantUtils;

class CustomEnchant extends Enchantment
{
    /** @var Loader */
    protected $plugin;

    /** @var string */
    public $name = "";
    /** @var int */
    public $rarity = CustomEnchant::RARITY_SIMPLE;
    /** @var int */
    public $maxLevel = 5;
    /** @var string */
    private $displayName;
    /** @var string */
    public $description;
    /** @var array */
    public $extraData;

    /** @var int */
    public $usageType = CustomEnchant::TYPE_HAND;
    /** @var int */
    public $itemType = CustomEnchant::ITEM_TYPE_WEAPON;

    /** @var array */
    public $cooldown;

    const TYPE_HAND = 0;
    const TYPE_ANY_INVENTORY = 1;
    const TYPE_INVENTORY = 2;
    const TYPE_ARMOR_INVENTORY = 3;
    const TYPE_HELMET = 4;
    const TYPE_CHESTPLATE = 5;
    const TYPE_LEGGINGS = 6;
    const TYPE_BOOTS = 7;

    const ITEM_TYPE_GLOBAL = 0;
    const ITEM_TYPE_DAMAGEABLE = 1;
    const ITEM_TYPE_WEAPON = 2;
    const ITEM_TYPE_SWORD = 3;
    const ITEM_TYPE_BOW = 4;
    const ITEM_TYPE_TOOLS = 5;
    const ITEM_TYPE_PICKAXE = 6;
    const ITEM_TYPE_AXE = 7;
    const ITEM_TYPE_SHOVEL = 8;
    const ITEM_TYPE_HOE = 9;
    const ITEM_TYPE_ARMOR = 10;
    const ITEM_TYPE_HELMET = 11;
    const ITEM_TYPE_CHESTPLATE = 12;
    const ITEM_TYPE_LEGGINGS = 13;
    const ITEM_TYPE_BOOTS = 14;
    const ITEM_TYPE_COMPASS = 15;

    const RARITY_SIMPLE = 1;
    const RARITY_UNCOMMON = 2;
    const RARITY_ELITE = 3;
    const RARITY_ULTIMATE = 4;
    const RARITY_LEGENDARY = 5;
    const RARITY_GODLY = 6;
    const RARITY_ENERGY = 7;
    const RARITY_EXECUTIVE = 8;

    /**
     * @param Loader $plugin
     * @param int $id
     */
    public function __construct(Loader $plugin, int $id)
    {
        $this->plugin = $plugin;
        $this->rarity = (int)array_flip(EnchantUtils::RARITY_NAMES)[ucfirst(strtolower($plugin->getEnchantmentData($this->name, "rarities", EnchantUtils::RARITY_NAMES[$this->rarity])))];
        $this->maxLevel = (int)$plugin->getEnchantmentData($this->name, "max_levels", $this->maxLevel);
        $this->displayName = (string)$plugin->getEnchantmentData($this->name, "display_names", $this->displayName ?? $this->name);
        $this->description = (string)$plugin->getEnchantmentData($this->name, "descriptions", $this->description ?? "");
        $this->extraData = $plugin->getEnchantmentData($this->name, "extra_data", $this->getDefaultExtraData());
        foreach ($this->getDefaultExtraData() as $key => $value) {
            if (!isset($this->extraData[$key])) {
                $this->extraData[$key] = $value;
                $plugin->setEnchantmentData($this->name, "extra_data", $this->extraData);
            }
        }
        parent::__construct($id, $this->name, $this->rarity, self::SLOT_ALL, self::SLOT_ALL, $this->maxLevel);
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function getDefaultExtraData(): array
    {
        return [];
    }

    public function getUsageType(): int
    {
        return $this->usageType;
    }

    public function getItemType(): int
    {
        return $this->itemType;
    }

    public function getPriority(): int
    {
        return 1;
    }

    public function canReact(): bool
    {
        return false;
    }

    public function canTick(): bool
    {
        return false;
    }

    public function canToggle(): bool
    {
        return false;
    }

    public function getCooldown(Player $player): int
    {
        return ($this->cooldown[$player->getName()] ?? time()) - time();
    }

    public function setCooldown(Player $player, int $coolDown): void
    {
        $this->cooldown[$player->getName()] = time() + $coolDown;
    }

    public function unregister(): void
    {
    }
}

