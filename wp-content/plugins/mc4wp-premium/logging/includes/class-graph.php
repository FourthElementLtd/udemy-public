<?php

class MC4WP_Graph
{

    /**
    * @var string
    */
    private $table_name;

    /**
     * @var array
     */
    private $initial_data = array();

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    public $range = 'this_week';

    /**
     * @var
     */
    public $start_date;

    /**
     * @var
     */
    public $end_date;

    /**
     * @var string
     */
    public $step_size = 'day';

    /**
     * @var array
     */
    public $datasets = array();

    /**
     * @var
     */
    private $day;

	/**
	 * @var string
	 */
    private $date_format_graph = 'Y, m j';


    /**
	* @param array $config
    */
    public function __construct(array $config = array())
    {
        // store config
        if (isset($config['range'])) {
            $this->range = $config['range'];
        }

        $this->config = $config;

        // set table prefix
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mc4wp_log';
    }

    /**
    * Initialize various settings to use
    */
    public function init()
    {
        $start_of_week = (int) get_option('start_of_week', 1);
        $timezone = wp_timezone();
	    $utc_offset = (int) get_option('gmt_offset', 0);
	    $now = new DateTime('now', $timezone);
	    $this->day = $now->format('d');

	    switch ($this->range) {
            case 'today':
                $start_date = new DateTime('today midnight', $timezone);
	            $end_date = (clone $start_date)->modify('+1 day');
                $this->step_size = 'hour';
                $this->date_format_graph = 'g a';
            break;

            case 'yesterday':
	            $start_date = new DateTime('yesterday midnight', $timezone);
	            $end_date = (clone $start_date)->modify('+1 day');
                $this->step_size = 'hour';
	            $this->date_format_graph = 'g a';
            break;

			case 'this_week':
			default:
	            $start_date = new DateTime('last sunday midnight', $timezone);
				$start_date->modify(sprintf('+%d days', $start_of_week));
	            $end_date = (clone $start_date)->modify('+7 days');
				$this->step_size = 'day';
	            $this->date_format_graph = 'M j';
				break;

			case 'last_week':
				$start_date = new DateTime('last sunday midnight', $timezone);
				$start_date->modify(sprintf('-%d days', 7 - $start_of_week));
				$end_date = (clone $start_date)->modify('+7 days');
				$this->step_size = 'day';
				$this->date_format_graph = 'M j';
				break;

			case 'this_month':
				$start_date = new DateTime("first day of this month midnight", $timezone);
				$end_date = (clone $start_date)->modify('+1 month');
				$this->step_size = 'day';
				$this->date_format_graph = 'M j';
				break;

            case 'last_month':
	            $start_date = new DateTime("first day of last month midnight", $timezone);
	            $end_date = (clone $start_date)->modify('+1 month');
                $this->step_size = 'day';
	            $this->date_format_graph = 'M j';
           	 break;

            case 'this_quarter':
	            $month = floor($now->format('m') / 3) * 3 + 1;
                $start_date = new DateTime(gmdate(sprintf('Y-%d-01 00:00:00', $month)), $timezone);
	            $end_date = (clone $start_date)->modify('+3 months');
                $this->step_size = 'day';
	            $this->date_format_graph = 'M j';
            break;

			case 'last_quarter':
				$month = floor($now->format('m') / 3) * 3 + 1 - 3;
				$start_date = new DateTime(gmdate(sprintf('Y-%d-01 00:00:00', $month)), $timezone);
				$end_date = (clone $start_date)->modify('+3 months');
				$this->step_size = 'day';
				$this->date_format_graph = 'M j';
				break;

			case 'this_year':
				$start_date = new DateTime('1 january this year midnight', $timezone);
				$end_date = (clone $start_date)->modify('+1 year');
				$this->step_size = 'month';
				$this->date_format_graph = 'M';
				break;

            case 'last_year':
	            $start_date = new DateTime('1 january last year midnight', $timezone);
	            $end_date = (clone $start_date)->modify('+1 year');
                $this->step_size = 'month';
	            $this->date_format_graph = 'M';
            break;

            case 'custom':
                $start_date = new DateTime(implode('-', array( $this->config['start_year'], $this->config['start_month'], $this->config['start_day'] )) . ' 00:00:00', $timezone);
                $end_date = new DateTime(implode('-', array( $this->config['end_year'], $this->config['end_month'], $this->config['end_day'] )) . ' 23:59:59', $timezone);
                $this->step_size = $this->calculate_step_size($start_date, $end_date);
                $this->date_format_graph = $this->get_graph_date_format();
                $this->day = $this->config['start_day'];
                break;
        }

        // If start is before end, revert back to "week" range and re-init.
        if ($start_date >= $end_date) {
            add_settings_error('mc4wp', 'mc4wp-stats', __('End date can\'t be before the start date', 'mailchimp-for-wp'));
            $this->config['range'] = 'this_week';
            $this->init();
            return;
        }

        // modify start and end date to account for UTC offset
	    // we invert the offset here so that MySQL includes the proper results
        $this->start_date = (clone $start_date)->modify(sprintf('+%d hours', $utc_offset * -1))->format('Y-m-d H:i:s');
        $this->end_date = (clone $end_date)->modify(sprintf('+%d hours', $utc_offset * -1))->format('Y-m-d H:i:s');

        // setup array of dates with 0's
        $current = $start_date;
        $this->initial_data = array();
        while ($current < $end_date) {
            $key = $current->format('Y-m-d H:i:s');
            $this->initial_data[$key] = 0;
            $current->modify("+1 {$this->step_size}");
        }

        $this->query();
    }

    /**
     * Calculates an appropriate step size
     *
    * @param DateTime $start
    * @param DateTime $end
    *
    * @return string
    */
    public function calculate_step_size(DateTime $start, DateTime $end)
    {
        $difference = $end->format('U') - $start->format('U');
        $day_in_seconds = 86400;
        $month_in_seconds = 2592000;

        if ($difference > ($month_in_seconds * 6)) {
            $step = 'month';
        } elseif ($difference > $day_in_seconds) {
            $step = 'day';
        } else {
            $step = 'hour';
        }

        return $step;
    }

    /**
     * @return string
     */
    protected function get_date_format()
    {
    	switch ($this->step_size) {
		    case 'hour': return '%Y-%m-%d %H:00:00'; break;
		    default:
		    case 'day': return '%Y-%m-%d 00:00:00'; break;
		    case 'month': return "%Y-%m-{$this->day} 00:00:00"; break;
	    }
    }

	/**
	 * @return string
	 */
	protected function get_graph_date_format()
	{
		switch ($this->step_size) {
			case 'hour': return 'g a'; break;
			default:
			case 'day': return 'M j'; break;
			case 'month': return "M Y"; break;
		}
	}

    /**
     * @return array
     */
    public function query()
    {
        $datasets = array();

        // forms
        $forms = mc4wp_get_forms();

        foreach ($forms as $form) {
            $data = $this->get_data_for_form($form->ID);
            if (!is_array($data)) {
            	continue;
			}

            $dataset = array(
                'label' => sprintf('%s', esc_html($form->name)),
                'normalized' => true,
                'data' => $data,
            );
            $datasets[] = $dataset;
        }

        // integrations
        $integrations = mc4wp_get_integrations();
        foreach ($integrations as $integration) {
            $data = $this->get_data_for_type($integration->slug);
			if (!is_array($data)) {
				continue;
			}

            $dataset = array(
                'label' => $integration->name,
                'normalized' => true,
                'data' => $data,
            );
            $datasets[] = $dataset;
        }

        // Top Bar
        $data = $this->get_data_for_type('mc4wp-top-bar');
	    if (is_array($data)) {
			$dataset = array(
				'label' => 'Top Bar',
				'normalized' => true,
				'data' => $data,
			);
			$datasets[] = $dataset;
		}

        $this->datasets = $datasets;
    }

    private function format_data(array $results)
    {
    	$counts = $this->initial_data;
    	$total = 0;

    	// add database results to counts array
    	foreach ($results as $row) {
			$counts[$row->date_group] = (int) $row->count;
			$total += (int) $row->count;
	    }

    	if ($total === 0) {
    		return false;
	    }

    	// format data for use in Chart.js graph
	    $data = array();
    	foreach ($counts as $date => $count) {
    		$data[] = array(
			    'x' => gmdate($this->date_format_graph, strtotime($date)),
			    'y' => $count
		    );
	    }

		return $data;
    }

    public function get_data_for_type($type)
    {
		global $wpdb;
        $sql = "SELECT COUNT(*) AS count, DATE_FORMAT(DATE_ADD(datetime, INTERVAL %d HOUR), %s) AS date_group FROM `{$this->table_name}` WHERE `type` = %s AND datetime >= %s AND datetime <= %s GROUP BY date_group ORDER BY date_group ASC";
        $query = $wpdb->prepare($sql, (int) get_option('gmt_offset', 0), $this->get_date_format(), $type, $this->start_date, $this->end_date);
        $totals = $wpdb->get_results($query);
        return $this->format_data($totals);
    }

    public function get_data_for_form($form_id)
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) AS count, DATE_FORMAT(DATE_ADD(datetime, INTERVAL %d HOUR), %s) AS date_group FROM `{$this->table_name}` WHERE `related_object_ID` = %d AND `type` = %s AND datetime >= %s AND datetime <= %s GROUP BY date_group ORDER BY date_group ASC";
        $query = $wpdb->prepare($sql,  (int) get_option('gmt_offset', 0), $this->get_date_format(), $form_id, 'mc4wp-form', $this->start_date, $this->end_date);
        $totals = $wpdb->get_results($query);
        return $this->format_data($totals);
    }
}
