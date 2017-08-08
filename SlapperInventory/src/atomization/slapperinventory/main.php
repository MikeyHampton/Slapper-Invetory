<?php


namespace atomization\slapperinventory;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\CustomInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseTransaction;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\Plugin;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag; 
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\tile\Tile;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as c;


class Main extends PluginBase implements Listener {
    
    
    public $mode=0;
    public $added=0;
	public $name="";
	public $prefix="§4[§2Slapper Inventory§4]§r";
    
    
    public function onLoad(){
        $this->getLogger()->info("§2Lodeing Slapper Inventory by Fidget-Dev");

    }
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->getLogger()->info("§2Slapper Inventory has been Enabled");
        @mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "Inventories.json", Config::JSON);
		$this->config->save();
    }
    
    public function onDisable(){
        $this->getLogger()->info("§2Slapper Inventory has been Disabled");
    }
    
    public function OnCommand(CommandSender $s, Command $cmd, $label, array $args){
		if(empty($args[0])){
			$s->sendMessage($this->prefix.c::RED."You Need help?: /slapperinv help");
		}
		if(!isset($args[0])){unset($sender,$cmd,$label,$args);return false;};
        if($s->isOp() and $s instanceof Player){
		switch($args[0]){
			case "help":
		        $s->sendMessage("§1<---------- $this->prefix §1---------->");
				$s->sendMessage("§c/slapperinv add <name>:§f Add the Selector");
				$s->sendMessage("§c/slapperinv additem <INVENTORYRNAME> <Item ID> <damage>:§f Add item in the Inventory");
				$s->sendMessage("§c/slapperinv removeitem <INVENTORYRNAME> <Item ID> <damage>:§f Remove item from the Inventory");
				$s->sendMessage("§c/slapperinv addcommand <INVENTORYRNAME> <Item ID> <Command(No /)>:§f Add Command to Inventory Items");
				$s->sendMessage("§1<---------- $this->prefix §1---------->");
				break;
			case "add":
			    if((!empty($args[1])) and !($this->config->getNested("Inventories.$args[1]"))){
					$this->name=$args[1];
					$this->config->save();
					$s->sendMessage($this->prefix."§7- §2Click on a slapper now!");
					$this->mode=1;
				}else{
					$s->sendMessage($this->prefix."§7- §2Usage: /slapperinv add <name>");
				}
				break;
		    case "additem":
			    if($this->config->getNested("Inventories.$args[1]") and (!empty($args[1])) and (!empty($args[2])) and $args[2]>=0){
					$pos=$this->config->getNested("Inventories.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$chest->getInventory()->addItem(Item::get($args[2]));
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::GOLD.$args[2]." §7- §2ID's item was added to ".$args[1]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."§7- §2Usage: /slapperinv additem <INVENTORYRNAME> <Item ID> <damage>");
				}
				break;
            case "removeitem":
            case "deleteitem":
			case "delitem":
			    if($this->config->getNested("Inventories.$args[1]") and (!empty($args[1])) and (!empty($args[3])) and (!empty($args[2])) and $args[2]>=0){
					$pos=$this->config->getNested("Inventories.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$chest->getInventory()->removeItem(Item::get($args[2]), $args[3]);
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::RED.$args[2]." §7- §2ID's item was removed from the ".$args[1]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."§7- §2Usage: /slapperinv removeitem <INVENTORYRNAME> <Item ID> <damage>");
				}
				break;
			case "addcommand":
			    if((!empty($args[1])) and (!empty($args[2])) and (!empty($args[3]))){
                                if($s instanceof Player){
                                    if($this->config->getNested("Inventories.$args[1]")){
                                        $co=$this->config->getNested("Inventories.$args[1]");
                                        $chest=$s->getLevel()->getTile(new Vector3($co["x"], $co["y"], $co["z"]));
                                        $cn=$chest->getName();
                                        $ag=$args[2];
                                        if(isset($args[3]["/"])){
                                            $s->sendMessage("Wrong command! No /");
                                        }else{
                                            array_shift($args);
                                            array_shift($args);
                                            array_shift($args);
                                            $command = trim(implode(" ", $args));
                                            $this->config->setNested("Inventories.$cn.Commands.$ag", $command);
                                            $this->config->save();
                                            $s->sendMessage($this->prefix."$cn §7- §2Added command to $ag ID item");
                                        }
                                    }
                                }
                            }else{
								$s->sendMessage($this->prefix.c::YELLOW."§7- §2Usage: /slapperinv addcommand <INVENTORYRNAME> <Item ID> <Command(No /)>");
							}
				break;
		     case "list":
			$selectors=$s->getLevel()->getTiles();
                         if($selectors instanceof Chest){
                             $cn=$selectors->getName();
                             if($this->config->getNested("Inventories.$cn")){              // NOT WORKING NOW!
                                 shuffle($cn);
			         $s->sendMessage(c::BLUE."<---------- $this->prefix LIST ---------->");
			         $s->sendMessage($cn);
			         $s->sendMessage(c::BLUE."<---------- $this->prefix LIST ---------->");
                             }
                         }
			 break;
                        }
		}else{
                    $s->sendMessage(c::RED."§7- §4You are not op!");
        }
	}
    
    public function OnDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$player=$event->getDamager();
			$entity=$event->getEntity();
			if($player instanceof Player and $this->mode==1){
				$event->setCancelled(true);
			        $x=round($entity->getX());
			        $y=round($entity->getY() - 3);
			        $z=round($entity->getZ());
					$player->getLevel()->setBlock(new Vector3($x, $y, $z), Block::get(54));
                    $chest = new Chest($player->getLevel(), new CompoundTag(false, array(new IntTag("x", $x), new IntTag("y", $y), new IntTag("z", $z), new StringTag("id", Tile::CHEST))));
					$chest->setName($this->name);
                    $chest->saveNBT();
			        $player->getLevel()->addTile($chest);
				   $chest2=new ChestInventory($player->getLevel()->getTile(new Vector3($x, $y, $z)), $player);
				   $ch=$player->getLevel()->getTile(new Vector3($x, $y, $z));
				   $n=$this->name;
				   $ch->saveNBT();
				   $level=$player->getLevel()->getFolderName();
				   $this->config->setNested("Inventories.$n", ["x"=>$x, "y"=>$y, "z"=>$z, "level"=>$level, "Items"=>new ListTag("Items",$ch->getInventory()), "InventoryName"=>$n, "FloatingText"=>false, "FloatingTextName"=>$n]);
				   $this->config->setAll($this->config->getAll());
				   $this->config->save();
				   $player->sendMessage($this->prefix.c::GRAY."§7- §2Slapper Selected!");
				   $this->mode=0;
			}
			$x=round($entity->getX());
			        $y=round($entity->getY() - 3);
			        $z=round($entity->getZ());
			if($player->getLevel()->getTile(new Vector3($x, $y, $z))){
				$chest=$player->getLevel()->getTile(new Vector3($x, $y, $z));
                                $cn=$chest->getName();
				if($this->config->getNested("Inventories.$cn")){
				$event->setCancelled(true);
                                $fname=$this->config->getNested("Inventories.$cn.FloatingTextName");
                                if($this->config->getNested("Inventories.$cn.FloatingText", true)){
                                    $entity->setNameTag($cn);
                                    $entity->saveNBT();
                                }
				$player->addWindow($chest->getInventory());
                }
                
            }
            
		}
        
	}
    
    public function InventoryTransactionEvent(InventoryTransactionEvent $event){
		$Transaction = $event->getTransaction();
		$Player = null;
		$name = null;
		$chest = null;
		foreach ($Transaction->getInventories() as $inv) {
			if ($inv instanceof PlayerInventory)
				$Player = $inv->getHolder();
			elseif($inv instanceof ChestInventory)
				$name = $inv->getHolder()->getName();
				$chest=$inv->getHolder();
		}
		foreach ($Transaction->getTransactions() as $t) {
			foreach ($this->traderInvTransaction($t) as $nt)
				$added [] = $nt;
		}
		foreach ($added as $item) {
			$SourceItem = $item->getSourceItem();
			$TargetItem = $item->getTargetItem();
                $TargetItemid = $TargetItem->getId();
                $SourceItemid= $SourceItem->getId();
                if($this->config->getNested("Inventories.$name.Commands.$TargetItemid") and $TargetItem->getId()>=0){
                    $event->setCancelled(true);
                    //$Player->getInventory()->clearAll();
                    $Player->removeWindow($chest->getInventory());  // why not working?
                    $command=$this->config->getNested("Inventories.$name.Commands.$TargetItemid");
                    $playername=$Player->getName();
                    $this->newTask($playername, $command);
                }
                if($this->config->getNested("Inventories.$name.Commands.$SourceItemid") and $SourceItem->getId()>=0){
                    $event->setCancelled(true);
                }
		}
        
		
	}
    
    public function newTask($player, $command){
		$g=new WaitTask($this, $player, $command);
		$h=$this->getServer()->getScheduler()->scheduleRepeatingTask($g, 20);
		$g->setHandler($h);
	}
    
    public function traderInvTransaction($t){
		$src = clone $t->getSourceItem();
		$dst = clone $t->getTargetItem();
		if ($dst->getId() == Item::AIR)
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $t->getTargetItem(), clone $src)];
		if ($src->getId() == Item::AIR)
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $dst, clone $src)];
		if ($dst->getCount() > 0) {
			$dst->setCount(1);
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $t->getTargetItem(), clone $dst)];
		}
		return [];
	}
}
