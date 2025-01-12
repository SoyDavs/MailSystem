<?php

namespace SoyDavs\MailSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class Main extends PluginBase implements Listener {
    private MailManager $mailManager;
    private FormManager $formManager;
    private Config $userConfig;
    private Config $langConfig;
    private string $mailSuffix;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Create plugin directory and config files
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->saveResource("languages.yml");

        $this->userConfig = new Config($this->getDataFolder() . "users.yml", Config::YAML);
        $this->langConfig = new Config($this->getDataFolder() . "languages.yml", Config::YAML);

        // Get mail suffix from config
        $this->mailSuffix = $this->getConfig()->get("mail-suffix", "mcpe@server");

        // Initialize managers
        $this->mailManager = new MailManager($this, $this->userConfig, $this->langConfig, $this->mailSuffix);
        $this->formManager = new FormManager($this, $this->mailManager);

        // Register event listeners
        new EventListener($this, $this->mailManager, $this->langConfig);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(strtolower($command->getName()) !== "mail") {
            return false;
        }

        if(!$sender instanceof Player) {
            $sender->sendMessage($this->getMessage("ingame-only"));
            return false;
        }

        if(!isset($args[0])) {
            $this->formManager->openMainMenu($sender);
            return true;
        }

        switch($args[0]) {
            case "signup":
                $this->formManager->openSignupForm($sender);
                return true;

            case "send":
                $this->formManager->openSendMailForm($sender);
                return true;

            case "inbox":
                $this->formManager->openInboxForm($sender);
                return true;

            case "forward":
                if(count($args) < 2) {
                    $sender->sendMessage($this->getMessage("forward-usage"));
                    return false;
                }
                $mailIndex = intval($args[1]);
                $this->mailManager->forwardMail($sender, $mailIndex);
                return true;
        }

        $this->formManager->openMainMenu($sender);
        return true;
    }

    public function getMailManager(): MailManager {
        return $this->mailManager;
    }

    public function getFormManager(): FormManager {
        return $this->formManager;
    }

    public function getMessage(string $key, array $params = []): string {
        $message = $this->langConfig->getNested("messages." . $key, "Message not found: " . $key);
        foreach($params as $param => $value) {
            $message = str_replace("{" . $param . "}", $value, $message);
        }
        return $message;
    }
}