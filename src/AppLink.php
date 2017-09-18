<?php

namespace AppLink;

use DBAL\Database;

class AppLink {
    protected static $db;
    protected static $user;

    CONST TESTTABLE = 'users_test_progress';
    CONST QUESTIONSTABLE = 'theory_questions_2016';
    CONST DATAURL = 'http://www.ldcsupport.co.uk/app_online_reg/appphp/';
    
    public $testType = 'car';
    public $passmark = 43;
    public $numIncomplete = 0;
    public $numFlagged = 0;
    public $numSyncTests = 14;
    
    public $primQuestions = array();
    public $questionID = array();
    public $answers = array();
    public $qStatus = array();
    public $flagged = array();
    
    protected $questionsArray = array();
    protected $answersArray = array();
    protected $resultsArray = array();
    
    protected $newTests = false;

    /**
     * Set the database connection to be available to all of the functions
     * @param Database $db Must be an instance of the Database class
     */
    public function __construct(Database $db) {
        self::$db = $db;
        self::$user = new User(self::$db);
    }
    
    /**
     * Gets Test data from the local database
     * @param int $userID The user ID of the person you wish to get the test for
     * @param int $testID The Test ID for the test you wish to get the information for
     * @return array|boolean If the test exists the information will be returned as an array else will return false
     */
    public function getLocalTest($userID, $testID){
        return self::$db->select(self::TESTTABLE, array('user_id' => $userID, 'test_id' => $testID, 'type' => $this->testType, 'status' => 2));
    }

    /**
     * Uploads new Test data to the server 
     * @param  int $userID The user ID of the person you wish to get the upload to the server
     * @param int $testID The Test ID for the test you wish to upload to the server
     * @return boolean|array If no new test information is uploaded will return false else will return array from the HTML Data set by Glen
     */
    public function uploadTest($userID, $testID){
        if($this->getUniqueUser($userID) && ($testID <= 14)){
            $testInfo = $this->getLocalTest($userID, $testID);
            $serverTest = $this->getServerTestSummary($this->getUniqueUser($userID), $testID);
            if(($testInfo['complete'] > $serverTest['date'])){
                $testData = array();
                $this->createUploadformat($testInfo);
                $postData = array('userID' => intval($this->getUniqueUser($userID)), 'testID' => intval($testID), 'score' => intval($testInfo['totalscore']), 'dateTime' => $testInfo['complete'], 'timetaken' => $testInfo['time_taken'], 'tqa' => intval(50), 'qID' => implode(", ", $this->questionID[$testID]), 'prim' => implode(", ", $this->primQuestions[$testID]), 'ma1' => implode(", ", $this->answers[$testID][1]), 'ma2' => implode(", ", $this->answers[$testID][2]), 'ma3' => implode(", ", $this->answers[$testID][3]), 'ma4' => implode(", ", $this->answers[$testID][4]), 'ma5' => implode(", ", $this->answers[$testID][5]), 'ma6' => implode(", ", $this->answers[$testID][6]), 'questionStatus' => implode(", ", $this->qStatus[$testID]), 'flagged' => implode(", ", $this->flagged[$testID]));
                parse_str($this->getData(self::DATAURL.'uploadTest2.php', $postData), $testData);
                if($testData['done'] == 'true'){
                    //self::$user->setLastSyncDate(date('Y-m-d H:i:s'));
                    return $testID;
                }
            }
        }
        return false;
    }
    
    /**
     * Gets the test data from the server about the test ID and user ID given
     * @param int $userID The user ID of the person you wish to get the test for
     * @param int $testID The Test ID for the test you wish to get the information for
     * @return array Will return the array data from the HTML data string
     */
    public function downloadTest($userID, $testID){
        $testData = array();
        parse_str($this->getData(self::DATAURL.'downloadTest.php', array('servernumber' => intval($this->getUniqueUser($userID)), 'testNumber' => $testID)), $testData);
        return $testData;
    }
    
    /**
     * Returns a summary of the test data from the given test ID and User ID
     * @param int $userID The user ID of the person you wish to get the test for
     * @param int $testID The Test ID for the test you wish to get the information for
     * @return array|boolean Will return the array data from the HTML data string if exists else will return false
     */
    public function getServerTestSummary($userID, $testID){
        if($this->getUniqueUser($userID)){
            $testData = array();
            parse_str($this->getData(self::DATAURL.'testOverview.php', array('userID' => intval($this->getUniqueUser($userID)), 'testNumber' => $testID)), $testData);
            return $testData;
        }
        return false;
    }
    
    /**
     * Checks the server to see if their have been any newer tests uploaded
     * @param int $userID The UserID of the person you are getting the test info for
     * @param int $testID The TestID you are checking to see if their are any newer tests
     * @param datetime $date The date when the last test was taken
     * @return boolean If their is newer test data on the server will return true else will return false
     */
    public function checkForNewerTest($userID, $testID, $date = NULL){
        if(!$date){$date = self::$user->getLastSyncDate();}
        if($this->checkForAnyNewer($userID, $date)){
            $serverInfo = $this->getServerTestSummary($userID, $testID);
            if($serverInfo['date'] > $date){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Checks to see if their are any newer tests available
     * @param int $userID The user Id you are checking for newer tests for
     * @param datetime $date The date when you last check for test to see if any newer one exist
     * @return boolean Returns true if newer tests exist else return false
     */
    public function checkForAnyNewer($userID, $date = NULL){
        if(is_numeric($this->newTests)){
            if($this->newTests == 1){return true;}else{return false;}
        }
        elseif($this->getUniqueUser($userID)){
            if(!$date){$date = self::$user->getLastSyncDate();}
            $testData = array();
            parse_str($this->getData(self::DATAURL.'checkNewer.php', array('userID' => intval($this->getUniqueUser($userID)), 'date' => $date)), $testData);
            if($testData['cant'] == 'true'){
                $this->newTests = 1;
                return true;
            }
            else{
                $this->newTests = 0;
                return false;
            }
        }
        else{
            $this->newTests = 0;
            return false;
        }
    }
    
    /**
     * Checks to see if there are any tests on the local server which are newer and need to be uploaded
     * @param int $userID This should be the user ID
     * @return boolean|string If there are newer test locally will return the numbers of newer tests e.g 1,3,7,10 else will return false
     */
    public function anyNewToupload($userID){
        if($this->getUniqueUser($userID)){
            for($testID = 1; $testID <= $this->numSyncTests; $testID++){
                $testInfo = $this->getLocalTest($userID, $testID);
                if($testInfo){
                    $tests[] = $testID;
                    $complete[] = $testInfo['complete'];
                }
            }
            if($tests){
                $testData = array();
                parse_str($this->getData(self::DATAURL.'anyToUpload.php', array('userID' => intval($this->getUniqueUser($userID)), 'tests' => implode(', ', $tests), 'completed' => implode(', ', $complete))), $testData);
                if($testData['newerTest'] == 'true'){return $testData['tests'];}
            }
        }
        return false;
    }
    
    /**
     * As the database are different the user must have a unique userID in Glen/Phils database to work with their products
     * @param int $userID This should be the user ID for the user in the local database which should have an associated one in Glen/Phils database based on the unique email address
     * @return int|boolean If an app user ID exists will return that userID else will return false
     */
    public function getUniqueUser($userID){
        if(self::$user->getAppID($userID)){
            return self::$user->getAppID($userID);
        }
        else{
            return $this->hasUserAccount();
        }
    }
    
    /**
     * Checks to see if the user has an assciated online account
     * @return boolean|int If no account exists will return false else will return the userID
     */
    public function hasUserAccount(){
        $testData = array();
        $userInfo = self::$user->getUserInfo();
        parse_str($this->getData(self::DATAURL.'userExists.php', array('email' => $userInfo['email'])), $testData);
        if($testData['exists'] == 'true'){
            self::$user->setAppID($testData['userID']);
            return $testData['userID'];
        }
        return false;
    }
    
    /**
     * Inserts any new test data into the local database for a given user and test
     * @param int $userID The userID you wish to get new test data for
     * @param int $testID The testID you wish to get new test data for
     * @return boolean If new test data is inserted into the database will return true else return false
     */
    public function insertTest($userID, $testID){
        $downloadTest = $this->downloadTest($userID, $testID);
        $localTest = $this->getLocalTest($userID, $testID);
        if($downloadTest['testdate'] > $localTest['complete']){
            $this->updateDataFormat($downloadTest);
            self::$db->delete(self::TESTTABLE, array('user_id' => $userID, 'test_id' => $testID, 'type' => $this->testType));
            return self::$db->insert(self::TESTTABLE, array('user_id' => $userID, 'questions' => serialize($this->questionsArray), 'answers' => serialize($this->answersArray), 'results' => serialize($this->resultsArray), 'test_id' => $testID, 'started' => $downloadTest['testdate'], 'complete' => $downloadTest['testdate'], 'time_taken' => $downloadTest['timeTaken'], 'totalscore' => $downloadTest['finalscore'], 'status' => $this->testStatus($downloadTest['finalscore']), 'type' => $this->testType));
        }
        return false;
    }
    
    /**
     * Creates information compatible with how Glen/Phil store their data to upload to their database
     * @param array $testInfo This is the information returned from my local database
     * @return void
     */
    protected function createUploadformat($testInfo){
        $userAnswers = unserialize(stripslashes($testInfo['answers']));
        $questions = unserialize(stripslashes($testInfo['questions']));
        foreach($userAnswers as $i => $answer){
            $this->questionID[$testInfo['test_id']][] = ($i + 1);
            $this->primQuestions[$testInfo['test_id']][] = $questions[$i];
            $this->qStatus[$testInfo['test_id']][] = $this->convertStatus($answer['status']);
            $this->flagged[$testInfo['test_id']][] = $this->convertFlagged($answer['flagged']);
            $this->convertAnswers($answer['answer'], $testInfo['test_id']);
        }
    }
    
    /**
     * Converts my status values to those which Glen/Phil use
     * @param int $status The status of the question in my database
     * @return int The status value compatible with Glen/Phil's database
     */
    protected function convertStatus($status){
        if($status == 4){return 1;} // Correct
        elseif($status == 3){return 2;} // Incorrect
        return 0; // Incomplete or unattempted
    }
    
    /**
     * Converts Glen/Phil's status values to those which I use
     * @param int $status The status value from Glen/Phil's database
     * @return int The status of for my database
     */
    public function getStatusValue($status){
        if($status == 1){return 4;} // Correct
        elseif($status == 2){return 3;} // Incorrect
        else{// Incomplete or unattempted
            $this->numIncomplete = intval($this->numIncomplete + 1);
            return 0;
        }
    }
    
    /**
     * Converts the numerical values for flagged values in my database to test values for Glen/Phil's database
     * @param int $flaggedStatus The flagged status for the question in my database
     * @return string If the question is flagged will return 'flagged' else will be empty
     */
    protected function convertFlagged($flaggedStatus){
        if($flaggedStatus == 1){return 'flagged';}
        return '';
    }
    
    /**
     * Converts the flagged string values to numerical values for /y database
     * @param string $flagged If the question is flagged this value should be set to 'flagged'
     * @return int If $flagged == 'flagged' will return 1 else returns 0
     */
    protected function getFlaggedStatus($flagged){
        if($flagged == 'flagged'){
            $this->numFlagged = intval($this->numFlagged + 1);
            return 1;
        }
        return 0;
    }
    
    /**
     * Converts the alphabetical string value in my database to numerous integer values for answers selected in Glen/Phil's database
     * @param string $answer This should be the string value of the answers selected for the questions
     * @param int $testID This should be the test ID for the answers as when uploading multiple causes issues if not set
     * @return void
     */
    protected function convertAnswers($answer, $testID){
        if(strpos($answer, 'A') !== false){$this->answers[$testID][1][] = 1;}else{$this->answers[$testID][1][] = 0;}
        if(strpos($answer, 'B') !== false){$this->answers[$testID][2][] = 1;}else{$this->answers[$testID][2][] = 0;}
        if(strpos($answer, 'C') !== false){$this->answers[$testID][3][] = 1;}else{$this->answers[$testID][3][] = 0;}
        if(strpos($answer, 'D') !== false){$this->answers[$testID][4][] = 1;}else{$this->answers[$testID][4][] = 0;}
        if(strpos($answer, 'E') !== false){$this->answers[$testID][5][] = 1;}else{$this->answers[$testID][5][] = 0;}
        if(strpos($answer, 'F') !== false){$this->answers[$testID][6][] = 1;}else{$this->answers[$testID][6][] = 0;}
    }
    
    /**
     * Converts the numerous integer values for answers selected in Glen/Phil's database to an alphabetical string
     * @param array $testdata This should be the test data return from Glen/Phil's database
     * @param int $arraykey This should be the array key value of the question (normally 0 - 49)
     * @return string Returns an alphabetical string for answers selected
     */
    protected function getAnswerString($testdata, $arraykey){
        $answer = '';
        if($testdata['mockanswer1'.$arraykey] == 1){$answer.='A';}
        if($testdata['mockanswer2'.$arraykey] == 1){$answer.='B';}
        if($testdata['mockanswer3'.$arraykey] == 1){$answer.='C';}
        if($testdata['mockanswer4'.$arraykey] == 1){$answer.='D';}
        if($testdata['mockanswer5'.$arraykey] == 1){$answer.='E';}
        if($testdata['mockanswer6'.$arraykey] == 1){$answer.='F';}
        return $answer;
    }
    
    /**
     * Checks to see if the users score is a pass or fail
     * @param int $score The number of questions the user answered correctly
     * @return int Will return 1 if the user passed else will return 2
     */
    protected function testStatus($score){
        if($score >= $this->passmark){return 1;}
        return 2;
    }
    
    /**
     * Returns the questions DSA category number
     * @param int $prim This should be the prim number of the current question
     * @return int Returns the DSA Category number of the current question
     */
    protected function getDSACat($prim){
        $dsacat = self::$db->select(self::QUESTIONSTABLE, array('prim' => $prim), array('dsacat'));
        return $dsacat['dsacat'];
    }
    
    /**
     * Converts the data to an test overview to insert into my database
     * @param array $testData This should be the test data array retrieved from Glen/Phil's database
     * @return void
     */
    protected function getTestResults($testData){
        for($i = 0; $i < $testData['count']; $i++){
             if($this->getStatusValue($testData['status'.$i]) == 4){$type = 'correct';}
             else{$type = 'incorrect';}
             
             $dsa = $this->getDSACat($testData['prim'.$i]);
             $this->resultsArray['dsa'][$dsa][$type] = (int)$this->resultsArray['dsa'][$dsa][$type] + 1;
        }
        
        $this->resultsArray['correct'] = $testData['finalscore'];
        $this->resultsArray['incorrect'] = ($testData['count'] - $testData['finalscore']);
        $this->resultsArray['incomplete'] = $this->numIncomplete;
        $this->resultsArray['flagged'] = $this->numFlagged;
        $this->resultsArray['numquestions'] = intval($testData['count']);
        $this->resultsArray['percent']['correct'] = round(($testData['finalscore'] / $testData['count']) * 100);
        $this->resultsArray['percent']['incorrect'] = round((($testData['count'] - $testData['finalscore']) / $testData['count']) * 100);
        $this->resultsArray['percent']['flagged'] = round(($this->numFlagged / $testData['count']) * 100);
        $this->resultsArray['percent']['incomplete'] = round(($this->numIncomplete / $testData['count']) * 100);
        if($this->testStatus($testData['finalscore']) == 1){
            $this->resultsArray['status'] = 'pass';
        }
        else{
            $this->resultsArray['status'] = 'fail';
        }
    }
    
    /**
     * Sets all of the variables ready to insert into my database in a converted format from the data in Glen/Phil's database to one compatible with mine
     * @param array $testdata This should be the test data array retreived from the HTML data of Glen/Phil's database
     * @return void
     */
    protected function updateDataFormat($testdata){
        for($i = 0; $i < $testdata['count']; $i++){
            $this->questionsArray[($i + 1)] = $testdata['prim'.$i];
            $this->answersArray[($i + 1)]['status'] = $this->getStatusValue($testdata['status'.$i]);
            $this->answersArray[($i + 1)]['answer'] = $this->getAnswerString($testdata, $i);
            $this->answersArray[($i + 1)]['flagged'] = $this->getFlaggedStatus($testdata['flagged'.$i]);
        }
        $this->getTestResults($testdata);
    }
    
    /**
     * Gets the HTML data from a given url
     * @param string $url The URL you wish to get the data from
     * @param array|null $postData If you wish to set $_POST data to send to the page set the array values as array('$key' => $value);
     * @param int $timeout The timeout for the URL to load in seconds
     * @return string The HTML data will be returned as a string
     */
    protected function getData($url, $postData = NULL, $timeout = 5){
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
       curl_setopt($ch, CURLOPT_URL, $url);
       if(is_array($postData)){
            foreach($postData as $key => $value) {
                $fields .= $key.'='.$value.'&';
            }
            rtrim($fields, '&');
            curl_setopt($ch, CURLOPT_POST, count($postData));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
       }
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
       return curl_exec($ch);
    }
}
