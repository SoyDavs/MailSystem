<?php

namespace SoyDavs\MailSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class Main extends PluginBase implements Listener {
    private Config $mailData;
    private Config $userConfig;
    private Config $langConfig;
    private string $mailSuffix;
    
    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        
        // Create plugin directory and config files
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->saveResource("languages.yml");
        
        $this->mailData = new Config($this->getDataFolder() . "mails.yml", Config::YAML);
        $this->userConfig = new Config($this->getDataFolder() . "users.yml", Config::YAML);
        $this->langConfig = new Config($this->getDataFolder() . "languages.yml", Config::YAML);
        
        // Get mail suffix from config
        $this->mailSuffix = $this->getConfig()->get("mail-suffix", "mcpe@server");
    }
    
    private function getMessage(string $key, array $params = []): string {
        $message = $this->langConfig->getNested("messages." . $key, "Message not found: " . $key);
        foreach($params as $param => $value) {
            $message = str_replace("{" . $param . "}", $value, $message);
        }
        return $message;
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!$command->getName() === "mail") {
            return false;
        }
        
        if(!$sender instanceof Player) {
            $sender->sendMessage($this->getMessage("ingame-only"));
            return false;
        }
        
        if(!isset($args[0])) {
            $this->sendHelp($sender);
            return true;
        }
        
        switch($args[0]) {
            case "signup":
                if(!isset($args[1])) {
                    $sender->sendMessage($this->getMessage("signup-usage"));
                    return false;
                }
                
                $username = strtolower($args[1]);
                if($this->userConfig->exists($username)) {
                    $sender->sendMessage($this->getMessage("username-taken"));
                    return false;
                }
                
                $mailAddress = $username . "." . $this->mailSuffix;
                $this->userConfig->set($sender->getName(), [
                    "mail" => $mailAddress,
                    "inbox" => [],
                    "sent" => [],
                    "drafts" => []
                ]);
                $this->userConfig->save();
                
                $sender->sendMessage($this->getMessage("registration-success", ["mail" => $mailAddress]));
                return true;
                
            case "send":
                if(count($args) < 3) {
                    $sender->sendMessage($this->getMessage("send-usage"));
                    return false;
                }
                
                $recipient = strtolower($args[1]);
                $message = implode(" ", array_slice($args, 2));
                
                if(!$this->userConfig->exists($sender->getName())) {
                    $sender->sendMessage($this->getMessage("registration-required"));
                    return false;
                }
                
                // Find recipient by checking username, mail or player name
                $recipientData = null;
                $recipientName = null;
                
                foreach($this->userConfig->getAll() as $playerName => $data) {
                    if(strtolower($playerName) === $recipient || 
                       strtolower($data["mail"]) === $recipient . "." . strtolower($this->mailSuffix) ||
                       strtolower(explode(".", $data["mail"])[0]) === $recipient) {
                        $recipientData = $data;
                        $recipientName = $playerName;
                        break;
                    }
                }
                
                if($recipientData === null) {
                    $sender->sendMessage($this->getMessage("recipient-not-found"));
                    return false;
                }
                
                $mail = [
                    "from" => $sender->getName(),
                    "message" => $message,
                    "timestamp" => time(),
                    "read" => false
                ];
                
                $recipientData["inbox"][] = $mail;
                $this->userConfig->set($recipientName, $recipientData);
                
                $senderData = $this->userConfig->get($sender->getName());
                $senderData["sent"][] = $mail;
                $this->userConfig->set($sender->getName(), $senderData);
                
                $this->userConfig->save();
                
                $sender->sendMessage($this->getMessage("mail-sent"));
                
                // If recipient is online, notify them
                $recipientPlayer = $this->getServer()->getPlayerByPrefix($recipientName);
                if($recipientPlayer !== null) {
                    $recipientPlayer->sendTitle(
                        $this->getMessage("new-mail-title"),
                        $this->getMessage("new-mail-subtitle", ["sender" => $sender->getName()])
                    );
                }
                return true;
                
            case "inbox":
                if(!$this->userConfig->exists($sender->getName())) {
                    $sender->sendMessage($this->getMessage("registration-required"));
                    return false;
                }
                
                $userData = $this->userConfig->get($sender->getName());
                $inbox = $userData["inbox"];
                
                if(empty($inbox)) {
                    $sender->sendMessage($this->getMessage("empty-inbox"));
                    return true;
                }
                
                $sender->sendMessage($this->getMessage("inbox-header"));
                
                // Mark all messages as read
                foreach($inbox as $index => &$mail) {
                    $mail["read"] = true;
                    $sender->sendMessage($this->getMessage("mail-format", [
                        "index" => $index,
                        "from" => $mail["from"],
                        "message" => $mail["message"],
                        "date" => date("Y-m-d H:i:s", $mail["timestamp"]),
                        "read_status" => TF::GREEN . " [Read]"
                    ]));
                }
                
                // Save the updated read status
                $userData["inbox"] = $inbox;
                $this->userConfig->set($sender->getName(), $userData);
                $this->userConfig->save();
                
                return true;
        }
        
        $this->sendHelp($sender);
        return true;
    }
    
    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if($this->userConfig->exists($player->getName())) {
            $userData = $this->userConfig->get($player->getName());
            $unreadCount = count(array_filter($userData["inbox"], fn($mail) => !$mail["read"]));
            
            if($unreadCount > 0) {
                $player->sendTitle(
                    $this->getMessage("unread-mail-title"),
                    $this->getMessage("unread-mail-subtitle", ["count" => $unreadCount])
                );
            }
        }
    }
    
    private function sendHelp(Player $player): void {
        $player->sendMessage($this->getMessage("help-header"));
        $player->sendMessage($this->getMessage("help-signup"));
        $player->sendMessage($this->getMessage("help-send"));
        $player->sendMessage($this->getMessage("help-inbox"));
    }
}