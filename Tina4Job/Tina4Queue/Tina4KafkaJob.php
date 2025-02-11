<?php
//
//namespace Tina4Jobs\Tina4Queue;
//
//use RdKafka\Producer;
//use RdKafka\KafkaConsumer;
//use RdKafka\Message;
//
//class Tina4KafkaJob implements Tina4QueueInterface
//{
//    private $config;
//
//    public function __construct(array $config = [])
//    {
//        $this->config = array_merge([
//            'brokers' => 'localhost:9092',
//            'default_topic' => 'tina4jobs-queue',
//            'group.id' => 'tina4jobs-group',
//            'timeout' => 120000, // in milliseconds
//        ], $config);
//    }
//
//    /**
//     * Add a new job to the Kafka queue.
//     *
//     * @param object|string $job The job to add to the queue
//     * @param string $queue The queue (Kafka topic) to add the job to
//     */
//    public function addJob(object|string $job, string $queue = "default"): void
//    {
//        $producer = new Producer();
//        $producer->addBrokers($this->config['brokers']);
//
//        $topicName = $queue === "default" ? $this->config['default_topic'] : $queue;
//        $topic = $producer->newTopic($topicName);
//
//        $message = json_encode(['job' => $job]);
//
//        try {
//            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
//            $producer->flush(1000); // Ensure the message is sent
//        } catch (\Exception $e) {
//            error_log("Failed to add job to Kafka: {$e->getMessage()}");
//        }
//    }
//
//    /**
//     * Get the next job from the Kafka queue.
//     *
//     * @param string $queue The queue (Kafka topic) to get the job from
//     * @return object|null The next job from the queue
//     */
//    public function getNextJob(string $queue = "default"): ?object
//    {
//        $consumer = new KafkaConsumer([
//            'metadata.broker.list' => $this->config['brokers'],
//            'group.id' => $this->config['group.id'],
//            'enable.auto.commit' => 'false',
//        ]);
//
//        $topicName = $queue === "default" ? $this->config['default_topic'] : $queue;
//        $consumer->subscribe([$topicName]);
//
//        $message = $consumer->consume($this->config['timeout']);
//
//        switch ($message->err) {
//            case RD_KAFKA_RESP_ERR_NO_ERROR:
//                return unserialize(convert_uudecode($message->payload));
//            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
//                return null; // No more messages
//            case RD_KAFKA_RESP_ERR__TIMED_OUT:
//                return null; // Timeout waiting for messages
//            default:
//                error_log("Error getting job from Kafka: {$message->errstr()}");
//                return null;
//        }
//    }
//
//    /**
//     * Mark the job as completed in Kafka.
//     * Kafka doesn't natively track job status, so this method can be logged or left as a placeholder.
//     */
//    public function markJobCompleted(int $jobId): void
//    {
//        // Kafka does not have a built-in mechanism to track job completion.
//        // You can implement your custom tracking mechanism here if needed.
//        error_log("Job {$jobId} marked as completed.");
//    }
//
//    /**
//     * Mark the job as failed in Kafka.
//     * @param string $exception The exception details
//     * @param int $jobId The ID of the job
//     */
//    public function markJobFailed(string $exception, int $jobId): void
//    {
//        // Kafka does not track failures directly. Implement custom tracking if necessary.
//        error_log("Job {$jobId} failed: {$exception}");
//    }
//
//    /**
//     * Release a job back into the Kafka queue with a delay.
//     * Kafka does not natively support delayed messages, so this may require additional logic.
//     *
//     * @param object|string $job The job to release
//     * @param int $timeBetween Time in seconds before the job is released back
//     */
//    public function releaseJob(object|string $job, int $timeBetween): void
//    {
//        // Simulate a delay using sleep or implement delayed messaging using Kafka Streams.
//        sleep($timeBetween);
//
//        $this->addJob($job);
//    }
//}
