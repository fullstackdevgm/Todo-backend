<?php

include_once('TDODaemonConfig.php');

class TDODaemonLogger
{			
	private $log_path = LOG_PATH;
	private $fd;
	private $filePath;
    private $log_size_limit = LOG_SIZE_LIMIT;
    private $file_pattern;

	function __construct($module)
	{
		$this->filePath = $this->log_path . $module . '.log';
        $this->file_pattern = $this->log_path . $module . '*.log';
		$this->fd = fopen($this->filePath, "a");
	}
					
	function log($msg)
	{
        $this->logrotate();
        $str = "[" . date("Y/m/d H:i:s", mktime()) . "] " . $msg;
        fwrite($this->fd, $str . "\n");
    }

    /**
     * Log autorotate function.
     *
     * Rename current log file to new archive log file.
     * Create new log file.
     */
    function logrotate()
    {
        if (filesize($this->filePath) > $this->log_size_limit) {
            fclose($this->fd);
            $find_files = glob($this->file_pattern);
            $new_file_path = str_replace('*', '-' . sizeof($find_files), $this->file_pattern);
            rename($this->filePath, $new_file_path);
            $this->fd = fopen($this->filePath, "a");
        }
    }
	
}

?>
