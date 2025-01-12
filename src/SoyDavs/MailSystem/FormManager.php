<?php

namespace SoyDavs\MailSystem;

use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use Vecnavium\FormsUI\CustomForm;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class FormManager {
    private Main $plugin;
    private MailManager $mailManager;

    public function __construct(Main $plugin, MailManager $mailManager) {
        $this->plugin = $plugin;
        $this->mailManager = $mailManager;
    }

    public function openMainMenu(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if($data === null){
                return;
            }
            switch($data){
                case 0:
                    $this->openSendMailForm($player);
                    break;
                case 1:
                    $this->openInboxForm($player);
                    break;
                case 2:
                    $this->openSignupForm($player);
                    break;
            }
        });

        $form->setTitle(TF::AQUA . "Mail System");
        $form->setContent(TF::WHITE . "Choose an option below:");

        // Adding buttons with internal texture icons
        $form->addButton(TF::GREEN . "Send Mail", 0, "textures/ui/book_edit_default");
        $form->addButton(TF::YELLOW . "Inbox", 0, "textures/ui/mail_icon");
        $form->addButton(TF::BLUE . "Signup", 0, "textures/ui/hanging_sign_bamboo");

        $player->sendForm($form);
    }

    public function openSignupForm(Player $player): void {
        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null){
                return;
            }
            $username = strtolower(trim($data[1]));
            if(empty($username)){
                $player->sendMessage($this->plugin->getMessage("username-empty"));
                return;
            }
            if($this->mailManager->getUserConfig()->exists($username)) {
                $player->sendMessage($this->plugin->getMessage("username-taken"));
                return;
            }

            $mailAddress = $username . "." . $this->plugin->getConfig()->get("mail-suffix", "mcpe@server");
            $this->mailManager->getUserConfig()->set($player->getName(), [
                "mail" => $mailAddress,
                "inbox" => [],
                "sent" => [],
                "drafts" => []
            ]);
            $this->mailManager->getUserConfig()->save();

            $player->sendMessage($this->plugin->getMessage("registration-success", ["mail" => $mailAddress]));
        });

        $form->setTitle(TF::GOLD . "Signup");
        $form->addLabel(TF::WHITE . "Create your mail account below.");
        $form->addInput(TF::YELLOW . "Choose a username:", "Username");

        $player->sendForm($form);
    }


    public function openSendMailForm(Player $player): void {
        if(!$this->plugin->getMailManager()->getUserConfig()->exists($player->getName())) {
            $player->sendMessage($this->plugin->getMessage("registration-required"));
            return;
        }

        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null){
                return;
            }
            $recipient = strtolower(trim($data[1]));
            $message = trim($data[2]);

            if(empty($recipient) || empty($message)){
                $player->sendMessage($this->plugin->getMessage("send-empty-fields"));
                return;
            }

            $this->mailManager->sendMail($player, $recipient, $message);
        });

        $form->setTitle(TF::AQUA . "Send Mail");
        $form->addLabel(TF::WHITE . "Compose a new mail below.");
        $form->addInput(TF::YELLOW . "Recipient (username/mail):", "Recipient");
        $form->addInput(TF::YELLOW . "Message:", "Message");

        $player->sendForm($form);
    }

    public function openInboxForm(Player $player): void {
        if(!$this->plugin->getMailManager()->getUserConfig()->exists($player->getName())) {
            $player->sendMessage($this->plugin->getMessage("registration-required"));
            return;
        }

        $userData = $this->plugin->getMailManager()->getUserConfig()->get($player->getName());
        $inbox = $userData["inbox"];

        $form = new SimpleForm(function(Player $player, ?int $data) use ($inbox){
            if($data === null){
                return;
            }
            if($data === count($inbox)) { // Back button
                $this->openMainMenu($player);
                return;
            }
            if(!isset($inbox[$data])){
                $player->sendMessage($this->plugin->getMessage("mail-not-found"));
                return;
            }
            $mail = $inbox[$data];
            $this->viewMail($player, $data, $mail);
        });

        $form->setTitle(TF::DARK_PURPLE . "Inbox");
        
        if(empty($inbox)) {
            $form->setContent(TF::RED . $this->plugin->getMessage("empty-inbox"));
            $form->addButton(TF::GRAY . "Back", 0, "textures/ui/cancel");
        } else {
            $form->setContent(TF::WHITE . $this->plugin->getMessage("inbox-header"));
            foreach($inbox as $index => $mail) {
                $status = $mail["read"] ? TF::GREEN . "Read" : TF::RED . "Unread";
                $form->addButton(TF::YELLOW . "From: " . TF::WHITE . $mail["from"] . " " . $status, 0, "textures/ui/book_edit_default");
            }
            $form->addButton(TF::GRAY . "Back", 0, "textures/ui/cancel");
        }
        $player->sendForm($form);

        // Mark all messages as read
        $this->mailManager->markAsRead($player);
    }

    public function viewMail(Player $player, int $index, array $mail): void {
        $form = new SimpleForm(function(Player $player, ?int $data) use ($mail, $index){
            if($data === null){
                return;
            }
            switch($data){
                case 0:
                    // Close button
                    break;
                case 1:
                    $this->openForwardMailForm($player, $index);
                    break;
                case 2:
                    $this->plugin->getMailManager()->deleteMail($player, $index);
                    break;
            }
        });

        $form->setTitle(TF::GOLD . "Mail from " . TF::WHITE . $mail["from"]);
        $form->setContent(
            TF::WHITE . "Message: " . TF::GRAY . $mail["message"] . "\n" .
            TF::WHITE . "Date: " . TF::GRAY . date("Y-m-d H:i:s", $mail["timestamp"])
        );

        $form->addButton(TF::GREEN . "Close", 0, "textures/ui/check");
        $form->addButton(TF::BLUE . "Forward", 0, "textures/ui/arrow_dark_right");
        $form->addButton(TF::RED . "Delete", 0, "textures/ui/cancel"); 

        $player->sendForm($form);
    }

    public function openForwardMailForm(Player $player, int $mailIndex): void {
        $form = new CustomForm(function(Player $player, ?array $data) use ($mailIndex){
            if($data === null){
                return;
            }
            $recipient = strtolower(trim($data[1]));
            if(empty($recipient)){
                $player->sendMessage($this->plugin->getMessage("send-empty-fields"));
                return;
            }

            $this->mailManager->sendMail($player, $recipient, $data[2]); // Adjust as needed
        });

        $form->setTitle(TF::LIGHT_PURPLE . "Forward Mail");
        $form->addLabel(TF::WHITE . "Forward the following mail:");
        $form->addInput(TF::YELLOW . "Recipient (username/mail):", "Recipient");
        $form->addInput(TF::YELLOW . "Message (optional):", "Message");

        $player->sendForm($form);
    }
}
