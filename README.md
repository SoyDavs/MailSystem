


# MailSystem Plugin for PocketMine

MailSystem is a feature-rich PocketMine plugin that allows players to create and manage a virtual mail system in-game. Players can register custom email-like addresses, send messages to other players, and manage their inboxes.

## Features

- **Custom Email Registration:** Players can register unique email-like addresses with a customizable suffix.
- **Mail Sending:** Send messages to other players via their registered email addresses.
- **Inbox Management:** Players can view their inbox, read messages, and see notifications for unread mail.
- **Persistent Storage:** Mails and user data are stored in YAML files.
- **Localization Support:** Easily customizable messages using `languages.yml`.

## Commands

### `/mail`
- **Usage:** `/mail <signup|send|inbox>`
- **Description:** Main command to interact with the mail system.

#### Subcommands:
1. **`signup`**
   - **Usage:** `/mail signup <username>`
   - **Description:** Register a unique email-like address.

2. **`send`**
   - **Usage:** `/mail send <recipient> <message>`
   - **Description:** Send a message to another registered player.

3. **`inbox`**
   - **Usage:** `/mail inbox`
   - **Description:** View your inbox and read messages.

## Configuration

### `config.yml`
Customize the plugin's behavior by modifying the configuration file:

```yaml
# Mail System Configuration

# Suffix that will be appended to all mail addresses
mail-suffix: "mcpe@server"

# Maximum number of mails in inbox
max-inbox-size: 50

# Maximum number of mails in sent folder
max-sent-size: 30

# Maximum number of drafts
max-drafts: 10

# Time in seconds before marking a mail as "old" (for cleanup purposes)
mail-expiry: 604800  # 7 days
```

## Permissions

### `mailsystem.command.mail`
- **Default:** `true`
- **Description:** Allows players to use mail system commands.

## Installation

1. Place the plugin `.phar` file into your PocketMine `plugins` folder.
2. Start your server to generate the configuration and language files.
3. Edit `config.yml` and `languages.yml` as needed.
4. Restart your server.

## TODO

| Feature                | Description                                                                 |
|------------------------|-----------------------------------------------------------------------------|
| **Implement UI**       | Add an interactive menu-based UI for easier mail management.               |
|                        | Integrate PocketMine's forms API to provide graphical interfaces.          |
| **Add SQL Support**    | Migrate data storage from YAML to SQL for improved scalability and speed.  |
|                        | Ensure compatibility with both MySQL and SQLite.                          |

## Contributing

Feel free to submit issues or pull requests to improve the plugin. Contributions to implement features from the TODO list are especially welcome!

---

