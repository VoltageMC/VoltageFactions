<?php

namespace xSuper\Core;

use pocketmine\{block\Block,
    event\block\BlockBreakEvent,
    event\entity\EntityArmorChangeEvent,
    event\entity\EntityDamageByEntityEvent,
    event\entity\EntityDamageEvent,
    event\entity\EntityEffectAddEvent,
    event\entity\EntityInventoryChangeEvent,
    event\entity\EntityShootBowEvent,
    event\entity\ProjectileHitBlockEvent,
    event\entity\ProjectileLaunchEvent,
    event\inventory\InventoryTransactionEvent,
    event\Listener,
    event\player\PlayerDeathEvent,
    event\player\PlayerInteractEvent,
    event\player\PlayerItemHeldEvent,
    event\player\PlayerJoinEvent,
    event\player\PlayerMoveEvent,
    event\player\PlayerQuitEvent,
    event\server\DataPacketReceiveEvent,
    event\server\DataPacketSendEvent,
    inventory\transaction\action\SlotChangeAction,
    item\Armor,
    item\Item,
    item\ItemIds,
    network\mcpe\protocol\ContainerClosePacket,
    network\mcpe\protocol\InventoryContentPacket,
    network\mcpe\protocol\InventorySlotPacket,
    network\mcpe\protocol\InventoryTransactionPacket,
    network\mcpe\protocol\MobEquipmentPacket,
    Player,
    utils\TextFormat};
use xSuper\Core\Enchants\ReactiveEnchantment;
use xSuper\Core\Enchants\ToggleableEnchantment;
use xSuper\Core\Menus\ChestMenu;
use xSuper\Core\Menus\DoubleChestMenu;
use xSuper\Core\Utils\ProjectileTracker;
use xSuper\Core\Utils\EnchantUtils;

class EventListener implements Listener
{
    /** @var Loader */
    private $plugin;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     */

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        ReactiveEnchantment::attemptReaction($player, $event);
    }

    /**
     * @priority HIGHEST
     * @param DataPacketReceiveEvent $event
     */

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if ($packet instanceof InventoryTransactionPacket) {
            foreach ($packet->actions as $key => $action) {
                EnchantUtils::filterDisplayedEnchants($action->oldItem);
                EnchantUtils::filterDisplayedEnchants($action->newItem);
                $packet->actions[$key] = $action;
            }
            if (isset($packet->trData->itemInHand)) {
                EnchantUtils::filterDisplayedEnchants($packet->trData->itemInHand);
            }
        }
        if ($packet instanceof MobEquipmentPacket) {
            EnchantUtils::filterDisplayedEnchants($packet->item);
        } else if ($packet instanceof ContainerClosePacket) {
            if (isset(ChestMenu::$awaitingInventoryClose[$player->getName()])) {
                ChestMenu::$awaitingInventoryClose[$player->getName()]->send($player);
                unset(ChestMenu::$awaitingInventoryClose[$player->getName()]);
            } else  if (isset(DoubleChestMenu::$awaitingInventoryClose[$player->getName()])) {
                DoubleChestMenu::$awaitingInventoryClose[$player->getName()]->send($player);
                unset(DoubleChestMenu::$awaitingInventoryClose[$player->getName()]);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param DataPacketSendEvent $event
     */

    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        $packet = $event->getPacket();
        if ($packet instanceof InventorySlotPacket) {
            EnchantUtils::displayEnchants($packet->item);
        }
        if ($packet instanceof InventoryContentPacket) {
            foreach ($packet->items as $key => $item) {
                $packet->items[$key] = EnchantUtils::displayEnchants($item);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityArmorChangeEvent $event
     */

    public function onArmorChange(EntityArmorChangeEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $oldItem = $event->getOldItem();
            $newItem = $event->getNewItem();
            $inventory = $entity->getArmorInventory();
            $slot = $event->getSlot();
            if ($oldItem->equals($newItem, false, true)) return;
            foreach ($oldItem->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($entity, $oldItem, $enchantmentInstance, $inventory, $slot, false);
            foreach ($newItem->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($entity, $newItem, $enchantmentInstance, $inventory, $slot);
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     */

    public function onDamage(EntityDamageEvent $event): void
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            $attacker = $event->getDamager();
            if ($attacker instanceof Player) {
                ReactiveEnchantment::attemptReaction($attacker, $event);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     */

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        ReactiveEnchantment::attemptReaction($player, $event);
        if ($this->plugin->getConfig()->getNested("miscellaneous.armor-hold-equip", false) && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR) {
            if ($item instanceof Armor || $item->getId() === Item::ELYTRA || $item->getId() === Item::PUMPKIN || $item->getId() === Item::SKULL) {
                $slot = 0;
                if (EnchantUtils::isChestplate($item)) $slot = 1;
                if (EnchantUtils::isLeggings($item)) $slot = 2;
                if (EnchantUtils::isBoots($item)) $slot = 3;
                $player->getInventory()->setItemInHand($player->getArmorInventory()->getItem($slot));
                $player->getArmorInventory()->setItem($slot, $item);
                $event->setCancelled();
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param ProjectileLaunchEvent $event
     */

    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $projectile = $event->getEntity();
        $shooter = $projectile->getOwningEntity();
        if ($shooter instanceof Player) {
            ProjectileTracker::addProjectile($projectile, $shooter->getInventory()->getItemInHand());
        }
    }

    /**
     * @priority HIGHEST
     * @param ProjectileHitBlockEvent $event
     */

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $projectile = $event->getEntity();
        $shooter = $projectile->getOwningEntity();
        if ($shooter instanceof Player) {
            ReactiveEnchantment::attemptReaction($shooter, $event);
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerQuitEvent $event
     */

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if ($player->isClosed()) {
            foreach ($player->getInventory()->getContents() as $slot => $content) {
                foreach ($content->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $content, $enchantmentInstance, $player->getInventory(), $slot, false);
            }
            foreach ($player->getArmorInventory()->getContents() as $slot => $content) {
                foreach ($content->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $content, $enchantmentInstance, $player->getArmorInventory(), $slot, false);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     */

    public function onMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        if (!EnchantUtils::shouldTakeFallDamage($player)) {
            if ($player->getLevel()->getBlock($player->floor()->subtract(0, 1))->getId() !== Block::AIR && EnchantUtils::getNoFallDamageDuration($player) <= 0) {
                EnchantUtils::setShouldTakeFallDamage($player, true);
            } else {
                EnchantUtils::increaseNoFallDamageDuration($player);
            }
        }
        if ($event->getFrom()->floor()->equals($event->getTo()->floor())) {
            return;
        }
        ReactiveEnchantment::attemptReaction($player, $event);
    }

    /**
     * @priority HIGHEST
     * @param PlayerItemHeldEvent $event
     */

    public function onItemHold(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $player->getInventory();
        $oldItem = $inventory->getItemInHand();
        $newItem = $event->getItem();
        foreach ($oldItem->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $oldItem, $enchantmentInstance, $inventory, $inventory->getHeldItemIndex(), false);
        foreach ($newItem->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $newItem, $enchantmentInstance, $inventory, $inventory->getHeldItemIndex());
    }

    /**
     * @priority HIGHEST
     * @param PlayerJoinEvent $event
     */

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        foreach ($player->getInventory()->getContents() as $slot => $content) {
            foreach ($content->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $content, $enchantmentInstance, $player->getInventory(), $slot);
        }
        foreach ($player->getArmorInventory()->getContents() as $slot => $content) {
            foreach ($content->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($player, $content, $enchantmentInstance, $player->getArmorInventory(), $slot);
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityEffectAddEvent $event
     */

    public function onEffectAdd(EntityEffectAddEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            ReactiveEnchantment::attemptReaction($entity, $event);
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerDeathEvent $event
     */

    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        ReactiveEnchantment::attemptReaction($player, $event);
    }

    /**
     * @priority HIGHEST
     * @param EntityShootBowEvent $event
     */

    public function onShootBow(EntityShootBowEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            ReactiveEnchantment::attemptReaction($entity, $event);
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityInventoryChangeEvent $event
     */

    public function onInventoryChange(EntityInventoryChangeEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $oldItem = $event->getOldItem();
            $newItem = $event->getNewItem();
            $inventory = $entity->getInventory();
            $slot = $event->getSlot();
            if ($newItem->getId() === Item::AIR) {
                foreach ($oldItem->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($entity, $oldItem, $enchantmentInstance, $inventory, $slot, false);
            }
            if ($oldItem->getId() === Item::AIR) {
                foreach ($newItem->getEnchantments() as $enchantmentInstance) ToggleableEnchantment::attemptToggle($entity, $newItem, $enchantmentInstance, $inventory, $slot);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param InventoryTransactionEvent $event
     */

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $actions = array_values($transaction->getActions());
        if (count($actions) === 2) {
            $allowed = array(270, 274, 257, 285, 278, 298, 302, 306, 314, 310, 299, 303, 307, 315, 311, 301, 305, 309, 317, 313, 272, 268, 267, 283, 276);
            foreach ($actions as $i => $action) {
                if ($action instanceof SlotChangeAction && ($otherAction = $actions[($i + 1) % 2]) instanceof SlotChangeAction && ($itemClickedWith = $action->getTargetItem())->getNamedTag()->hasTag("energyOrb") && ($itemClicked = $action->getSourceItem())->getNamedTag()->hasTag("energyOrb")) {
                    $energy = $itemClickedWith->getNamedTag()->getInt("energyOrb") + $itemClicked->getNamedTag()->getInt("energyOrb");
                    $event->setCancelled();
                    $itemClicked = Loader::getInstance()->giveEnergyOrb($energy, $transaction->getSource());
                    $action->getInventory()->setItem($action->getSlot(), $itemClicked);
                    $otherAction->getInventory()->setItem($otherAction->getSlot(), Item::get(Item::AIR));
                } else if ($action instanceof SlotChangeAction && ($otherAction = $actions[($i + 1) % 2]) instanceof SlotChangeAction && ($itemClickedWith = $action->getTargetItem())->getNamedTag()->hasTag("energyOrb") && in_array($itemClicked = $action->getSourceItem()->getId(), $allowed)) {
                    if ($action->getSourceItem()->getNamedTag()->hasTag("energy")) {
                        $energy = $itemClickedWith->getNamedTag()->getInt("energyOrb") + $action->getSourceItem()->getNamedTag()->getInt("energy");
                    } else {
                        $energy = $itemClickedWith->getNamedTag()->getInt("energyOrb");
                    }
                    $event->setCancelled();
                    $pickaxe = array(270, 274, 257, 285, 278);
                    if (in_array($action->getSourceItem()->getID(), $pickaxe)) {
                        $action->getSourceItem()->getNamedTag()->setInt("energy", $energy);
                        $action->getInventory()->setItem($action->getSlot(), $action->getSourceItem());
                        $otherAction->getInventory()->setItem($otherAction->getSlot(), Item::get(Item::AIR));
                    } else {
                        $item = $action->getSourceItem();
                        $item->getNamedTag()->setInt("energy", $energy);
                        $item->setLore([
                           TextFormat::RESET . "",
                           TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Cosmic Energy",
                           TextFormat::RESET . TextFormat::WHITE . number_format($energy, 0, ".", ",")
                        ]);
                        $action->getInventory()->setItem($action->getSlot(), $item);
                        $otherAction->getInventory()->setItem($otherAction->getSlot(), Item::get(Item::AIR));
                    }
                } else if ($action instanceof SlotChangeAction && ($otherAction = $actions[($i + 1) % 2]) instanceof SlotChangeAction && ($itemClickedWith = $action->getTargetItem())->getId() === ItemIds::ENCHANTED_BOOK && ($itemClicked = $action->getSourceItem())->getId() !== ItemIds::AIR) {
                    if (count($itemClickedWith->getEnchantments()) < 1) return;
                    $enchantmentSuccessful = false;
                    foreach ($itemClickedWith->getEnchantments() as $enchantment) {
                        if (!EnchantUtils::canBeEnchanted($itemClicked, $enchantment->getType(), $enchantment->getLevel())) continue;
                        if ($itemClicked->getNamedTag()->hasTag("maxce")) {
                            $limit = $itemClicked->getNamedTag()->getInt("maxce");
                        } else {
                            $limit = 5;
                        }
                        if ($limit < 5) $limit = 5;
                        if (count($itemClicked->getEnchantments()) === 0) {
                            $itemClicked->addEnchantment($enchantment);
                            $action->getInventory()->setItem($action->getSlot(), $itemClicked);
                            $enchantmentSuccessful = true;
                        } else {
                            foreach ($itemClicked->getEnchantments() as $enchantment2) {
                                if ($enchantment2->getId() == $enchantment->getId() && $enchantment->getLevel() > $enchantment2->getLevel()) {
                                    $itemClicked->addEnchantment($enchantment);
                                    $action->getInventory()->setItem($action->getSlot(), $itemClicked);
                                    $enchantmentSuccessful = true;
                                } elseif (count($itemClicked->getEnchantments()) < $limit) {
                                    $itemClicked->addEnchantment($enchantment);
                                    $action->getInventory()->setItem($action->getSlot(), $itemClicked);
                                    $enchantmentSuccessful = true;
                                }
                            }
                        }
                    }
                    if ($enchantmentSuccessful) {
                        $event->setCancelled();
                        $otherAction->getInventory()->setItem($otherAction->getSlot(), Item::get(Item::AIR));
                    }
                }
            }
        }
    }
}
