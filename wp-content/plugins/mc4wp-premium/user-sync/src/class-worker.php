<?php

namespace MC4WP\User_Sync;

use Error;
use MC4WP_Queue as Queue;
use MC4WP_Debug_Log;
use Mockery\Exception;

class Worker {

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var User_Handler
     */
    private $user_handler;

    /**
     * Worker constructor.
     *
     * @param Queue      $queue
     * @param User_Handler $user_handler
     */
    public function __construct( Queue $queue, User_Handler $user_handler ) {
        $this->queue = $queue;
        $this->user_handler = $user_handler;
    }

    /**
     * Add hooks
     */
    public function add_hooks() {
        add_action( 'mc4wp_user_sync_process_queue', array( $this, 'work' ) );
    }

    /**
     * Put in work!
     */
    public function work() {

        // We'll use this to keep track of what we've done
        $done = array();

        while( ( $job = $this->queue->get() ) ) {

           $user_id = $job->data;

            // don't perform the same job more than once
            if( ! in_array( $job->data, $done ) ) {

                // do the actual work
                try {
					$success = $this->user_handler->handle_user( $user_id );
				}catch( \Error $e ) {
                    $message = sprintf( 'User Sync: Failed to process background job. %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine() );
                    $this->get_log()->error( $message );
                } catch(\Exception $e) {
					$message = sprintf( 'User Sync: Error handling user #%d: %s', $user_id, (string) $e);
					$this->get_log()->error( $message );
				}

                // keep track of what we've done
                $done[] = $job->data;
            }

            // remove job from queue & force save for long-lived processes
            $this->queue->delete( $job );
            $this->queue->save();
        }
    }

	/**
	 * @return MC4WP_Debug_Log
	 * @throws \Exception
	 */
    private function get_log() {
        return mc4wp('log');
    }

}
