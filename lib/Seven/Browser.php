<?php

namespace Seven;

/**
 * Seven\Browser
 *
 * Dispatcher for routing requests and includes browser helper methods
 *
 * @package    Seven
 * @author     Osman Üngür <osmanungur@gmail.com>
 * @copyright  2011 Osman Üngür
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    Version @package_version@
 * @since      Class available since Release 1.0.0
 * @link       http://github.com/osmanungur/seven
 */
class Browser {

    private function getRepositories() {
        $result = array();
        $values = \Seven\Config::getValues();
        foreach ($values['repositories'] as $repository) {
            $result[] = new Repository($repository['name'], $repository['url']);
        }
        return $result;
    }

    private function getRepositoryInfo($id) {
        $values = \Seven\Config::getValues();
        $repository = $values['repositories'][$id];
        return new Repository($repository['name'], $repository['url'], $repository['path'], $repository['username'], $repository['password']);
    }

    private function getRepositoryLog($repository_id, $limit = 10, $revision_start = false, $revision_end = false) {
        $log = new \Seven\Command\Log();
        $result = $log->setRepository($this->getRepositoryInfo($repository_id))
                        ->setLimit($limit)
                        ->setRevision($revision_start, $revision_end)
                        ->execute();
        $parser = new Seven\LogParser($result);
        return $parser->parse();
    }

    private function getPostRequest($key) {
        if (\array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        return false;
    }

    public function dispatch() {
        switch ($this->getPostRequest('action')) {
            case 'repositories':
                return \json_encode(
                        $this->getRepositories()
                );
                break;

            case 'log':
                return \json_encode(
                        $this->getRepositoryLog(
                                $this->getPostRequest('repository_id'),
                                $this->getPostRequest('limit'),
                                $this->getPostRequest('revision_start'),
                                $this->getPostRequest('revision_end')
                ));
                break;


            default:
                return \json_encode(array('message' => 'Wrong action given'));
                break;
        }
    }

}