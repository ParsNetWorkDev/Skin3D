<?php

namespace Kia\Skin;

use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase implements Listener{

    private array $skinCache = [];

    public function onEnable(): void{
        $this->getLogger()->info(TF::GREEN . "Skin Plugin Enabled");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        
        if(!file_exists($this->getDataFolder() . "Skins.yml")){
            new Config($this->getDataFolder() . "Skins.yml", Config::YAML);
        }
        
        if(!class_exists("jojoe77777\FormAPI\SimpleForm")){
            $this->getLogger()->warning("FormAPI found nemishavad! lotfan FormAPI ra nasb konid.");
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TF::RED . "In command faghat dar game ghabele estefade ast!");
            return false;
        }

        switch($command->getName()){
            case "setskin":
                if(count($args) === 0){
                    $this->openSetSkinForm($sender);
                }else{
                    $this->setSkinFromArgs($sender, $args);
                }
                return true;
                
            case "skinnpc":
                if(count($args) === 0){
                    $this->openSpawnNPCForm($sender);
                }else{
                    $this->spawnNPCFromArgs($sender, $args);
                }
                return true;
        }
        
        return false;
    }

    private function openSetSkinForm(Player $player): void{
        if(!class_exists("jojoe77777\FormAPI\CustomForm")){
            $player->sendMessage(TF::RED . "FormAPI nasb nashode ast!");
            return;
        }

        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null){
                $player->sendMessage(TF::YELLOW . "Change skin cancel shod.");
                return;
            }

            $texture = trim($data[0] ?? "");
            $geometry = trim($data[1] ?? "");
            $geometryFile = trim($data[2] ?? "");

            if(empty($texture)){
                $player->sendMessage(TF::RED . "Error: ESM TEXTURE ra vared konid!");
                return;
            }
            if(empty($geometry)){
                $player->sendMessage(TF::RED . "Error: ESM GEOMETRY ra vared konid!");
                return;
            }
            if(empty($geometryFile)){
                $player->sendMessage(TF::RED . "Error: ESM GEOMETRY FILE ra vared konid!");
                return;
            }

            $this->setSkin($player, $texture, $geometry, $geometryFile);
        });

        $form->setTitle(TF::BOLD . "Tanzim Skin");
        
        $form->addInput(
            "Esm Texture", 
            "Esm file .png ra vared konid (mesal: skin_1)", 
            "", 
            "skin"
        );
        
        $form->addInput(
            "Esm Geometry", 
            "Esm geometry ra vared konid (mesal: geometry.humanoid.custom)", 
            "", 
            "geometry.humanoid.custom"
        );
        
        $form->addInput(
            "Esm Geometry File", 
            "Esm file .json ra vared konid (bedon .json)", 
            "", 
            "geometry"
        );
        
        $form->addLabel(
            "File ha bayad dar in masir bashand: " . $this->getDataFolder() . "\n" .
            "Texture: [name].png\n" .
            "Geometry: [file].json"
        );

        $player->sendForm($form);
    }

    private function openSpawnNPCForm(Player $player): void{
        if(!class_exists("jojoe77777\FormAPI\CustomForm")){
            $player->sendMessage(TF::RED . "FormAPI nasb nashode ast!");
            return;
        }

        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null){
                $player->sendMessage(TF::YELLOW . "Spawn NPC cancel shod.");
                return;
            }

            $texture = trim($data[0] ?? "");
            $geometry = trim($data[1] ?? "");
            $geometryFile = trim($data[2] ?? "");
            $npcName = trim($data[3] ?? "");

            if(empty($texture)){
                $player->sendMessage(TF::RED . "Error: ESM TEXTURE ra vared konid!");
                return;
            }
            if(empty($geometry)){
                $player->sendMessage(TF::RED . "Error: ESM GEOMETRY ra vared konid!");
                return;
            }
            if(empty($geometryFile)){
                $player->sendMessage(TF::RED . "Error: ESM GEOMETRY FILE ra vared konid!");
                return;
            }
            if(empty($npcName)){
                $player->sendMessage(TF::RED . "Error: ESM NPC ra vared konid!");
                return;
            }

            $this->spawnNPC($player, $texture, $geometry, $geometryFile, $npcName);
        });

        $form->setTitle(TF::BOLD . "Sakht NPC");
        
        $form->addInput(
            "Esm Texture NPC", 
            "Esm file .png ra vared konid (mesal: npc_skin)", 
            "", 
            "npc_skin"
        );
        
        $form->addInput(
            "Esm Geometry", 
            "Esm geometry ra vared konid (mesal: geometry.humanoid.custom)", 
            "", 
            "geometry.humanoid.custom"
        );
        
        $form->addInput(
            "Esm Geometry File", 
            "Esm file .json ra vared konid (bedon .json)", 
            "", 
            "geometry"
        );
        
        $form->addInput(
            "Esm NPC", 
            "Esm ke balaye NPC neshon dade mishavad ra vared konid", 
            "", 
            "Boss NPC"
        );
        
        $form->addLabel(
            "File ha bayad dar in masir bashand: " . $this->getDataFolder() . "\n" .
            "Texture: [name].png\n" .
            "Geometry: [file].json"
        );

        $player->sendForm($form);
    }

    public function setSkin(Player $player, string $texture, string $geometry, string $geometryFile): void{
        $texturePath = $this->getDataFolder() . $texture . ".png";
        $geometryPath = $this->getDataFolder() . $geometryFile . ".json";

        if(!file_exists($texturePath)){
            $player->sendMessage(TF::RED . "Error: File texture peyda nashod! Masir: " . $texturePath);
            return;
        }

        if(!file_exists($geometryPath)){
            $player->sendMessage(TF::RED . "Error: File geometry peyda nashod! Masir: " . $geometryPath);
            return;
        }

        try {
            $skinData = $this->encodeSkin($texturePath);
            if(empty($skinData)){
                $player->sendMessage(TF::RED . "Error: Encoding skin anjam nashod!");
                return;
            }

            $geometryData = file_get_contents($geometryPath);
            if($geometryData === false){
                $player->sendMessage(TF::RED . "Error: File geometry khوندi nashod!");
                return;
            }

            $skin = new Skin(
                $player->getSkin()->getSkinId(),
                $skinData,
                "",
                $geometry,
                $geometryData
            );

            $player->setSkin($skin);
            $player->sendSkin();
            
            $player->sendMessage(TF::GREEN . "Skin ba movafaghiat tanzim shod!");
            $player->sendMessage(TF::GRAY . "Texture: " . $texture . ".png");
            
        } catch (\Exception $e) {
            $player->sendMessage(TF::RED . "Error: " . $e->getMessage());
            $this->getLogger()->error("Skin error: " . $e->getMessage());
        }
    }

    public function spawnNPC(Player $player, string $texture, string $geometry, string $geometryFile, string $npcName): void{
        $texturePath = $this->getDataFolder() . $texture . ".png";
        $geometryPath = $this->getDataFolder() . $geometryFile . ".json";

        if(!file_exists($texturePath)){
            $player->sendMessage(TF::RED . "Error: File texture peyda nashod! Masir: " . $texturePath);
            return;
        }

        if(!file_exists($geometryPath)){
            $player->sendMessage(TF::RED . "Error: File geometry peyda nashod! Masir: " . $geometryPath);
            return;
        }

        try {
            $skinData = $this->encodeSkin($texturePath);
            if(empty($skinData)){
                $player->sendMessage(TF::RED . "Error: Encoding skin anjam nashod!");
                return;
            }

            $geometryData = file_get_contents($geometryPath);
            if($geometryData === false){
                $player->sendMessage(TF::RED . "Error: File geometry khوندi nashod!");
                return;
            }

            $skin = new Skin(
                $player->getSkin()->getSkinId(),
                $skinData,
                "",
                $geometry,
                $geometryData
            );

            $npc = new Human($player->getLocation(), $skin, null);
            $npc->setNameTag($npcName);
            $npc->setNameTagVisible(true);
            $npc->setNameTagAlwaysVisible(true);
            $npc->setMaxHealth(10);
            $npc->setHealth(10);
            $npc->spawnToAll();

            $player->sendMessage(TF::GREEN . "NPC ba movafaghiat sakhte shod!");
            $player->sendMessage(TF::GRAY . "Esm: " . $npcName);
            $player->sendMessage(TF::GRAY . "Location: " . $player->getLocation()->getX() . ", " . $player->getLocation()->getY() . ", " . $player->getLocation()->getZ());

        } catch (\Exception $e) {
            $player->sendMessage(TF::RED . "Error: " . $e->getMessage());
            $this->getLogger()->error("NPC spawn error: " . $e->getMessage());
        }
    }

    public function encodeSkin(string $path): string{
        try {
            $size = @getimagesize($path);
            if($size === false){
                $this->getLogger()->error("Getimagesize failed: " . $path);
                return "";
            }

            $img = @imagecreatefrompng($path);
            if($img === false){
                $this->getLogger()->error("Imagecreatefrompng failed: " . $path);
                return "";
            }

            $skinbytes = "";
            for ($y = 0; $y < $size[1]; $y++) {
                for ($x = 0; $x < $size[0]; $x++) {
                    $colorat = @imagecolorat($img, $x, $y);
                    if($colorat === false){
                        $colorat = 0;
                    }
                    
                    $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                    $r = ($colorat >> 16) & 0xff;
                    $g = ($colorat >> 8) & 0xff;
                    $b = $colorat & 0xff;
                    
                    $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
                }
            }
            
            @imagedestroy($img);
            return $skinbytes;
            
        } catch (\Exception $e) {
            $this->getLogger()->error("Encode skin error: " . $e->getMessage());
            return "";
        }
    }

    private function setSkinFromArgs(Player $player, array $args): void{
        if(count($args) < 3){
            $player->sendMessage(TF::RED . "Tarze estefade: /setskin <texture> <geometry> <geometryFile>");
            $player->sendMessage(TF::YELLOW . "Mesan: /setskin skin_1 geometry.humanoid.custom geometry");
            return;
        }

        $this->setSkin($player, $args[0], $args[1], $args[2]);
    }

    private function spawnNPCFromArgs(Player $player, array $args): void{
        if(count($args) < 4){
            $player->sendMessage(TF::RED . "Tarze estefade: /skinnpc <texture> <geometry> <geometryFile> <npcName>");
            $player->sendMessage(TF::YELLOW . "Mesan: /skinnpc npc_1 geometry.humanoid.custom geometry Boss");
            return;
        }

        $this->spawnNPC($player, $args[0], $args[1], $args[2], $args[3]);
    }
}