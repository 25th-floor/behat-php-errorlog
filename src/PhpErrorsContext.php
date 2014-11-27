<?php

namespace TwentyFifth\Behat\PhpErrorLog;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Behat PHP Errors Context
 *
 * Setup a php error log file for each scenario and check afterwards if there was something logged
 *
 * works with selenium and non selenium, not yet with webapi.
 *
 * @author Thomas Subera <ts@25th-floor.com>
 * @package TwentyFifth\Behat\PhpErrorLog
 */
class PHPErrorsContext extends RawMinkContext
{
    CONST IGNORE_TAG = 'ignore-php-logging';

    /** @var  string Path to the Log Files */
    private $path;

    /** @var int maximum output size */
    private $maxSize = 1024;

    /** @var  string */
    private $url;

    /** @var  string cache filename for step */
    private $filename;

    /**
     * @param string $path Directory where to put the log files
     * @param string $url Url for Selenium Tests
     * @param int    $size Maximum FileSize which is outputted to buffer, defaults to 1024
     */
    function __construct($path, $url = null, $size = null)
    {
        $this->path = $path;

        if ($url !== null) {
            $this->url = $url;
        }

        if ($size !== null && $size > 0) {
            $this->maxSize = $size;
        }
    }

    /**
     * prepare php to log errors to a specific file
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     *
     * @throws \Exception if file could not be written
     */
    public function preparePHPErrorLogFile(BeforeScenarioScope $scope)
    {
        $this->filename = false;

        if ($this->ignoreScenario($scope)) {
            return;
        }

        $path = $this->getFilePath($this->createFileName($scope));

        // cleanup left overs
        if (file_exists($path)) {
            unlink($path);
        }

        // check for write access
        if (!touch($path)) {
            throw new \Exception("File $path could not be created!");
        }

        $this->filename = $path;

        if ($this->getMink()->getDefaultSessionName() == 'selenium2') {
            if ($this->url == null) {
                throw new \Exception('Need Url Configuration for selenium tests');
            }
            echo "Sending Php Error Log through " . $this->url . $path . PHP_EOL;
            $this->visitPath($this->url . urlencode($path));
            $this->filename = false; // don't do anything stupid
            return;
        }

        echo "Set PHP Error Log to " . $path . PHP_EOL;

//        ini_set("log_errors", 1);
//        ini_set("error_log", $path);

    }

    /**
     * need for non selenium tests
     *
     * @BeforeStep
     */
    public function sendHeader()
    {
        $filename = $this->filename;

        if (!$filename) {
            return;
        }

        $this->getSession()->setRequestHeader('X-BEHAT-PHP-ERROR-LOG', $filename);
    }

    /**
     * Checks if php has logged something and if it did, returns the output. But only if the file is not too big.
     * (Don't want to spam the output window)
     *
     * @AfterScenario
     *
     * @param AfterScenarioScope $scope
     *
     * @throws \Exception
     */
    public function lookForPHPErrors(AfterScenarioScope $scope)
    {
        // ignore scenario
        if ($this->ignoreScenario($scope)) {
            return;
        }

        $path = $this->getFilePath($this->createFileName($scope));

        // nothing in the file
        $filesize = filesize($path);
        if (0 == $filesize) {
            unlink($path); // cleanup
            return;
        }

        $message = sprintf(
            'PHP Error Log "%s" is not empty (%d bytes)',
            $path,
            $filesize
        );

        if ($filesize <= $this->maxSize) {
            echo 'PHP Error Log:' . PHP_EOL;

            readfile($path);
        }

        throw new \Exception($message);
    }

    /**
     * should we ignore the scenario
     *
     * @param ScenarioScope $scope
     *
     * @return bool
     */
    protected function ignoreScenario(ScenarioScope $scope)
    {
        // no need to do something if this is an empty scenario
        if (!$scope->getScenario()->hasSteps() && !$scope->getFeature()->hasBackground()) {
            return true;
        }

        // ignore scenarios with the ignore tag
        if ($scope->getScenario()->hasTag(self::IGNORE_TAG) || $scope->getFeature()->hasTag(self::IGNORE_TAG)) {
            return true;
        }

        return false;
    }

    /**
     * @param ScenarioScope $scope
     *
     * @return string
     */
    protected function createFileName(ScenarioScope $scope)
    {
        $filename = basename($scope->getFeature()->getFile()) . '.' . $scope->getScenario()->getLine();
        return urlencode(
            str_replace(' ', '_', strtolower($filename))
        ) . '.log';
    }

    /**
     * return the absolute log file path
     *
     * @param string $filename
     *
     * @return string
     */
    protected function getFilePath($filename)
    {
        $path = str_replace('//', '/', $this->path . DIRECTORY_SEPARATOR);

        return $path . $filename;
    }

}
