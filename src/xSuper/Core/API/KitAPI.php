<?php

namespace xSuper\Core\API;

use InvalidArgumentException;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xSuper\Core\Loader;
use function array_chunk;
use function array_shift;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function floor;
use function in_array;
use function is_array;
use function is_numeric;
use function serialize;
use function str_replace;
use function strtolower;
use function unserialize;

class KitAPI{

    /** @var KitAPI[] */
    public $kits = [];
    /** @var string[] */
    public $hasKit = [];
    /** @var bool */
    public $permissionsMode;
    /** @var self */
    public static $instance;

    private $ak;
    private $data;
    private $name;
    private $coolDown;
    public $coolDowns = [];

    /** @var Item[] */
    private $items = [];
    /** @var Item[] */
    private $armor = ['helmet' => null, 'chestplate' => null, 'leggings' => null, 'boots' => null];
    /** @var Item[] */
    private $slots = [];
    /** @var EffectInstance[] */
    private $effects = [];

    /** @var  int */
    private $imgType;
    /** @var  string */
    private $imgData;
    /** @var string */
    private $formName;

    public function __construct(Loader $ak, array $data, string $name){
        $this->ak = $ak;
        $this->data = $data;
        $this->name = $name;
        $this->coolDown = $this->getCoolDownMinutes();

        if(file_exists($this->ak->getDataFolder().'cooldowns/'.strtolower($this->name).'.sl')){
            $this->coolDowns = unserialize(file_get_contents($this->ak->getDataFolder().'cooldowns/'.strtolower($this->name).'.sl'), ['allowed_classes' => false]);
        }

        foreach($this->data['items'] as $itemString){
            $item = $this->loadItem($itemString);
            if($item !== null){
                $this->items[] = $item;
            }
        }
        isset($this->data['helmet']) && ($this->armor['helmet'] = $this->loadItem($this->data['helmet']));
        isset($this->data['chestplate']) && ($this->armor['chestplate'] = $this->loadItem($this->data['chestplate']));
        isset($this->data['leggings']) && ($this->armor['leggings'] = $this->loadItem($this->data['leggings']));
        isset($this->data['boots']) && ($this->armor['boots'] = $this->loadItem($this->data['boots']));

        if(isset($this->data['slots']) && is_array($this->data['slots'])){
            foreach($this->data['slots'] as $index => $itemString){
                $item = $this->loadItem($itemString);
                if($item !== null){
                    $this->slots[$index] = $item;
                }
            }
        }

        if(isset($this->data['effects']) && is_array($this->data['effects'])){
            foreach($this->data['effects'] as $effectString){
                $effect = $this->loadEffect($effectString);
                if($effect !== null){
                    $this->effects[] = $effect;
                }
            }
        }

        if(isset($data['img-type'])){
            if($data['img-type'] === 'url'){
                $this->imgType = 1;
            }elseif($data['img-type'] === 'path'){
                $this->imgType = 0;
            }else{
                $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Image type '.$data['img-type'].' not supported. Please use \'path\' or \'url\'');
            }
        }
        if(isset($data['img-data'])){
            $this->imgData = $data['img-data'];
        }
        if(isset($data['form-name'])){
            $this->formName = $data['form-name'];
        }
    }

    public function getName() : string{
        return $this->name;
    }

    public function handleRequest(Player $player) : bool
    {
        if ($this->testPermission($player)) {
            if (!isset($this->coolDowns[$player->getLowerCaseName()])) {
                if (!($this->ak->getConfig()->get('one-kit-per-life') && isset($this->hasKit[strtolower($player->getName())]))) {
                    $this->addTo($player);
                    $player->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "> " . TextFormat::RESET . TextFormat::GRAY . "You have claimed the " . $this->name . " kit!");
                    return true;
                }
            } else {
                $player->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "> " . TextFormat::RESET . TextFormat::GRAY . "Your " . $this->name . " kit is on cooldown for " . $this->getCoolDownLeft($player) . "!");
            }
        } else {
            $player->sendMessage(TextFormat::BOLD . TextFormat::AQUA . "> " . TextFormat::RESET . TextFormat::GRAY . "Sorry, you dont not have permission to use the " . $this->name . " kit!");
        }
        return false;
    }

    public function addTo(Player $player) : void{
        foreach($this->items as $item){
            $player->getInventory()->addItem($item);
        }

        $this->armor['helmet'] !== null && $player->getArmorInventory()->setHelmet($this->armor['helmet']);
        $this->armor['chestplate'] !== null && $player->getArmorInventory()->setChestplate($this->armor['chestplate']);
        $this->armor['leggings'] !== null && $player->getArmorInventory()->setLeggings($this->armor['leggings']);
        $this->armor['boots'] !== null && $player->getArmorInventory()->setBoots($this->armor['boots']);

        foreach($this->slots as $slot => $item){
            $player->getInventory()->setItem($slot, $item);
        }

        foreach($this->effects as $effect){
            $player->addEffect(clone $effect);
        }

        if(isset($this->data['commands']) && is_array($this->data['commands'])){
            foreach($this->data['commands'] as $cmd){
                $this->ak->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace('{player}', $player->getName(), $cmd));
            }
        }
        if($this->coolDown){
            $this->coolDowns[$player->getLowerCaseName()] = $this->coolDown;
        }
        $this->hasKit[$player->getLowerCaseName()] = $this->name;
    }


    private function loadItem(string $itemString) : ?Item{
        $array = explode(':', $itemString);
        if(count($array) < 2){
            $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Item '.$itemString.' could not be loaded because name and damage are not specified');
            return null;
        }

        $name = array_shift($array);
        $damage = array_shift($array);
        try{
            $item = Item::fromString($name.':'.$damage);
        }catch(InvalidArgumentException $exception){
            $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Item '.$itemString.' could not be loaded');
            $this->ak->getLogger()->warning($exception->getMessage());
            return null;
        }

        if(!empty($array)){
            $count = array_shift($array);
            if(is_numeric($count)){
                $item->setCount((int) $count);
            }else{
                $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Item '.$itemString.' could not be loaded because the count is not a number');
                return null;
            }
        }

        if(!empty($array)){
            $name = array_shift($array);
            if(strtolower($name) !== 'default'){
                $item->setCustomName($name);
            }
        }

        if(!empty($array)){
            $enchantmentsArrays = array_chunk($array, 2);
            foreach ($enchantmentsArrays as $enchantmentsData){
                if(count($enchantmentsData) !== 2){
                    $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Enchantments must be specified in the format name:level. Enchantment: '.$enchantmentsData[0].' will not be included in the item '.$itemString);
                    continue;
                }

                $enchantment = Enchantment::getEnchantmentByName($enchantmentsData[0]);
                if($enchantment === null){ //If the specified enchantment is not a vanilla enchantment
                    if($this->ak !== null){
                        $enchantment = EnchantAPI::getEnchantmentByName($enchantmentsData[0]);
                        if($enchantment === null){ //If the specified enchantment is not a custom enchantment
                            $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Enchantment '.$enchantmentsData[0].' in item '.$itemString.' could not be loaded because the enchantment does not exist');
                            continue;
                        }
                    }else{
                        $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Enchantment '.$enchantmentsData[0].' in item '.$itemString.' could not be loaded because the enchantment does not exist');
                        continue;
                    }
                }

                if(!is_numeric($array[1])){
                    $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Enchantment '.$enchantmentsData[0].' in item '.$itemString.' could not be loaded because the level is not a number');
                    continue;
                }

                $item->addEnchantment(new EnchantmentInstance($enchantment, (int) $enchantmentsData[1]));
            }
        }
        return $item;
    }

    private function loadEffect(string $effectString) : ?EffectInstance{
        $array = explode(':', $effectString);
        if(count($array) < 2){
            $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Effect '.$effectString.' could not be loaded because name and level are not specified');
            return null;
        }
        $name = array_shift($array);
        $duration = array_shift($array);
        if(!is_numeric($duration)){
            $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Effect '.$effectString.' could not be loaded because the duration is not a number');
            return null;
        }

        if(!empty($array)){
            $amplifier = array_shift($array);
            if(!is_numeric($amplifier)){
                $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Effect '.$effectString.' could not be loaded because the amplifier is not a number');
                return null;
            }
        }else{
            $amplifier = 0;
        }

        $e = Effect::getEffectByName($name);
        if($e === null){
            $this->ak->getLogger()->warning('Bad configuration in kit '.$this->name.'. Effect '.$effectString.' could not be loaded because the effect does not exist');
            return null;
        }
        return new EffectInstance($e, (int) $duration * 20, (int) $amplifier);
    }

    public function getCoolDownMinutes() : int{
        $min = 0;
        if(isset($this->data['cooldown']['minutes'])){
            $min += (int) $this->data['cooldown']['minutes'];
        }
        if(isset($this->data['cooldown']['hours'])){
            $min += (int) $this->data['cooldown']['hours'] * 60;
        }
        return $min;
    }

    public function getCoolDownLeft(Player $player) : string{
        if(($minutes = $this->coolDowns[$player->getLowerCaseName()]) < 60){
            return ($minutes . 'm');
        }
        if(($modulo = $minutes % 60) !== 0){
            return (floor($minutes / 60) . 'h ' . $modulo . 'm');
        }
        return ($minutes / 60 . 'h');
    }

    public function processCoolDown() : void{
        foreach($this->coolDowns as $player => $min){
            --$this->coolDowns[$player];
            if($this->coolDowns[$player] <= 0){
                unset($this->coolDowns[$player]);
            }
        }
    }

    public function testPermission(Player $player) : bool{
        if($this->permissionsMode){
            return $player->hasPermission(strtolower($this->name) . ".customkits") || $player->hasPermission($this->name .".customkits");
        }

        return
            (isset($this->data['users']) ? in_array($player->getLowerCaseName(), $this->data['users'], true) : true)
            &&
            (isset($this->data['worlds']) ? in_array(strtolower($player->getLevel()->getName()), $this->data['worlds'], true) : true)
            ;
    }

    public function loadKits() : void{
        Loader::getInstance()->saveResource('kits.yml');
        $kitsData = yaml_parse_file(Loader::getInstance()->getDataFolder().'kits.yml');
        $this->fixConfig($kitsData);
        foreach($kitsData as $kitName => $kitData){
            $this->kits[$kitName] = [$this, $kitData, $kitName];
        }
    }

    private function fixConfig(array &$config) : void{
        foreach($config as $name => $kit){
            if(isset($kit['users'])){
                $users = array_map('strtolower', $kit['users']);
                $config[$name]['users'] = $users;
            }
            if(isset($kit['worlds'])){
                $worlds = array_map('strtolower', $kit['worlds']);
                $config[$name]['worlds'] = $worlds;
            }
        }
    }

    /**
     * @param string $kitName
     * @return KitAPI|null
     */
    public function getKit(string $kitName) : ?KitAPI{
        /**@var KitAPI[] $lowerKeys*/
        $lowerKeys = array_change_key_case($this->kits, CASE_LOWER);
        if(isset($lowerKeys[strtolower($kitName)])){
            return $lowerKeys[strtolower($kitName)];
        }
        return null;
    }

    /**
     * @param $player
     * @return string|null
     */
    public function getPlayerKit($player) : ?string{
        if($player instanceof Player){
            $player = $player->getLowerCaseName();
        }else{
            $player = strtolower($player);
        }
        return $this->hasKit[$player] ?? null;
    }

    public function save() : void{
        if(!empty($this->coolDowns)){
            file_put_contents($this->ak->getDataFolder().'cooldowns/'.strtolower($this->name).'.sl', serialize($this->coolDowns));
        }
    }

    public static function getInstance(): KitAPI
    {
        return self::$instance;
    }
}
