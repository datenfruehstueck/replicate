<?php

namespace ReplicationManagement;
require_once('abstractmodel.php');
require_once('modelhandler.php');
require_once('database.php');

class System {
    protected $config;
    protected $db;
    protected $replications;
    protected $replication;

    function __construct(&$config) {
        $this->config = $config;
        $this->db = new Database($config);

        //collect table names
        $this->config['database']['tables'] = [];
        while($row = $this->db->fetch_row('SHOW TABLES')) {
            $table = array_pop($row);
            $this->config['database']['tables'][] = $table;
            require_once($table.'.php');
        }

        //collect replications
        $this->replications = new ModelHandler($this->db, $this->config, 'replication');
        $this->replications->sql_filter = 'active = 1';
        $this->replications->sql_order = 'repl_year ASC, repl_author_last ASC, uid ASC';
        $this->replications->collect_entries();

        $this->replication = NULL;
    }

    function debug($var) {
        if(is_string($var) || is_numeric($var)) {
            echo '<code>'.$var.'</code><br>';
        } elseif(is_bool($var)) {
            echo '<code>'.intval($var).'</code><br>';
        } elseif(is_array($var)) {
            echo '<pre>'.print_r($var, TRUE).'</pre><br>';
        } else {
            echo '<pre>';
            var_dump($var);
            echo '</pre><br>';
        }
    }

    function run() {
        //preprocess URL
        $url = trim($_SERVER['REQUEST_URI']);
        $url = str_replace('?'.$_SERVER['QUERY_STRING'], '', $url);
        if(substr($url, 0, 1) == '/') {
            $url = substr($url, 1);
        }
        if(substr($url, -1) == '/') {
            $url = substr($url, 0, strlen($url)-1);
        }
        $url_parts = explode('/', $url, 2);

        //try to identify a given replication
        $linkedReplications = $this->replications->get_entries_by_field('link_internal', strtolower($url_parts[1]));
        if(count($linkedReplications) > 0) {
            $this->replication = $linkedReplications[0];
        }

        //explicit URL routing
        switch ($url_parts[0]) {
            case 'studies':
                if($this->replication === NULL) {
                    if(isset($_GET['activate'])) {
                        $this->route_activate(strtolower($url_parts[1]));
                    }
                    return $this->route_studies();
                } else {
                    return $this->route_replication();
                }
                break;
            case 'add':
                return $this->route_add();
                break;
            case 'added':
            case 'about':
            case 'info':
            default:
                if($url_parts[0] == '') {
                    return $this->route_static();
                } else {
                    return $this->route_static($url_parts[0]);
                }
                break;
        }
        return FALSE;
    }

    protected function redirect($route) {
        $url = '/'.$route;
        header('Location: '.$url);
        exit();
    }

    protected function refresh() {
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit();
    }

    protected function output($template, array $marker = [], $active = '', $title = 'Replications') {
        if($this->replication !== NULL) {
            $title = $this->replication->repl_title;
        }
        if($active == '') {
            $active = $template;
        }
        echo $this->replace('_layout', [
            'title' => $title,
            'content' => $this->replace($template, $marker),
            'active_info' => $active == 'info' ? 'active' : '',
            'active_studies' => $active == 'studies' ? 'active' : '',
            'active_about' => $active == 'about' ? 'active' : '',
            'active_add' => $active == 'add' ? 'active' : ''
        ]);
    }

    protected function replace($template, array $marker = []) {
        $template = file_get_contents('html/'.$template.'.html');
        foreach($marker as $mark => $value) {
            $template = str_replace('{{'.$mark.'}}', $value, $template);
        }
        return $template;
    }

    protected function route_static($page = 'info') {
        $this->output($page, [
            'num_total' => $this->replications->count()
        ]);
        return TRUE;
    }

    protected function route_studies() {
        $studies = '';
        if($this->replications->count() > 0) {
            foreach ($this->replications->entries as $study) {
                $studies .= '<tr>' .
                    '<td><a href="/studies/' . $study->link_internal . '">' . (($study->orig_doi == '' || $study->orig_doi === NULL) ? $study->repl_title : $study->orig_doi) . '</a></td>' .
                    '<td>' . $study->repl_author_last . ' (' . $study->repl_year . ')</td>' .
                    '<td>'.$study->repl_type.'</td>' .
                    '<td>'.$study->repl_level.'</td>' .
                    '<td><span title="' . $study->result . '">' . $study->get_result_code('emoji') . '</span></td>' .
                    '</tr>';
            }
        } else {
            $studies = '<tr><td colspan="4">Currently no studies are registered</td></tr>';
        }
        $this->output('studies', [
            'num_total' => $this->replications->count(),
            'num_successful' => count($this->replications->get_entries_by_field('result_numeric', 5)),
            'num_mostlysuccessful' => count($this->replications->get_entries_by_field('result_numeric', 4)),
            'num_somewhatsuccessful' => count($this->replications->get_entries_by_field('result_numeric', 3)),
            'num_rathersuccessful' => count($this->replications->get_entries_by_field('result_numeric', 2)),
            'num_unsuccessful' => count($this->replications->get_entries_by_field('result_numeric', 1)),
            'num_notreplicable' => count($this->replications->get_entries_by_field('result_numeric', 0)),
            'num_level_bachelor' => count($this->replications->get_entries_by_field('repl_level', 'Bachelor thesis')),
            'num_level_master' => count($this->replications->get_entries_by_field('repl_level', 'Master thesis')),
            'num_level_phd' => count($this->replications->get_entries_by_field('repl_level', 'Doctoral thesis')),
            'studies' => $studies
        ]);
        return TRUE;
    }

    protected function route_replication() {
        $this->output('replication', [
            'orig_doi' => $this->replication->orig_doi,
            'orig_link_alternative' => $this->replication->orig_link_alternative,
            'link_external' => $this->replication->link_external,
            'link_internal' => $this->replication->link_internal,
            'orig_citation' => $this->replication->orig_citation,
            'orig_abstract' => $this->replication->orig_abstract,
            'repl_author_last' => $this->replication->repl_author_last,
            'repl_author_first' => $this->replication->repl_author_first,
            'repl_author_etal' => $this->replication->repl_author_etal,
            'repl_type' => $this->replication->repl_type,
            'repl_level' => $this->replication->repl_level,
            'repl_year' => $this->replication->repl_year,
            'repl_title' => $this->replication->repl_title,
            'repl_abstract' => $this->replication->repl_abstract,
            'result' => $this->replication->result,
            'result_details' => $this->replication->result_details,
            'result_code' => $this->replication->get_result_code('status')
        ]);
        return TRUE;
    }

    protected function route_add() {
        $markers = ['message' => ''];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if(('' != $_POST['orig_doi'] || '' != $_POST['orig_link_alternative']) &&
                '' != $_POST['orig_citation'] &&
                '' != $_POST['orig_abstract'] &&
                '' != $_POST['repl_author_last'] &&
                '' != $_POST['repl_author_first'] &&
                isset($_POST['repl_level']) &&
                '' != $_POST['repl_year'] &&
                '' != $_POST['repl_title'] &&
                '' != $_POST['repl_abstract'] &&
                isset($_POST['result'])) {

                $doi = trim(str_replace(['https://doi.org/', 'https://dx.doi.org/'], '', strtolower($_POST['orig_doi'])));
                $addedReplication = $this->replications->add(new Replication($this->db, $this->config, [
                    'orig_doi' => $doi,
                    'orig_link_alternative' => ($doi == '' ? trim($_POST['orig_link_alternative']) : ''),
                    'orig_citation' => trim($_POST['orig_citation']),
                    'orig_abstract' => trim($_POST['orig_abstract']),
                    'repl_author_last' => trim($_POST['repl_author_last']),
                    'repl_author_first' => trim($_POST['repl_author_first']),
                    'repl_author_etal' => implode('; ', preg_split('/(\r?\n)+/', trim($_POST['repl_author_etal']))),
                    'repl_type' => $_POST['repl_type'],
                    'repl_level' => $_POST['repl_level'],
                    'repl_year' => intval($_POST['repl_year']),
                    'repl_title' => trim($_POST['repl_title']),
                    'repl_abstract' => trim($_POST['repl_abstract']),
                    'result' => $_POST['result'],
                    'result_details' => trim($_POST['result_details']),
					'repl_author_emails' => trim($_POST['repl_author_emails']),
                    'active' => 0
                ]));
                if ($addedReplication) {
                    @mail($this->config['mail_team'],
                        'New ' . $addedReplication->repl_type . ' added',
                        'A new ' . $addedReplication->repl_type . ' has just been added: 
'.$addedReplication->orig_citation.'
'.$addedReplication->link_external.'
>> Original study abstract: '.$addedReplication->orig_abstract.'

Future internal link: '.$this->config['main_url'].'/studies/' .$addedReplication->link_internal.' 
- Replicator: '.$addedReplication->repl_author_first.' '.$addedReplication->repl_author_last.' 
- List of other replicators: '.$addedReplication->repl_author_etal.'
- List of email addresses :'.$addedReplication->repl_author_emails.'
- Replication data: '.$addedReplication->repl_type.', '.$addedReplication->repl_level.', '.$addedReplication->repl_year.' 
- Replication: '.$addedReplication->repl_title.' 
- Replication result: '.$addedReplication->result.' 
>> Replication abstract: '.$addedReplication->repl_abstract.'

It requires manual activation to become visible. You may activate it  by using this link: 
'.$this->config['main_url'].'/studies/' . $addedReplication->link_internal.'?activate='.rawurlencode(md5($addedReplication->link_external)).' 
',
                        'From: '.$this->config['mail_team']."\r\n".'X-Mailer: PHP/' . phpversion());
                    $this->redirect('added');
                } else {
                    $markers['message'] = 'Study entry could not be created.';
                }
            } else {
                $markers['message'] = 'All fields must be filled in when submitting a replication/reproduction.';
            }
        }
        $markers['message_hidden'] = $markers['message'] == '' ? 'd-none' : '';
        $this->output('add', $markers);
        return TRUE;
    }

    protected function route_activate($study_url_part) {
        if(isset($_GET['activate'])) {
            $unfilteredReplications = new ModelHandler($this->db, $this->config, 'replication');
            $unfilteredReplications->sql_filter = 'active = 0';
            $unfilteredReplications->collect_entries();
            $linkedReplications = $unfilteredReplications->get_entries_by_field('link_internal', $study_url_part);
            if (count($linkedReplications) > 0) {
                if (rawurlencode(md5($linkedReplications[0]->link_external)) == $_GET['activate']) {
                    $linkedReplications[0]->active = 1;
                    if($linkedReplications[0]->update()) {
                        $this->redirect('studies/'.$linkedReplications[0]->link_internal);
                    }
                }
            }
        }
    }
}
