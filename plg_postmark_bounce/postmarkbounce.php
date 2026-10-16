<?php
/**
 * @package    Joomla.Plugin.System.PostmarkBounce
 * @subpackage Plugins
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;

class PlgSystemPostmarkBounce extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onAjaxPostmark_bounce()
    {
        $app = Factory::getApplication();

        // Postmark always POSTs; reject anything else
        if (strtoupper($app->getInput()->getMethod()) !== 'POST') {
            $this->respond(405, Text::_('PLG_SYSTEM_POSTMARKBOUNCE_INVALID_EVENT'));
        }

        // Optional shared secret: if set, the webhook URL must carry &token=...
        $secret = trim((string) $this->params->get('webhook_token', ''));

        if ($secret !== '' && !hash_equals($secret, (string) $app->getInput()->getString('token', ''))) {
            $this->respond(403, Text::_('PLG_SYSTEM_POSTMARKBOUNCE_FORBIDDEN'));
        }

        $data = json_decode((string) file_get_contents('php://input'), true);

        // Ensure it's a valid bounce or spam complaint event
        if (!is_array($data) || !isset($data['RecordType']) || !in_array($data['RecordType'], ['Bounce', 'SpamComplaint'], true)) {
            $this->respond(400, Text::_('PLG_SYSTEM_POSTMARKBOUNCE_INVALID_EVENT'));
        }

        // Administrator email or custom email from plugin parameters
        $adminEmail     = $app->get('mailfrom');
        $customEmail    = trim((string) $this->params->get('notification_email', ''));
        $recipientEmail = $customEmail !== '' ? $customEmail : $adminEmail;

        $eventType = ($data['RecordType'] === 'Bounce')
            ? Text::_('PLG_SYSTEM_POSTMARKBOUNCE_BOUNCE')
            : Text::_('PLG_SYSTEM_POSTMARKBOUNCE_SPAM_COMPLAINT');

        // Plain-text e-mail, so no HTML escaping; just keep values on one line
        $subject = sprintf(Text::_('PLG_SYSTEM_POSTMARKBOUNCE_EMAIL_SUBJECT'), $eventType);
        $body    = Text::_('PLG_SYSTEM_POSTMARKBOUNCE_EVENT_RECEIVED') . "\n\n"
            . Text::_('PLG_SYSTEM_POSTMARKBOUNCE_RECIPIENT') . ' ' . $this->clean($data['Email'] ?? 'N/A') . "\n"
            . Text::_('PLG_SYSTEM_POSTMARKBOUNCE_EVENT_TYPE') . ' ' . $eventType . "\n"
            . Text::_('PLG_SYSTEM_POSTMARKBOUNCE_DESCRIPTION') . ' ' . $this->clean($data['Description'] ?? Text::_('PLG_SYSTEM_POSTMARKBOUNCE_NO_DESCRIPTION')) . "\n"
            . Text::_('PLG_SYSTEM_POSTMARKBOUNCE_BOUNCED_AT') . ' ' . $this->clean($data['BouncedAt'] ?? 'N/A') . "\n"
            . Text::_('PLG_SYSTEM_POSTMARKBOUNCE_SERVER_ID') . ' ' . $this->clean($data['ServerID'] ?? 'N/A') . "\n"
            . Text::_('PLG_SYSTEM_POSTMARKBOUNCE_MESSAGE_STREAM') . ' ' . $this->clean($data['MessageStream'] ?? 'N/A') . "\n\n";

        try {
            // MailerFactoryInterface exists from Joomla 4.4; fall back for 4.0–4.3
            $mailer = interface_exists(MailerFactoryInterface::class)
                ? Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer()
                : Factory::getMailer();

            $mailer->setSender([$adminEmail, 'Postmark Notifier']);
            $mailer->addRecipient($recipientEmail);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->send();
        } catch (\Throwable $e) {
            // Non-2xx makes Postmark retry later
            $this->respond(500, Text::_('PLG_SYSTEM_POSTMARKBOUNCE_MAIL_FAILED'));
        }

        $this->respond(200, 'success');
    }

    private function clean($value): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value));
    }

    private function respond(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => $code === 200 ? 'success' : 'error', 'message' => $message]);
        Factory::getApplication()->close();
    }
}
