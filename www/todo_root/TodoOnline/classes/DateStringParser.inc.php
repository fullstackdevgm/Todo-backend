<?php
/**
 * DateStringParser Class, v.1.0
 * 
 *   Main features:
 *       - Natural Language Parsing
 *       - Time Ago
 *   
 *   Documentation:
 * 
 *      nltotime($string, $format = NULL)
 *          - pass in any form of string, and $format according to PHP Date (http://php.net/manual/en/function.date.php)
 *   
 *      nlp_filtered_string()
 *          - returns string after nltotime was called, removes parsable words. See example.
 * 
 *      time_ago($string, $granularity = 2)
 *          - pass in any date format into $string, will automatically parse it. Granularity determines how much information will be displayed.
 * 
 *   Examples and Use Cases:
 *       1) Include the class, and create the object
 *          
 *          include_once('DateStringParser.inc.php');
 *          $parser = new DateStringParser();
 *       
 *       2) Natural Language Parsing
 * 
 *          // outputs "2012-01-06"
 *          echo $parser->nltotime("tomorrow i am going to grab lunch", "Y-m-d");
 * 
 *          // outputs "i am going to grab lunch"
 *          echo $parser->nlp_filtered_string();
 * 
 *       3) Time Ago
 * 
 *          // outputs "one year, 4 days, 59 minutes ago"
 *          echo $parser->time_ago("2011-1-2 03:00:00", 3);
 *          
 *          // outputs "one year ago"
 *          echo $parser->time_ago("2011-1-2 03:00:00", 1);
 * 
 * @author Terence D. Pae <terencepae@gmail.com>
 * @version 1.0
 */
class DateStringParser {
	// by default, php parsing engine parses "i" and "at" as numeric value. To exclude more words from getting inserted into parsing engine, please add here.
	private $excluded_parse_words = array('i', 'at');
	
	// since "12pm," and "today," are not parsable, we exclude the end char if they match these.
	private $excluded_end_chars = array('.', '!', ',', '?');
	
	// performance threshold for parsing.
	private $threshold = 5;
	
	// used for time ago function, just a word "ago"
	private $time_ago = 'ago';
	
	// this is the formatting used for time ago function. Keep %d for numeric value.
	private $time_ago_format = array(
		'seconds' => array(
			'singular' => 'one second',
			'plural' => '%d seconds'
			),
		'minutes' => array(
			'singular' => 'one minute',
			'plural' => '%d minutes'
			),
		'hours' => array(
			'singular' => 'one hour',
			'plural' => '%d hours'
			),
		'days' => array(
			'singular' => 'one day',
			'plural' => '%d days'
			),
		'weeks' => array(
			'singular' => 'one week',
			'plural' => '%d weeks'
			),
		'months' => array(
			'singular' => 'one month',
			'plural' => '%d months'
			),
		'years' => array(
			'singular' => 'one year',
			'plural' => '%d years'
			)
	);
	
	// private variables, DO NOT EDIT
	private $filtered_words = array();
	private $raw_input;
	
	// an empty constructor :)
	function __construct() {

	}

	public function nltotime($string, $format = NULL) {
		$this->raw_input = $string;
		if ($format != NULL) {
			return date($format, $this->get_parsed_time($string));
		} else {
			return $this->get_parsed_time($string);
		}
	}
	
	public function nlp_filtered_string() {
		return $this->build_string($this->filtered_words);
	}
	
	public function time_ago($string, $granularity = 2) {
		$parsed_time = strtotime($string);
		
		if (!$parsed_time) {
			return FALSE;
		}
		
		$difference = time() - $parsed_time;
		$units = array (
			'years' => 		31536000,
			'weeks' => 		604800,
			'days' => 		86400,
			'hours' => 		3600,
			'minutes' => 	60,
			'seconds' => 	1
		);
		$output = '';
		foreach($units as $key => $value) {
			if ($difference >= $value) {
				$output .= ($output ? ', ' : '') . $this->time_ago_format($key, floor($difference / $value));
				$difference %= $value;
				$granularity--;
			}
			if ($granularity == 0) {
				break;
			}
		}
		if ($output) {
			$output .= ' '.$this->time_ago;
		}
		return ($output ? $output : FALSE);
	}
	
	private function time_ago_format($type, $value) {
		if ($value == 1) {
			return $this->time_ago_format[$type]['singular'];
		} else {
			return str_replace('%d', $value, $this->time_ago_format[$type]['plural']);
		}
	}
	
	private function get_parsed_time($data) {
		if (is_array($data)) {
			$raw_input_array = $data;
			$raw_input_string = $this->build_string($data);
		} else {
			$raw_input_array = $this->build_array($data);
			$raw_input_string = $data;
		}
		
		$parsable_words = array();
		$this->filtered_words = array();
		
		if (count($raw_input_array) < $this->threshold) {
			// check if raw string is parsable.
			if ($this->parse_word($raw_input_string)) {
				return $this->parse_word($raw_input_string);
			}
		}
		
		// loop through each word, find parsable words.
		foreach($raw_input_array as $word) {
			if (!in_array(strtolower($word), $this->excluded_parse_words) && ($this->parse_word($word))) {
				$parsable_words[] = $this->strip_ending_char($word);
			} else {
				$this->filtered_words[] = $word;
			}
		}
		return $this->parse_array($parsable_words);
	}
	
	private function build_string($array) {
		return implode(" ", $array);
	}
	
	private function build_array($string) {
		return explode(" ", $string);
	}
	
	private function parse_array($array) {
		return strtotime($this->build_string($array));
	}
	
	private function strip_ending_char($word) {
		if (in_array(substr($word, -1, 1), $this->excluded_end_chars)) {
			return substr($word, 0, -1);
		} else {
			return $word;
		}
	}
	private function parse_word($word) {
		return strtotime($this->strip_ending_char($word));
	}
}


