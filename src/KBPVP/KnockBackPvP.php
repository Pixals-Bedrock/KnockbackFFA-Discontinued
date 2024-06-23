<?php

namespace KBPVP;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\item\ItemFactory;
use pocketmine\utils\Config;
use pocketmine\block\BlockFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\types\NetworkSession;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\EnderPearl;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;


class KnockBackPvP extends PluginBase implements Listener
{

    public $cooldown = [];
    public $scoreboards = [];
    public $kill = [];
    public $topkills = [];

    public function onEnable() : void 
    {
        @mkdir($this->getDataFolder() . "kbpvpdata/");
        $this->kills = new Config($this->getDataFolder() . "kbpvpdata/kills.yml", 2);
        $this->deaths = new Config($this->getDataFolder() . "kbpvpdata/deaths.yml", 2);
        $this->points = new Config($this->getDataFolder() . "kbpvpdata/points.yml", 2);
        $this->perks = new Config($this->getDataFolder() . "kbpvpdata/perks.yml", 2);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("§aENABLED | UTRIXKBPVP");
        // $this->getScheduler()->scheduleRepeatingTask(new KnockTask($this), 20 * 5);
    }

    public function onDisable() : void 
    {
        $this->getLogger()->info("§cDisabled | UtriXKBPvP");
    }

    public function new(Player $player, string $objectiveName, string $displayName): void{
		if(isset($this->scoreboards[$player->getName()])){
			$this->remove($player);
		}
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 1;
		
		// $player->sendDataPacket($pk);
		$player->getNetworkSession()->sendDataPacket($pk);
		$this->scoreboards[$player->getName()] = $objectiveName;
	}

	public function remove(Player $player){
		$objectiveName = $this->getObjectiveName($player);
		$pk = new RemoveObjectivePacket();
		if($objectiveName == null || $objectiveName == ""){
			return true;
		} else {
			$pk->objectiveName = $objectiveName;
			$player->getNetworkSession()->sendDataPacket($pk);
			unset($this->scoreboards[$player->getName()]);
		}
	}

	public function setLine(Player $player, int $score, string $message): void{
		if(!isset($this->scoreboards[$player->getName()])){
			$this->getLogger()->error("Cannot set a score to a player with no scoreboard");
			return;
		}
		
		$objectiveName = $this->getObjectiveName($player);
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
	}
    public function getObjectiveName(Player $player): ?string{
		return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
	}

    public function KnockBackPvPScoreboard(Player $player)
    {

        $utrix = $this->getServer()->getPluginManager()->getPlugin("UtriXCore");
        $utrix->config = new Config($utrix->getDataFolder() . "playerranks/" . strtolower($player->getName()) . ".yml", Config::YAML, array(
			"Name" => $player->getName(),
			"Rank" => "Member",
			"ChatLevel" => 0,
			"JoinMSG" => "NONE",
			"Reason_Of_Ban" => "NONE",
			"BAN_APPEAL_CODE" => "NONE",
			"UtriXFriends" => "NONE",
			"UtriXMutes" => "FALSE",
			"UCoins" => 0,
			"UEmeralds" => 0,
			"LeaveMSG" => "NONE",
		));
        $ucoins = $utrix->config->get("UCoins");
        $uemeralds = $utrix->config->get("UEmeralds");
        $kills = $this->kills->get($player->getName());
        $deaths = $this->deaths->get($player->getName());
        $points = $this->points->get($player->getName()); 
        $this->new($player, "KnockBackPvP", "§7» §l§4KnockBack§cPvP §r§7«");
        $this->setLine($player, 16, "");
        $this->setLine($player, 15, "§7» §fKills: §a" . $kills);
        $this->setLine($player, 13, "§7» §fDeaths: §4" . $deaths);
        $this->setLine($player, 11, "§7» §fGlourios Points: §3" . $points);
        $this->setLine($player, 9, "§7» §fUCoins: §e" . $ucoins);
        $this->setLine($player, 7, "§7» §fUEmeralds: §a" . $uemeralds);
        $this->setLine($player, 6, "       ");
        $this->setLine($player, 5, "§7» §fK.D: §6" . $kills . "." . $deaths);
        $this->setLine($player, 4, " ");
        $this->setLine($player, 3, "§7» §4mc.utrix.cloudns.nz");

    }


    public function KnockKit(Player $player)
    {
        $this->KnockBackPvPScoreboard($player);
        $knock_stick = ItemFactory::getInstance()->get(280, 0, 1);
        $bow = ItemFactory::getInstance()->get(261, 0, 1);
        $blocks = ItemFactory::getInstance()->get(236, 0, 64);
        $arrows = ItemFactory::getInstance()->get(VanillaItems::ARROW()->getId(), 0, 1);
        $knock_stick->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 2));
        $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 2));
        $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 2));

        $enderpearls = ItemFactory::getInstance()->get(368, 0, 2);

        $player->getInventory()->setItem(0, $knock_stick);
        $player->getInventory()->setItem(1, $blocks);
        $player->getInventory()->setItem(2, $enderpearls);
        $player->getInventory()->setItem(3, $bow);
        $player->getInventory()->setItem(8, $arrows);



        
    }

    


    public function removeBlocks(BlockPlaceEvent $e)
    {
        $player = $e->getPlayer();
        $block = $e->getBlock();
        
        $x = $block->getPosition()->getX();
        $y = $block->getPosition()->getY();
        $z = $block->getPosition()->getZ();
        $concrete = VanillaBlocks::CONCRETE();
        if($block instanceof $concrete)
        {
            if($player->getWorld()->getFolderName() == "kbpvp")
            {
                
                // $player->getInventory()->addItem(VanillaBlocks::CONCRETE()->asItem()->setCount(1));
                $this->getScheduler()->scheduleRepeatingTask(new KnockTask($this, $player, $block), 20);
            }
        }
    
    }



    public function TopKills(){
        $ranking = 1;
        $kills = $this->kills->getAll();
        $datacount = count($kills);
        if($datacount > 0){
            arsort($kills);
            $msg = "";
            foreach($kills as $name => $amount)
            {
                if($ranking <= 10){
                    if($amount > 10){
                        $msg .= "§l§6#$ranking §a$name §cHas Kills Of §b$amount\n";
                        $ranking++;
                    }
                }
            }
        }
        return $msg;
    }
    
    

    public function TopKillsGUI(Player $player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null){

        });


        $form->setTitle("§4KnockBack§cPvP §7 | §4TopKills");
        $form->setContent($this->TopKills());
        $form->addButton("§7» §cBack");
        $form->sendToPlayer($player);
        return $form;
    }


    public function onJoinDataBase(PlayerJoinEvent $e)
    {
        $player = $e->getPlayer();
        /**$this->kbpvpdata = new Config($this->getDataFolder() . "kbpvpdata/" . strtolower($player->getName()) . ".yml", Config::YAML, array(
			"Name" => $player->getName(),
			"Kills" => 0,
            "Deaths" => 0,
            "Points" => 0,
            "KillerNow" => "NONE",
		));**/

        $this->kills->set($player->getName(), $this->kills->get($player->getName(), 0) + 0);
        $this->deaths->set($player->getName(), $this->deaths->get($player->getName(), 0) + 0);
        $this->points->set($player->getName(), $this->points->get($player->getName(), 0) + 0);
        $this->perks->set($player->getName(), $this->perks->get($player->getName(), 0) . "NONE");
        $this->kills->save();
        $this->points->save();
        $this->deaths->save();
        $this->perks->save();
    }

    public function onQuit(PlayerQuitEvent $e){
        $player = $e->getPlayer();

    }


    public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args) : bool{


        if($cmd->getName() == "topkillskb")
        {
            if($sender->getWorld()->getFolderName() == "kbpvp"){
                $this->TopKillsGUI($sender);
            }
        }


        return true;
    }



    



    public function Kills(EntityDamageByEntityEvent $e)
    {
        $entity = $e->getEntity();
        
        
        
        if($entity instanceof Player)
        {
            if($entity->getWorld()->getFolderName() == "kbpvp")
            {
                $killer = $e->getDamager();
                $entity->setHealth(20);
                $killer->setHealth(20);
                $e->setKnockback(0.9);
                
                $this->kill[$entity->getName()] = $killer;
                if($entity->getHealth() <= 2)
                {
                    
                    
                    
                    $entity->setHealth(20);
                    $killer->setHealth(20);
                    $entity->setHealth(20);
                    $entity->teleport($this->getServer()->getWorldManager()->getWorldByName("kbpvp")->getSafeSpawn());
                    $this->deaths->set($entity->getName(), $this->deaths->get($entity->getName(), 0) + 1);
                    $this->deaths->save();
                    
                   foreach($this->getServer()->getWorldManager()->getWorldByName("kbpvp")->getPlayers() as $p)
                    {
                        $p->sendMessage("§7| §4KnockBack§cPvP §7» §a" . $killer->getName() . " §3has Killed §c" . $entity->getName());
                    }
                    
                    if($killer instanceof Player)
                    {
                        $this->kbpvpdata = new Config($this->getDataFolder() . "kbpvpdata/" . strtolower($killer->getName()) . ".yml", Config::YAML, array(
                            "Name" => $killer->getName(),
                            "Kills" => 0,
                            "Deaths" => 0,
                            "Points" => 0,
                        ));
                        $this->kills->set($killer->getName(), $this->kills->get($killer->getName(), 0) + 1);
                        $this->points->set($killer->getName(), $this->points->get($killer->getName(), 0) + 1);
                        $this->kills->save();
                        $this->points->save();
                        $utrix = $this->getServer()->getPluginManager()->getPlugin("UtriXCore");
                        $utrix->config = new Config($utrix->getDataFolder() . "playerranks/" . strtolower($killer->getName()) . ".yml", Config::YAML, array(
                            "Name" => $killer->getName(),
                            "Rank" => "Member",
                            "ChatLevel" => 0,
                            "JoinMSG" => "NONE",
                            "Reason_Of_Ban" => "NONE",
                            "BAN_APPEAL_CODE" => "NONE",
                            "UtriXFriends" => "NONE",
                            "UtriXMutes" => "FALSE",
                            "UCoins" => 0,
                            "UEmeralds" => 0,
                            "LeaveMSG" => "NONE",
                        ));
                        $ender = ItemFactory::getInstance()->get(368, 0, 1);
                        $killer->getInventory()->addItem($ender);
                        $killer->getInventory()->addItem(VanillaBlocks::CONCRETE()->asItem()->setCount(64));

                        $utrix->config->set("UCoins", $utrix->config->get("UCoins") + 1);
                        $this->kbpvpdata->save();
                        $utrix->config->save();
                    }
                }


            }
        }
    }

    

    public function ProtectArea(EntityDamageEvent $e)
    {
        $player = $e->getEntity();
        $cause = $e->getCause();
        
        if($player instanceof Player)
        {
            if($cause == EntityDamageEvent::CAUSE_FALL)
            {
                $e->cancel();
            }

            

            if($cause == EntityDamageEvent::CAUSE_VOID)
            {
                // echo "worked";
                
                if($player->getWorld()->getFolderName() == "kbpvp")
                {
                    if(isset($this->kill[$player->getName()]))
                    {
                        $e->cancel();
                        $killer = $this->kill[$player->getName()];
                        $player->teleport($this->getServer()->getWorldManager()->getWorldByName("kbpvp")->getSafeSpawn());
                        $this->deaths->set($player->getName(), $this->deaths->get($player->getName(), 0) + 1);
                        $this->deaths->save();

                        

                        $this->kills->set($killer->getName(), $this->kills->get($killer->getName(), 0) + 1);
                        $this->points->set($killer->getName(), $this->points->get($killer->getName(), 0) + 1);
                        $this->kills->save();
                        $this->points->save();
                        foreach($this->getServer()->getWorldManager()->getWorldByName("kbpvp")->getPlayers() as $p)
                        {
                            $p->sendMessage("§7| §4KnockBack§cPvP §7» §e" . $killer->getName() . " §3has Killed §c" . $player->getName());
                        }
                        $this->KnockBackPvPScoreboard($killer);
                        $this->KnockBackPvPScoreboard($player);
                        $ender = ItemFactory::getInstance()->get(368, 0, 1);
                        $killer->getInventory()->addItem($ender);
                        $killer->getInventory()->addItem(VanillaBlocks::CONCRETE()->asItem()->setCount(64));
                        unset($this->kill[$player->getName()]); 
                
                    } else {
                        $this->deaths->set($player->getName(), $this->deaths->get($player->getName(), 0) + 1);
                        $this->deaths->save();
                        $e->cancel();
                        $player->teleport($this->getServer()->getWorldManager()->getWorldByName("kbpvp")->getSafeSpawn());
                        $player->sendPopup("§7| §4KnockBack§cPvP §7» §cYou Died");
                        $this->KnockBackPvPScoreboard($player);
                    }
                }
               
                
            }   
    }
}




    



}