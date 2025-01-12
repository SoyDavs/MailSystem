<?php

namespace SoyDavs\MailSystem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

class EventListener implements Listener {
    private Main $plugin;
    private MailManager $mailManager;
    private Config $langConfig;

    public function __construct(Main $plugin, MailManager $mailManager, Config $langConfig) {
        $this->plugin = $plugin;
        $this->mailManager = $mailManager;
        $this->langConfig = $langConfig;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if($this->mailManager->getUserConfig()->exists($player->getName())) {
            $userData = $this->mailManager->getUserConfig()->get($player->getName());
            $unreadCount = count(array_filter($userData["inbox"], fn($mail) => !$mail["read"]));

            if($unreadCount > 0) {
                $player->sendTitle(
                    TF::GOLD . $this->plugin->getMessage("unread-mail-title"),
                    TF::YELLOW . str_replace("{count}", $unreadCount, $this->plugin->getMessage("unread-mail-subtitle"))
                );
            }
        }
    }
}
