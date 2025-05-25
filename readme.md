


# Notifier Bot

This PHP-based bot periodically checks the appointment calendar (API endnode) and notifies you via email when there's a change in availability.

## Features

- Monitors the calendar API for specific months.
- Sends email alerts when:
  - The HTTP status is not 200.
  - The returned data is not empty (`{"data":{}}`).
  - Debug mode is enabled.
- Logs all API responses to `response.log`.

## Setup

### 1. Clone the Repository

```bash
git clone https://github.com/sghiaseddin/notifier-bot.git
cd notifier-bot
```

### 2. Install Dependencies

This project uses [PHPMailer](https://github.com/PHPMailer/PHPMailer) and [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv):

```bash
composer require phpmailer/phpmailer
composer require vlucas/phpdotenv
```

### 3. Configure Environment Variables

Copy the example environment file and fill in your values:

```bash
cp .sample.env .env
```

Then edit `.env` and add your SMTP credentials, payload URLs, and flags.

### 4. Test the Bot

Run it manually:

```bash
./cron.sh
```

### 5. Set Up Cron Job (Optional)

To run every 5 minutes, add this line to your crontab:

```bash
*/5 * * * * /usr/bin/php /full/path/to/check_calendar.php >/dev/null 2>&1
```

## License

MIT