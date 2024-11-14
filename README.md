<a href="https://github.com/catalyst/moodle-tool_emailutils/actions?query=branch%3AMOODLE_39_STABLE">
<img src="https://github.com/catalyst/moodle-tool_emailutils/actions/workflows/ci.yml/badge.svg?branch=MOODLE_39_STABLE" >
</a>

# Email utilities for Moodle

This plugin is intended to become a place for a suite of semi related email utilities
to help improve the securty, scalability, and performance of emails.

Over time some of the features may be pulled into Moodle core.

A rough roadmap with no set timelines:

* Handle incoming email complaints via AWS SES Complaints
* Email round trip monitoring, eg sends an email to itself and asserts it arrives and in the right shape
* DMARC email processing and reporting


## Branches
The following lists the supported branch to use based on your Moodle version.

| Moodle version | Branch            |
|----------------|-------------------|
| Moodle 3.9-4.3 | MOODLE_39_STABLE  |
| Moodle 4.4+    | MOODLE_404_STABLE |

## Installation

```
git clone git@github.com:catalyst/moodle-tool_emailutils.git admin/tool/emailutils
```

## DKIM Manager

In the admin settings there is a new admin page for creating and managing DKIM
certificate pairs, showing the DNS records, validating, activating and testing them.

Admin > Server > Email > DKIM manager

Note that DKIM support should work in Totara if you have backported MDL-69513

https://tracker.moodle.org/browse/MDL-69513

## AWS SES Complaints Plugin for Moodle

This plugin is for use with the AWS SES service.

The complaints list (/admin/tool/emailutils/index.php) is not implemented yet.

### Configuring AWS SES

AWS Simple Email Service (SES) creates Simple Notification Service (SNS) topics
for both bounces and complaints on each domain, a SNS topic is basically a
message channel, when you publish a message to a topic, it fans out the message
to all subscribed endpoints.

You can check the SNS topics on a domain by going into SES Management
Console, clicking on the domain name and then going into the Notification
section, the topics can also be found on the SNS console and they would be
called your-domain-ses-notifications and your-domain-ses-complaints, the idea
is to create subscriptions for the plug-in to this topics.

Before you can create the subscriptions you will need to create a username and
password on the plug-in configuration:

    https://yoursite.com/admin/category.php?category=tool_emailutils

First go into the SNS Console and click on Subscriptions and then Create
subscription, on the Topic ARN textbox you can type in the name or the ARN of
one of the topics, you will need to do one at the time.

On protocol you should choose HTTPS.

The endpoint needs to include the username and password you created earlier
separated  by “:”  and  followed by “@” and finally the plug-in endpoint,
something like:

    https://username:password@yoursite.com/admin/tool/emailutils/client.php

Optional settings are fine as default, please notice that both subscriptions,
notifications and complaints, should have the same endpoint.

Once they have been created you can find the subscriptions on the SNS console
Subscriptions section, they should automatically change status from “Pending
confirmation” to “Confirmed” after few minutes, if this doesn’t happen something
went wrong during the set up  process, the most common error is a typo on the
endpoint, subscription can not be change once created but you can always create
a new subscription with the right endpoint.
