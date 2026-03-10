<?php
/*
 * Amazon SNS example script
 *
 * Creates or loads a topic, subscribes an email endpoint, and can publish
 * a sample message once the subscription is confirmed. Configure the
 * following before running:
 *   1. Provide AWS credentials via environment variables
 *      (AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY) or by defining the
 *      AWS_ACCESS_KEY / AWS_PRIVATE_KEY constants below.
 *   2. Set SNS_TEST_EMAIL or edit $your_email.
 *   3. Optionally set AWS_REGION or the SNS_TOPIC_* overrides.
 */

// Uncomment the next two lines if you prefer defining credentials here instead of using env vars.
//define('AWS_ACCESS_KEY', '<your amazon access key>');
//define('AWS_PRIVATE_KEY', '<your amazon secret key>');

//define('AWS_REGION', 'us-east-1'); // Optional: override default region/host for SNS.

$your_email = getenv('SNS_TEST_EMAIL') ?: 'your_email@example.com';
$topic_title = getenv('SNS_TOPIC_NAME') ?: 'hello-world';
$topic_display_name = getenv('SNS_TOPIC_DISPLAY_NAME') ?: 'Amazon SNS Test';

if ($your_email === 'your_email@example.com') {
    fwrite(STDERR, "Set SNS_TEST_EMAIL or edit example.php with your email address.\n");
    exit(1);
}

require_once 'class.amazon_sns_helper.php';
require_once 'class.amazon_sns_topic.php';
require_once 'class.amazon_sns_subscriber.php';

$topic = amazon_sns_topic::create($topic_title);

print 'Created/loaded topic ARN: ' . $topic->getArn() . "\n";

$topic->setDisplayName($topic_display_name);

// Create the new subscription - this will email you an opt-in message.
amazon_sns_subscriber::create($topic, $your_email);

print "Subscription requested. Check {$your_email} for the confirmation link.\n";

$publishAfterSubscribe = getenv('SNS_PUBLISH_AFTER_SUBSCRIBE');
if (!empty($publishAfterSubscribe)) {
    print "Waiting 5 seconds before attempting to publish...\n";
    sleep(5);
    $topic->publish('Amazon SNS Test message', 'Hello, world!');
    print "Publish request sent. Ensure the subscription is confirmed to receive the email.\n";
} else {
    print "Set SNS_PUBLISH_AFTER_SUBSCRIBE=1 to automatically send a test message after subscribing.\n";
}
