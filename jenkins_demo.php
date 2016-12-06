<?php

class JenkinsDemo
{

    private $baseUrl;

    private $jenkins = null;

	function __construct(baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	/*
	* Function to validate Curl
	*/
	private function validateCurl($curl, $errorMessage) {

        if (curl_errno($curl)) {
             return json_encode(['errMsg'=> $errorMessage]);
        }
        $info = curl_getinfo($curl);

        if ($info['http_code'] === 403) {
           return json_encode(['errMsg'=> 'Access Denied [HTTP status code 403] to %s"', $info['url']]);
        }
    }

    /*
	* Initialize jenkins
    */
	private function initialize()
    {
        if (null !== $this->jenkins) {
            return;
        }

        $curl = curl_init($this->baseUrl . '/api/json');

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $this->validateCurl($curl, sprintf('Error during getting list of jobs on %s', $this->baseUrl));

        $this->jenkins = json_decode($ret);
        if (!$this->jenkins instanceof \stdClass) {
            return  json_encode(['errMsg'=> 'Error during json_decode']);
        }
    } 

    /*
	* Get array of all Jobs
    */
    public function getAllJobs()
    {
        $this->initialize();
        $jobs = array();
        foreach ($this->jenkins->jobs as $job) {
            $jobs[$job->name] = array(
                'name' => $job->name,
                'checked' => $job->timestamp,
                'status' => $job->building
            );
            $this->DbInsert($jobs);
        }
        return json_encode(['msg'=> count($jobs) . "inserted into the database"]);
    }
    
    private function DbInsert($jobs){
    	$db = new SQLite3('db/mydb');
    	$db->exec("DROP table if exists jenkins");
    	$db->exec("creatw table jenkins (name varchar(255), checked datetime, status int(3)) ");
    	$db->exec("insert into jenkins(name,checked,status)values($jobs['name'],$jobs['checked'],$jobs['status'])");
    }
}

?>