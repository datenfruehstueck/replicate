<?php

namespace ReplicationManagement;

class Replication extends AbstractModel {
	protected $table = 'replication';
	public $orig_doi;
	public $orig_link_alternative;
	public $orig_citation;
	public $orig_abstract;
    public $repl_author_last;
	public $repl_author_first;
	public $repl_author_etal;
    public $repl_type;
    public $repl_level;
	public $repl_year;
    public $repl_title;
    public $repl_abstract;
    public $result;
    public $result_details;
	public $repl_author_emails;
    public $active;

    public $link_internal;
    public $link_external;
    public $result_numeric;

    public $result_dict = [
        'Successful! Original results replicated/reproduced successfully.' => 5,
        'Mostly successful! Original and replicated/reproduced results match but only in effect direction.' => 4,
        'Somewhat successful! Original and replicated/reproduced results match for some but not for other aspects.' => 3,
        'Rather unsuccessful! Most original results did not replicate/reproduce successfully.' => 2,
        'Unsuccessful! Original results did not replicate/reproduce whatsoever.' => 1,
        'Not replicable/reproducible! Data and/or methods could not be employed in comparable fashion.' => 0,
        'Success not determinable (elaborate!):' => -9
    ];

    public $result_codes = [
        '5' => ['status' => 'success', 'emoji' => '👍'],
        '4' => ['status' => 'info', 'emoji' => '💪'],
        '3' => ['status' => 'primary', 'emoji' => '🤙'],
        '2' => ['status' => 'warning', 'emoji' => '🤞'],
        '1' => ['status' => 'danger', 'emoji' => '👎'],
        '0' => ['status' => 'dark', 'emoji' => '🖖'],
        '-9' => ['status' => 'secondary', 'emoji' => '👉']
    ];

    function __construct(&$db, &$config, $row) {
        parent::__construct($db, $config, $row);

        $this->result_numeric = $this->result_dict[$this->result];

        if($this->orig_doi != '' && $this->orig_doi !== NULL) {
            $this->link_internal = $this->orig_doi;
            $this->link_external = 'https://doi.org/'.$this->orig_doi;
        } else {
            $this->link_internal = strtolower(substr(md5($this->uid), 0, 5)).'-'.strtolower(str_replace(' ', '', $this->repl_author_last));
            $this->link_external = $this->orig_link_alternative;
        }
    }

    function get_result_code($code = 'status') {
        return $this->result_codes[$this->result_numeric][$code];
    }
}
