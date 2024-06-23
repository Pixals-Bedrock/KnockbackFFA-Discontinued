<?php

namespace KBPVP;

use KBPVP\KnockBackPvP;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Concrete;
use pocketmine\event\BlockPlaceEvent;

class KnockTask extends Task
{

    public $time = 5;

    public function __construct(KnockBackPvP $plugin, $player, Concrete $block)
    {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->block = $block;
    }

    public function onRun(): void
    {
       

        if($this->time == 0)
        {
            foreach($this->plugin->getServer()->getWorldManager()->getWorldByName("kbpvp")->getPlayers() as $p)
            {
                
                $this->player->getWorld()->setBlockAt($this->block->getPosition()->getX(), $this->block->getPosition()->getY(), $this->block->getPosition()->getZ(), VanillaBlocks::AIR());

            }
            $this->getHandler()->cancel();

        }
        $this->time--;
        
        // TODO: Implement onRun() method.
    }
}