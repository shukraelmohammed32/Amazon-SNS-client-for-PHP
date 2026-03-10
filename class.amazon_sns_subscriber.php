<?php
/*
 * Amazon SNS Subscriber
 * 
 * Allows creating of a new subscriber for a topic
 * 
 * @author Russell Smith <russell.smith@ukd1.co.uk>
 */
class amazon_sns_subscriber {

	/*
	 * Create a new subscriber
	 * 
	 * @param amazon_sns_topic topic to create the subscriber in
	 * @param string endpoint - aka email address for most people?
	 * @param string protocol to use (see docs)
	 */
	public static function create (amazon_sns_topic $topic, $endpoint, $protocol = 'email', array $extraParams = array()) {
		if (empty($endpoint)) {
			throw new InvalidArgumentException('A subscription endpoint is required.');
		}
		$params = array_merge(array('Action'=>'Subscribe', 'TopicArn'=>$topic->getArn(), 'Endpoint'=>$endpoint, 'Protocol'=>$protocol), $extraParams);
		return amazon_sns_helper::request($params);
	}

	/*
	 * Confirm a subscription using the token provided by AWS.
	 */
	public static function confirm ($token, $TopicArn = null) {
		if (empty($token)) {
			throw new InvalidArgumentException('Subscription confirmation token is required.');
		}
		$params = array('Action'=>'ConfirmSubscription', 'Token'=>$token);
		if (!empty($TopicArn)) {
			$params['TopicArn'] = $TopicArn;
		}
		return amazon_sns_helper::request($params);
	}

	/*
	 * Remove a subscription by ARN.
	 */
	public static function unsubscribe ($SubscriptionArn) {
		if (empty($SubscriptionArn)) {
			throw new InvalidArgumentException('SubscriptionArn is required to unsubscribe.');
		}
		return amazon_sns_helper::request(array('Action'=>'Unsubscribe', 'SubscriptionArn'=>$SubscriptionArn));
	}

	/*
	 * List subscriptions for a topic.
	 */
	public static function listByTopic (amazon_sns_topic $topic, array $extraParams = array()) {
		$params = array_merge(array('Action'=>'ListSubscriptionsByTopic', 'TopicArn'=>$topic->getArn()), $extraParams);
		return amazon_sns_helper::request($params);
	}

}