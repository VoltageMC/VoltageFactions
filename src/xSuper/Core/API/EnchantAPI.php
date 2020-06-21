<?php

declare(strict_types=1);

namespace xSuper\Core\API;

use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\utils\Config;
use ReflectionException;
use ReflectionProperty;
use SplFixedArray;
use pocketmine\entity\Entity;
use xSuper\Core\Enchants\CustomEnchant;
use xSuper\Core\Enchants\EnchantIds;
use xSuper\Core\Loader;

class EnchantAPI
{
    /** @var Loader */
    private static $plugin;
    /** @var array[] */
    public $enchantmentData;

    /** @var self */
    public static $instance;

    /** @var CustomEnchant[] */
    public static $enchants = [];

    /**
     * @param Loader $plugin
     * @throws ReflectionException
     */
    public static function init(Loader $plugin): void
    {
        self::$plugin = $plugin;
        $vanillaEnchantments = new SplFixedArray(1024);


        $property = new ReflectionProperty(Enchantment::class, "enchantments");
        $property->setAccessible(true);
        foreach ($property->getValue() as $key => $value) {
            $vanillaEnchantments[$key] = $value;
        }
        $property->setValue($vanillaEnchantments);
    }

    /**
     * @param string $enchant
     * @param string $data
     * @param int|string|array $default
     * @return mixed
     * @internal
     */
    public function getEnchantmentData(string $enchant, string $data, $default = "")
    {
        if (!isset($this->enchantmentData[str_replace(" ", "", strtolower($enchant))][$data])) $this->setEnchantmentData($enchant, $data, $default);
        return $this->enchantmentData[str_replace(" ", "", strtolower($enchant))][$data];
    }

    /**
     * @param string $enchant
     * @param string $data
     * @param int|string|array $value
     */
    public function setEnchantmentData(string $enchant, string $data, $value): void
    {
        $this->enchantmentData[str_replace(" ", "", strtolower($enchant))][$data] = $value;
        $config = new Config(Loader::getInstance()->getDataFolder() . $data . ".json");
        $config->set(str_replace(" ", "", strtolower($enchant)), $value);
        $config->save();
    }

    public static function getPlugin(): Loader
    {
        return self::$plugin;
    }

    public static function registerEnchantment(CustomEnchant $enchant): void
    {
        Enchantment::registerEnchantment($enchant);
        /** @var CustomEnchant $enchant */
        $enchant = Enchantment::getEnchantment($enchant->getId());
        self::$enchants[$enchant->getId()] = $enchant;

        self::$plugin->getLogger()->debug("Custom Enchantment '" . $enchant->getName() . "' registered with id " . $enchant->getId());
    }

    /**
     * @param int|Enchantment $id
     * @throws ReflectionException
     */
    public static function unregisterEnchantment($id): void
    {
        $id = $id instanceof Enchantment ? $id->getId() : $id;
        self::$enchants[$id]->unregister();
        self::$plugin->getLogger()->debug("Custom Enchantment '" . self::$enchants[$id]->getName() . "' unregistered with id " . self::$enchants[$id]->getId());
        unset(self::$enchants[$id]);

        $property = new ReflectionProperty(Enchantment::class, "enchantments");
        $property->setAccessible(true);
        $value = $property->getValue();
        unset($value[$id]);
        $property->setValue($value);
    }

    /**
     * @return CustomEnchant[]
     */
    public static function getEnchantments(): array
    {
        return self::$enchants;
    }

    public static function getEnchantment(int $id): ?CustomEnchant
    {
        return self::$enchants[$id] ?? null;
    }

    public static function getEnchantmentByName(string $name): ?CustomEnchant
    {
        foreach (self::$enchants as $enchant) {
            if (
                strtolower(str_replace(" ", "", $enchant->getName())) === strtolower(str_replace(" ", "", $name)) ||
                strtolower(str_replace(" ", "", $enchant->getDisplayName())) === strtolower(str_replace(" ", "", $name))
            ) return $enchant;
        }
        return null;
    }

    public static function getInstance(): EnchantAPI
    {
        return self::$instance;
    }
}

