<?php
/**
 * @package    Joomla.Plugin.System.PostmarkBounce
 * @subpackage Plugins
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
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
            exit('Invalid webhook event');
        }

        // Get administrator email or custom email from plugin parameters
        $config = Factory::getConfig();
        $adminEmail = $config->get('mailfrom');
        $customEmail = $this->params->get('notification_email', '');
        $recipientEmail = !empty($customEmail) ? $customEmail : $adminEmail;

        // Determine event type
        $eventType = ($data['RecordType'] === 'Bounce') ? 'Email Bounce' : 'Spam Complaint';

        // Prepare email details
        $subject = "[Postmark] {$eventType} Notification";
        $body = "An event has been received:\n\n" .
                "Recipient: " . htmlspecialchars($data['Email']) . "\n" .
                "Event Type: " . htmlspecialchars($eventType) . "\n" .
                "Description: " . htmlspecialchars($data['Description'] ?? 'No description provided') . "\n" .
                "Bounced At: " . htmlspecialchars($data['BouncedAt'] ?? 'N/A') . "\n" .
                "Server ID: " . htmlspecialchars($data['ServerID'] ?? 'N/A') . "\n" .
                "Message Stream: " . htmlspecialchars($data['MessageStream'] ?? 'N/A') . "\n\n";

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
