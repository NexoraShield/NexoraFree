# Release Notes — Nexora Free

This is the changelog of the **free** edition of Nexora. The free edition is a
complete product on its own: it never needs another edition, another panel or
another service to work.

The changes of the professional edition are not published here.

---

## v1.24.00 — the free edition

The first release of the free edition. It is built from the same code base as
the professional one, but everything that belongs to the professional edition
has been taken out, so nothing of it is published here.

### What the free edition has

- Selling VPN services automatically (products, categories, the shop of the bot)
- Connection to the VPN panels (Marzban, Marzneshin, X-UI, Hiddify, WGDashboard,
  MikroTik, IBSng and more)
- Basic user management (profiles, balances, subscriptions, expiration)
- The wallet of the customer
- The payment gateways (Zarinpal, Variza, Card to card, AqayePardakht, IranPay,
  NowPayment, FrenzyEx)
- The Mini App of Telegram
- The web panel (products, categories, users, invoices, wallet, payments, the
  texts of the bot, the colours of the buttons, the backup, the queue, the
  health of the panels, the update)
- The admin menu inside the bot
- Four languages: Persian, English, Russian, Chinese

### What was taken out

- The agent/reseller panel (the panel of the reseller, the shop of the agent and
  the sale of the panels to the resellers)
- The advanced reports (reports, finance, audit, export)
- The full customization (the keyboard editor, the button editor, the custom
  apps, the magic page, the collaboration notes)
- The support tickets
- The multi-bot system
- The premium emoji of Telegram — **everywhere**: the messages, the buttons,
  the menus, the automated messages and the output of the bot. Only the normal
  emoji are used.

### Technical

- The bot never sends a premium emoji: the tags are always taken out of the
  text and a button never gets an icon.
- The library of the emoji, the picker of the panel and the scripts of the
  patch are not part of this edition (they would have brought the features of
  the professional edition back).
- The installer does not patch anything any more and it only installs the files
  of this edition.
- The uninstaller removes the bot, its cron dispatcher and its vhost of nginx.
  The database stays, unless `--purge` is passed.
- The page of the VPN panels is back in the web panel (it manages the panels and
  their colours).
- Every PHP file of the edition is checked (210 files, no error) and the
  scripts of the shell are checked as well.

### The install is the same as before

```bash
curl -sL https://raw.githubusercontent.com/NexoraShield/Nexora/main/nexora-update.sh -o /tmp/nexora-install.sh && sudo bash /tmp/nexora-install.sh
```
