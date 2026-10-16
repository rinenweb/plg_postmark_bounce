# Postmark Bounce Notifier Plugin for Joomla

## Description

This Joomla plugin handles bounce and spam complaint notifications from Postmark via webhook.  
When an email sent through Postmark bounces or is marked as spam, this plugin can notify a specified email address.

It's ideal for keeping your mailing lists clean and being instantly notified of delivery issues.  
Will (hopefully) play nice with Joomla 4.x, 5.x and 6.x (PHP 7.4+).

---

## Installation

1. Download the latest release of the plugin.
2. In your Joomla administrator panel, go to **Extensions > Manage > Install**.
3. Upload and install the plugin ZIP file.
4. Go to **Extensions > Plugins**, search for **Postmark Bounce**, and enable the plugin.

---

## Configuration

After enabling the plugin, click on its name to configure the available options:

- **Notification Email**  
  _Email address to receive bounce notifications. If left empty, the administrator's email will be used._

- **Webhook Token**  (since v1.0.1)  
  _Optional but recommended. If set, append &token=YOUR_TOKEN to the webhook URL in Postmark. Requests without the correct token are rejected._

### Instructions to set up the webhook in Postmark:

1. Log in to your [Postmark](https://postmarkapp.com) account.
2. Navigate to **Servers** and select the server used for sending emails.
3. Click on **Webhooks** in the left-hand menu.
4. Click **Add Webhook** and enter the following URL: https://yourdomain.com/index.php?option=com_ajax&plugin=postmark_bounce&format=json
_(Replace `yourdomain.com` with your actual domain name)_
5. Under **Event Types**, check:
- `Bounce`
- `Spam Complaint`
6. Click **Save Webhook**.
7. Optionally, use the **Send Test** feature to verify the webhook is working.

---

## Screenshot

![Plugins_Postmark_Bounce_Notifier](https://github.com/user-attachments/assets/492cee1a-627f-46bb-83b4-53ea390e5f47)

---

## License and Disclaimer

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version. See the [LICENSE](https://github.com/rinenweb/plg_postmark_bounce/blob/main/LICENSE) file for details.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this plugin. If not, see https://www.gnu.org/licenses/gpl-3.0.txt.

I do not offer any kind of support. Using freely distributed software does not entitle you to free support or labor from its developers. You may contact me with any ideas for new features, but there are no guarantees that your request will be implemented or ever responded to in a certain timeframe or at all.
