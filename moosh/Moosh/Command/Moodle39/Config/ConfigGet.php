<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Config;
use Moosh\MooshCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;


class ConfigGet extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('get', 'config');

        $this->minArguments = 0;
        $this->maxArguments = 2;
    }

    public function execute()
    {
        global $CFG, $DB;

        $name = NULL;
        $plugin = NULL;

        if(isset($this->arguments[0])) {
            $plugin = trim($this->arguments[0]);
        }

        if(isset($this->arguments[1])) {
            $name =  trim($this->arguments[1]);
        }

        print_r(get_config($plugin,$name));
        echo "\n";

        if ($plugin == 'cex') {
            $file = fopen("./config/core.yml", "w");
            fwrite($file, (Yaml::dump((array)get_config('core', $name))));
            fclose($file);
            $manager = \core_plugin_manager::instance();
            $plugins = $manager->get_plugin_types();
            asort($plugins);
            $cutcharacters = strlen($CFG->dirroot);
            print_r($cutcharacters);
            foreach ($plugins as $type => $directory) {
                $pluginsinside = array();
                $finder = new Finder();
                $iterator = $finder->directories()->depth(0)->in($directory);
                foreach ($iterator as $dir) {
                    $filename = $type . '_' . $dir->getBasename();
                    $config_array = (array)get_config($filename, $name);
                    if (!(array_key_exists('version', $config_array) && count($config_array) == 1 || count($config_array) == 0)) {
                        $file = fopen("./config/" . $filename . ".yml", "w");
                        fwrite($file, (Yaml::dump((array)get_config($filename, $name))));
                        fclose($file);
                    }
                }
            }
            echo "\n";
        }

        if ($plugin == 'cim') { 
            $importArray =  array_values(array_diff(((Array)scandir('./config')), array('..', '.')));
            foreach ($importArray as $item) {
                $file = fopen('./config/' . $item, 'r');
                $file = file_get_contents('./config/' . $item,);
                $filename_without_ext = preg_replace('/\\.[^.\\s]{3,4}$/', '', $item);        
                      
                foreach (Yaml::parse($file) as $name => $value){
                    set_config($name, $value, $filename_without_ext != 'core' ? $filename_without_ext : NULL);
                }
            }   
        }}

    protected function getArgumentsHelp()
    {
        $ret = "\n\nARGUMENTS:";
        $ret .= "\n\t";
        $ret .= "<plugin> <name>";

        return $ret;
    }
}
