<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Amazon SNS Notification Class
 *
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

namespace tool_emailutils;

/**
 * Amazon SNS Notification Class
 *
 * Parses Amazon SES complaints and bounces contained in SNS Notification messages.
 */
class sns_notification {

    /**
     * SNS Message
     * @var mixed
     */
    public $message;

    /**
     * Unprocessed SNS Message
     * @var mixed
     */
    public $messageraw;

    /**
     * Set SNS Message
     * @param mixed $message SNS Message
     * @return sns_notification
     */
    public function set_message($message): sns_notification {
        $this->messageraw = $message;
        $this->message = json_decode($message, true);
        return $this;
    }

    /**
     * Get SNS Message
     * @return mixed SNS Message
     */
    public function get_message() {
        if (isset($this->message)) {
            return $this->message;
        } else {
            http_response_code(400); // Invalid request.
            exit;
        }
    }

    /**
     * Get the raw message as provided by AWS
     * @return string Message as string
     */
    public function get_raw_message() {
        if (isset($this->messageraw)) {
            return $this->messageraw;
        } else {
            http_response_code(400); // Invalid request.
            exit;
        }
    }

    /**
     * Get SNS Message Type
     * @return string SNS Message Type
     */
    public function get_type(): string {
        return $this->message['notificationType'];
    }

    /**
     * Get the email address that sent out the offending email
     * @return string Source email address
     */
    public function get_source_email(): string {
        return $this->message['mail']['source'];
    }

    /**
     * Get the IP address of the server that sent out the offending email
     * @return string Source IP address
     */
    public function get_source_ip(): string {
        return $this->message['mail']['sourceIp'];
    }

    /**
     * Get the Amazon Resource Name that sent out the offending email
     * @return string Source ARN
     */
    public function get_source_arn(): string {
        return $this->message['mail']['sourceArn'];
    }

    /**
     * Get the email address that complained about or bounced the source email
     * @return string Destination email address
     */
    public function get_destination(): string {
        return $this->message['mail']['destination'][0];
    }

    /**
     * Gets the type of bounce that occured
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#bounce-types
     * @return string Bounce type
     */
    public function get_bouncetype(): string {
        if ($this->is_bounce()) {
            return $this->message['bounce']['bounceType'];
        }
        return '';
    }

    /**
     * Gets the subtype of bounce that occured
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#bounce-types
     * @return string Bounce sub type
     */
    public function get_bouncesubtype(): string {
        if ($this->is_bounce()) {
            return $this->message['bounce']['bounceSubType'];
        }
        return '';
    }

    /**
     * Gets the type of complaint that occured
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#complaint-types
     * @return string Complaint type
     */
    public function get_complainttype(): string {
        if ($this->is_complaint()) {
            // Feedback type is only available if a feedback report is attached to the complaint.
            return $this->message['complaint']['complaintFeedbackType'] ?? '';
        }
        return '';
    }

    /**
     * Gets the subtype of complaint that occured
     * This should either be field can either be null or OnAccountSuppressionList
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#complaint-object
     * @return string Complaint sub type
     */
    public function get_complaintsubtype(): string {
        if ($this->is_complaint()) {
            return $this->message['complaint']['complaintSubType'] ?? '';
        }
        return '';
    }

    /**
     * Returns all the message subtypes as an array
     * @return string subtypes as a array
     */
    protected function get_subtypes(): array {
        $subtypes = [];
        if ($this->is_complaint()) {
            $subtypes = [
                $this->get_complainttype(),
                $this->get_complaintsubtype(),
            ];
        } else if ($this->is_bounce()) {
            $subtypes = [
                $this->get_bouncetype(),
                $this->get_bouncesubtype(),
            ];
        }
        return array_filter($subtypes);
    }

    /**
     * Is the message about a complaint?
     * @return bool Is complaint?
     */
    public function is_complaint(): bool {
        return $this->get_type() === sns_client::COMPLAINT_TYPE;
    }

    /**
     * Is the message about a bounce?
     * @return bool Is bounce?
     */
    public function is_bounce(): bool {
        return $this->get_type() === sns_client::BOUNCE_TYPE;
    }

    /**
     * Return the message as a string
     * Eg. "Type about x from y"
     * @return string Message as string
     */
    public function get_messageasstring(): string {
        if ($this->is_complaint() || $this->is_bounce()) {
            $subtypes = $this->get_subtypes();
            $subtypestring = !empty($subtypes) ? ' (' . implode(':', $subtypes) . ')' : '';
            $type = $this->get_type() . $subtypestring;
            return $type . ' about ' . $this->get_source_email() . ' from ' . $this->get_destination();
        } else {
            http_response_code(400); // Invalid request.
            exit;
        }
    }

    /**
     * Print the message string
     * @return sns_notification
     */
    public function print_log(): sns_notification {
        echo $this->get_messageasstring() . "\n";
        return $this;
    }
}
