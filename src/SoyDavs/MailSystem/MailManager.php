<?php

namespace SoyDavs\MailSystem;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class MailManager {
    private Main $plugin;
    private Config $userConfig;
    private Config $langConfig;
    private string $mailSuffix;

    public function __construct(Main $plugin, Config $userConfig, Config $langConfig, string $mailSuffix) {
        $this->plugin = $plugin;
        $this->userConfig = $userConfig;
        $this->langConfig = $langConfig;
        $this->mailSuffix = $mailSuffix;
    }

    public function sendMail(Player $sender, string $recipient, string $message): void {
        $recipientData = $this->findUser($recipient);
        if($recipientData === null) {
            $sender->sendMessage($this->plugin->getMessage("recipient-not-found"));
            return;
        }

        $mail = [
            "from" => $sender->getName(),
            "message" => $message,
            "timestamp" => time(),
            "read" => false
        ];

        $recipientName = $recipientData['name'];
        $recipientData['data']['inbox'][] = $mail;
        $this->userConfig->set($recipientName, $recipientData['data']);

        $senderData = $this->userConfig->get($sender->getName());
        $senderData["sent"][] = $mail;
        $this->userConfig->set($sender->getName(), $senderData);

        $this->userConfig->save();

        $sender->sendMessage($this->plugin->getMessage("mail-sent"));

        // Notify recipient if online
        $recipientPlayer = $this->plugin->getServer()->getPlayerExact($recipientName);
        if($recipientPlayer !== null) {
            $recipientPlayer->sendTitle(
                $this->plugin->getMessage("new-mail-title"),
                str_replace("{sender}", $sender->getName(), $this->plugin->getMessage("new-mail-subtitle"))
            );
        }
    }

    public function forwardMail(Player $sender, int $mailIndex): void {
        $userData = $this->userConfig->get($sender->getName());
        $inbox = $userData["inbox"];

        if(!isset($inbox[$mailIndex])) {
            $sender->sendMessage($this->plugin->getMessage("mail-not-found"));
            return;
        }

        // Forward logic can be handled via FormManager
        $sender->sendMessage($this->plugin->getMessage("use-ui-to-forward"));
    }

    public function deleteMail(Player $player, int $mailIndex): void {
        $userData = $this->userConfig->get($player->getName());
        $inbox = $userData["inbox"];

        if(!isset($inbox[$mailIndex])) {
            $player->sendMessage($this->plugin->getMessage("mail-not-found"));
            return;
        }

        unset($inbox[$mailIndex]);
        $userData["inbox"] = array_values($inbox); // Reindex array
        $this->userConfig->set($player->getName(), $userData);
        $this->userConfig->save();

        $player->sendMessage($this->plugin->getMessage("mail-deleted"));
    }

    public function markAsRead(Player $player): void {
        $userData = $this->userConfig->get($player->getName());
        foreach($userData["inbox"] as &$mail) {
            $mail["read"] = true;
        }
        $this->userConfig->set($player->getName(), $userData);
        $this->userConfig->save();
    }

    private function findUser(string $recipient): ?array {
        foreach($this->userConfig->getAll() as $playerName => $data) {
            if(strtolower($playerName) === strtolower($recipient) || 
               strtolower($data["mail"]) === strtolower($recipient) . "." . strtolower($this->mailSuffix) ||
               strtolower(explode(".", $data["mail"])[0]) === strtolower($recipient)) {
                return ['name' => $playerName, 'data' => $data];
            }
        }
        return null;
    }

    public function getUserConfig(): Config {
        return $this->userConfig;
    }
}
