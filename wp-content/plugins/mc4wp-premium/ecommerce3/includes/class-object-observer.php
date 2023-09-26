<?php

abstract class MC4WP_Ecommerce_Object_Observer {
    /**
     * @var MC4WP_Queue
     */
    protected $queue;

    /**
     * Remove pending jobs from the queue
     *
     * @param string $method
     * @param int $object_id
     */
    protected function remove_pending_jobs($method, $object_id)
    {
        $jobs = $this->queue->all();
        foreach ($jobs as $job) {
            if ($job->data['method'] === $method && $job->data['args'][0] == $object_id) {
                $this->queue->delete($job);
            }
        }
    }

    /**
     * Add a job to the queue.
     *
     * @param string $method
     * @param int $object_id
     */
    protected function add_pending_job($method, $object_id)
    {
        $this->queue->put(
            array(
                'method' => $method,
                'args' => array( $object_id )
            )
        );

        // ensure queue event is scheduled
        _mc4wp_ecommerce_schedule_events();
    }

}