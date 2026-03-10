# Amazon SNS client for PHP

A tiny, dependency-free helper for creating topics, managing subscriptions, and publishing messages via Amazon Simple Notification Service (SNS). The codebase predates the AWS SDK, so it focuses on transparency and a minimal surface area rather than feature parity.

## Requirements

- PHP 5.6+ with the cURL and SimpleXML extensions enabled
- OpenSSL (for HTTPS connections)
- AWS credentials with SNS permissions

## Configuration

You can provide credentials and region information in two ways:

1. Preferred (environment variables):
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_REGION` (defaults to `us-east-1` if omitted)
   - Optional: `AWS_SNS_HOST` to point at a custom endpoint
2. Legacy constants (define before including the classes):
   - `AWS_ACCESS_KEY`
   - `AWS_PRIVATE_KEY`
   - `AWS_REGION`

The helper automatically prefers explicitly defined constants, then falls back to environment variables.

## Usage

1. Update your credentials/region as described above.
2. Edit or export the helper environment variables for the email, topic name, and display name if the defaults do not suit you.
3. Run the sample script to create a topic and subscription:
   ```bash
   php example.php
   ```
4. Confirm the subscription from the email sent by SNS.
5. (Optional) Set `SNS_PUBLISH_AFTER_SUBSCRIBE=1` before running the example to automatically send a test message after a short delay.

See [example.php](example.php) for a complete walkthrough.

## Helper capabilities

- `amazon_sns_topic::create($name)` – create or retrieve a topic by name and get the ARN back immediately.
- `amazon_sns_topic::publish($subject, $message, array $extra = [])` – publish a message with optional SNS parameters (e.g., `MessageStructure`).
- `amazon_sns_topic::setDisplayName($displayName)` and `delete()` – manage topic metadata and lifecycle.
- `amazon_sns_subscriber::create($topic, $endpoint, $protocol = 'email')` – create new subscriptions with basic validation.
- `amazon_sns_subscriber::confirm($token, $topicArn = null)` / `unsubscribe($subscriptionArn)` / `listByTopic($topic)` – utilities for managing subscription state.

All helper calls now throw `RuntimeException` (or `InvalidArgumentException` for bad input) when AWS returns an error or if the HTTP request fails.

## Notes

- AWS Signature Version 2 is still accepted by SNS and keeps the implementation simple. If you need Signature Version 4 or broader service coverage, migrate to the official AWS SDK for PHP.
- The library intentionally avoids composer autoloading; simply `require_once` the three class files when needed.
- Contributions that improve robustness while keeping the footprint small are welcome.
