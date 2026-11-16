# Nexora Free Bot 🤖

<h1 align="center"><a href="README.md"><kbd>🇬🇧 English</kbd></a><a href="README.fa.md"><kbd>🇮🇷 فارسی</kbd></a><a href="README.ru.md"><kbd>🇷🇺 Русский</kbd></a><a href="README.zh.md"><kbd>🇨🇳 中文</kbd></a></h1>

**Nexora Free** is an open-source Telegram bot for selling VPN services and managing them. Built with PHP, it works with many VPN panels (Marzban, Marzneshin, X-UI, Hiddify, WGDashboard, MikroTik, IBSng and more), has a glass theme web panel, several payment gateways and a Telegram Mini App.

> This repository is the **free** edition of Nexora. It is a complete product on its own: it never needs another edition, another panel or another service to work.
>
> The free edition does **not** have: the agent/reseller panel, the advanced reports, the multi-bot system, the full customization and the dedicated support. The premium emoji of Telegram is not supported either — only the normal emoji are used.

## ✨ Features

- 🎨 **Glass Theme UI** — Modern glassmorphism design for both the bot and the web panel
- 🎨 **Button Colors** — Blue / green / red per product, category and panel
- 📱 **Mini App** — Telegram Mini App for the customers
- 🔄 **Multi-Panel Support** — Marzban, Marzneshin, X-UI, Hiddify, WGDashboard, MikroTik, IBSng and more
- 💳 **Payment Gateways** — Zarinpal, Variza, Card to card, AqayePardakht, IranPay, NowPayment, FrenzyEx
- ⚡ **High Performance** — Optimized database queries, indexes and caching
- 📊 **Web Panel** — Products, categories, users, invoices, wallet, payments, backups, queue, health and settings
- 🔔 **Notifications** — Automated notifications for expiry, low balance and unpaid invoices
- 🎁 **Gift Codes & Discounts** — Promotional codes and the discount system
- 🎰 **Wheel of Fortune** — Gamification for the users
- 📦 **Product Management** — Categories, products, prices, display order, test products
- 👥 **User Management** — Profiles, balances, subscriptions, expiration
- 🌍 **Four languages** — Persian, English, Russian and Chinese
- 🔐 **Security** — CSRF protection, secure authentication, session management

## 📋 Requirements

- **PHP** >= 8.2
- **MySQL** >= 5.7 / MariaDB >= 10.3
- **Nginx** (or Apache with mod_rewrite)
- **Extensions:** PDO, PDO_MySQL, cURL, GD, mbstring, xml, zip, json, openssl
- **Composer** (for the dependencies)

## 🚀 One-Line Installation

```bash
curl -sL https://raw.githubusercontent.com/NexoraShield/NexoraFree/main/nexora-update.sh -o /tmp/nexora-install.sh && sudo bash /tmp/nexora-install.sh
```

The same command installs the bot and updates it later. It asks for the domain, the token of the bot (from BotFather), the numeric id of the administrator and the login of the web panel.

## 📦 Manual Installation

1. Get the code:
```bash
git clone https://github.com/NexoraShield/NexoraFree.git
cd Nexora
```

2. Put it on the server:
```bash
scp -r * root@YOUR_SERVER_IP:/var/www/html/nexorabotconfig/
```

3. Run the installer:
```bash
cd /var/www/html/nexorabotconfig
sudo bash install.sh
```

4. Fill in `config.php`:
```php
$APIKEY = 'YOUR_BOT_TOKEN';
$adminnumber = 'YOUR_ADMIN_TELEGRAM_ID';
$domainhosts = 'your-domain.com';
$usernamebot = 'your_bot_username';
```

5. Set the webhook:
```
https://api.telegram.org/botYOUR_BOT_TOKEN/setWebhook?url=https://your-domain.com/index.php?secret=YOUR_SECRET
```

## 🔄 Migration from Other Bots

### From Mirza Bot
```bash
bash <(curl -s https://raw.githubusercontent.com/NexoraShield/NexoraFree/main/migrate-mirza.sh)
```

### From ViraNaut Bot
```bash
bash <(curl -s https://raw.githubusercontent.com/NexoraShield/NexoraFree/main/migrate-viranaut.sh))
```

Both scripts detect the old installation, back up the database and the configuration, download the newest Nexora, keep `config.php` and the database, run the migrations and set the webhook.

### Manual Migration
```bash
mysqldump -u YOUR_DB_USER -p YOUR_DB_NAME > backup.sql   # 1. the database
cp /var/www/html/oldbot/config.php ./config.php            # 2. the configuration
# 3. copy the files of Nexora over the old ones (keep config.php)
php migrate.php /var/www/html/nexorabotconfig               # 4. the migrations
php rebrand.php /var/www/html/nexorabotconfig --apply       # 5. the names of the bot
systemctl reload nginx                                     # 6. the web server
```

---

## 🔧 CLI Commands

```bash
nexora status     # the state of the bot and of the services
nexora logs       # the last errors
nexora restart    # restart the services
nexora version    # the installed version
nexora update     # update to the newest version
nexora help       # the list of the commands
```

## 📁 Project Structure

```
Nexora/
├── index.php              # the entry of the bot (every update comes here)
├── function.php           # the helper functions of the whole bot
├── keyboard.php           # the keyboards of the bot
├── botapi.php             # the Telegram Bot API
├── admin.php              # the admin menu inside the bot
├── nexora_style.php       # the colours of the buttons and the style helpers
├── config.php             # the configuration (token, database, domain)
├── panels.php             # the driver of the vpn panels
├── panel_protocol.php     # the protocol of the panels
├── panels/                # the drivers of the panels (marzban, x-ui, ...)
├── panel/                 # the web panel
│   ├── index.php          # the dashboard
│   ├── login.php          # the login
│   ├── product.php        # the products
│   ├── category.php       # the categories
│   ├── panels.php         # the vpn panels
│   ├── users.php          # the users
│   ├── invoice.php        # the invoices
│   ├── service.php        # the wallet
│   ├── payment.php        # the payment gateways
│   ├── bottext.php        # the texts of the bot
│   ├── botcolors.php      # the colours of the buttons
│   ├── backup.php         # the backup of the database
│   ├── queue.php          # the queue of the work
│   ├── health.php         # the health of the panels
│   ├── update.php         # the update of the bot
│   └── ...
├── payment/               # the payment gateways
├── cronbot/               # the work of the cron (the reminders, the invoices)
├── app/                   # the mini app
├── api/                   # the api of the mini app and of the web panel
├── db/                    # the tables and the migrations
├── lang/                  # the languages (fa, en, ru, zh)
├── tools/                 # the helper scripts (the indexes, the backup)
├── install.sh             # the installer
├── nexora-update.sh       # the update of the bot
├── migrate.php            # the migrations of the database
└── README.md              # this file
```

## 🎨 Button Colors

- **primary** (blue)
- **success** (green)
- **danger** (red)

The colour of a product, a category or a panel is set in the web panel → *Button colours*, or in the bot → *Advanced features* → *Colours of the buttons*.

> The premium emoji of Telegram is **not** part of this edition. The bot only uses the normal emoji.

## 💳 Payment Gateways

The gateways are set in the web panel → *Payments*, or in the bot → *Advanced features* → *Payment gateways*: Zarinpal, Variza, Card to card, AqayePardakht, IranPay, NowPayment and FrenzyEx.

## ⚡ Performance Optimization

Nexora includes optimized database indexes. Run them manually once:
```bash
php tools/nexora-indexes.php
```

## 🔄 Updating

```bash
nexora update
```

or manually:
```bash
cd /var/www/html/nexorabotconfig
sudo bash nexora-update.sh
```

The update is done file by file, so the bot never goes down.

## 📱 Mini App

Nexora includes a Telegram Mini App. Configure it in BotFather and point it to your domain.

## 🤝 Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## 📄 License

This project is licensed under the MIT License.

## 🙏 Credits

- Nexora Bot developed by [NexoraShield](https://github.com/NexoraShield)
- Based on open-source VPN bot technology
- FrenzyEx payment gateway integration
- Glass theme support

## 📞 Support

- **Bot:** @NexoraShield_bot
- **Website:** NexoraShield.tr
- **Email:** info@NexoraShield.tr
- **Support:** @Nexora_supp
- **Channel:** @Nexorashieeld
- **Tutorial:** @NexoraUsersHelp

## 💖 Donate

If you find this project useful, please consider supporting its development:

### Crypto (USDT TRC20 / TRX)
```
TFvvFVAxBUkUfkw7cCt38RDehmnNkDHtfr
```

### Telegram
- **Stars/Gifts:** @Nexora_supp
- **Website:** NexoraShield.tr

### Other
- **Email:** info@NexoraShield.tr
- **Support:** @Nexora_supp

Your support helps keep this project free and maintained! 🙏
