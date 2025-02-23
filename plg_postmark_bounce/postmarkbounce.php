<?php
/**
 * @package    Joomla.Plugin.System.PostmarkBounce
 * @subpackage Plugins
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;

class PlgSystemPostmarkBounce extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function onAjaxPostmark_bounce()
    {
        // Get input data
        $input = Factory::getApplication()->input;
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        // Ensure it's a valid bounce or spam complaint event
        if (!isset($data['RecordType']) || !in_array($data['RecordType'], ['Bounce', 'SpamComplaint'])) {
            header('HTTP/1.1 400 Bad Request');
            exit(Text::_('PLG_SYSTEM_POSTMARKBOUNCE_INVALID_EVENT'));
        }

        // Get administrator email or custom email from plugin parameters
        $config = Factory::getConfig();
        $adminEmail = $config->get('mailfrom');
        $customEmail = $this->params->get('notification_email', '');
        $recipientEmail = !empty($customEmail) ? $customEmail : $adminEmail;

        // Determine event type
        $eventType = ($data['RecordType'] === 'Bounce') ? Text::_('PLG_SYSTEM_POSTMARKBOUNCE_BOUNCE') : Text::_('PLG_SYSTEM_POSTMARKBOUNCE_SPAM_COMPLAINT');

        // Prepare email details
        $subject = sprintf(Text::_('PLG_SYSTEM_POSTMARKBOUNCE_EMAIL_SUBJECT'), $eventType);
        $body = Text::_('PLG_SYSTEM_POSTMARKBOUNCE_EVENT_RECEIVED') . "\n\n" .
                Text::_('PLG_SYSTEM_POSTMARKBOUNCE_RECIPIENT') . ' ' . htmlspecialchars($data['Email']) . "\n" .
                Text::_('PLG_SYSTEM_POSTMARKBOUNCE_EVENT_TYPE') . ' ' . htmlspecialchars($eventType) . "\n" .
                Text::_('PLG_SYSTEM_POSTMARKBOUNCE_DESCRIPTION') . ' ' . htmlspecialchars($data['Description'] ?? Text::_('PLG_SYSTEM_POSTMARKBOUNCE_NO_DESCRIPTION')) . "\n" .
                Text::_('PLG_SYSTEM_POSTMARKBOUNCE_BOUNCED_AT') . ' ' . htmlspecialchars($data['BouncedAt'] ?? 'N/A') . "\n" .
                Text::_('PLG_SYSTEM_POSTMARKBOUNCE_SERVER_ID') . ' ' . htmlspecialchars($data['ServerID'] ?? 'N/A') . "\n" .
                Text::_('PLG_SYSTEM_POSTMARKBOUNCE_MESSAGE_STREAM') . ' ' . htmlspecialchars($data['MessageStream'] ?? 'N/A') . "\n\n";

        // Send email
        $mailer = Factory::getMailer();
        $mailer->setSender([$adminEmail, 'Postmark Notifier']);
        $mailer->addRecipient($recipientEmail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->send();

        // Respond to webhook
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
}