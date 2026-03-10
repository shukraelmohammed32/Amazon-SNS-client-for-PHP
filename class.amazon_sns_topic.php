<?php
/*
 * Amazon SNS Topic
 * 
 * Allows creating of new topics, deleting, setting the display name and publishing to existing topics
 * 
 * @author Russell Smith <russell.smith@ukd1.co.uk>
 */
class amazon_sns_topic {

	private $TopicArn;

	/*
	 * Create a new topic object
	 * 
	 * param string the topicarn you got previously after creating a new topic
	 */
	public function __construct ($TopicArn = null) {

		if (!is_null($TopicArn)) {
			$this->TopicArn = $TopicArn;
		}

	}

	/**
	 * Build a topic instance from a known ARN.
	 *
	 * @param string $TopicArn
	 * @return amazon_sns_topic
	 */
	public static function fromArn ($TopicArn) {
		return new self($TopicArn);
	}

	/**
	 * Publish a message to this topic.
	 * 
	 * @param string $subject subject of the message (for emails)
	 * @param string $message message body
	 * @param array $extraParams additional SNS parameters (e.g. MessageStructure)
	 * @return SimpleXMLElement
	 */
	public function publish ($subject, $message, array $extraParams = array()) {
		$this->assertTopicArn();
		$params = array_merge(array('Action'=>'Publish', 'TopicArn'=>$this->TopicArn, 'Subject'=>$subject, 'Message'=>$message), $extraParams);
		return amazon_sns_helper::request($params);
	}

	/*
	 * Set the display name for this topic
	 * 
	 * @param string the human readable display name
	 */
	public function setDisplayName ($DisplayName) {
		$this->assertTopicArn();
		return amazon_sns_helper::request(array('Action'=>'SetTopicAttributes', 'TopicArn'=>$this->TopicArn, 'AttributeName'=>'DisplayName', 'AttributeValue'=>$DisplayName));
	}

	/*
	 * Return the arn for the current topic
	 * 
	 * @return string
	 */
	public function getArn () {
		return $this->TopicArn;
	}

	/*
	 * Ask amazon to delete the current topic 
	 */
	public function delete () {
		$this->assertTopicArn();
		return amazon_sns_helper::request(array('Action'=>'DeleteTopic', 'TopicArn'=>$this->TopicArn));
	}

	/**
	 * Create a new topic with this name
	 * 
	 * @param string topic name
	 * @return amazon_sns_topic
	 */
	public static function create ($name) {
		$response = amazon_sns_helper::request(array('Action'=>'CreateTopic', 'Name'=>$name));

		return new amazon_sns_topic($response->CreateTopicResult->TopicArn);
	}

	/**
	 * Ensure an ARN is present before making a request.
	 */
	private function assertTopicArn () {
		if (empty($this->TopicArn)) {
			throw new InvalidArgumentException('TopicArn is not set for this topic instance.');
		}
	}

}