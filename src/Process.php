<?php
/**
 *
 * By: sayyassine
 * For the love of code
 */

namespace Gt\Daemon;
/**
 * Class Process
 */
class Process
{
    /**
     * @var string
     * Contains the command string
     */
    private $command = "" ;

    /**
     * @var array
     * The streams for the array input, output and errors
     */
    private $pipes = [];
    /**
     * @var null|resource
     * The thread within which the command will be ran
     */
    private $process = null ;

    /**
     * Process constructor.
     * @param string|null $command
     */
    public function __construct(string $command = null)
    {
        $this->command = "" ;
        $this->OS = explode(' ', strtolower(php_uname('s')))[0] ;
        if( $command ){
            $this->setCommand($command);
        }
    }


    /**
     * @throws \Exception
     * Runs the command in a concurrent thread.
     * Sets the input , output and errors streams.
     * Throws an exception if the command is empty or null.
     */
    public function run()
    {
        if( !$this->command ){
            throw new \Exception("Trying to run an empty command.");
        }

        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        $this->process = proc_open(escapeshellcmd($this->command), $descriptorspec, $this->pipes );

        if (!is_resource($this->process)) {
            throw new \Exception("An unexpected error occured while trying to run $this->command");
        }


        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);
        stream_set_blocking($this->pipes[0], 0);

    }

    /**
     * @return bool
     * @throws \Exception
     * is true as long as the thread is running
     */
    public function isAlive():bool
    {
        if( !is_resource($this->process))
            throw new \Exception("This function should be called after the run method.");

        return  proc_get_status($this->process)['running'];
    }

    /**
     * @return string
     * returns the command string
     */
    public function getCommand():string
    {
        return $this->command ;
    }

    /**
     * @param string $command
     * sets the command string
     * This needs to be called before the run method.
     *
     */
    public function setCommand(string $command)
    {
        $this->command = $command ;
    }

    /**
     * @return string
     * @throws \Exception
     * Returns all the outputs that are still not read
     */
    public function getOutput():string
    {

        if( !is_resource($this->process))
            throw new \Exception("This function should be called after the run method.");


        $output = fread($this->pipes[1], 1024);


        return $output ;
    }

    /**
     * @return string
     * @throws \Exception
     * Returns all the errors generated by the command that are still not read
     */
    public function getErrorOutput():string
    {


        if( !is_resource($this->process))
            throw new \Exception("This function should be called after the run method.");


        $output = fread($this->pipes[2], 1024);

        return $output ;
    }

    /**
     * closes the thread and the streams then returns the return code of the command
     * @return int
     */
    public function close():int
    {
        array_filter( $this->pipes, function($pipe){ return fclose($pipe); });
        return proc_close($this->process);
    }
}