<?php

namespace xSuper\Core\Tasks;

use xSuper\Core\Loader;
use pocketmine\scheduler\Task;

class KitCooldownTask extends Task{

    private $plugin;

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(int $tick) : void{
        foreach($this->plugin->kits as $kit){
            $kit->processCoolDown();
        }
    }

}
