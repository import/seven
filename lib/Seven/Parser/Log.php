<?php

namespace Seven;

/**
 * LogParser
 *
 * Parser for subversion log output
 *
 * @package    Seven
 * @author     Osman Üngür <osmanungur@gmail.com>
 * @copyright  2011 Osman Üngür
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    Version @package_version@
 * @since      Class available since Release 1.0.0
 * @link       http://github.com/osmanungur/seven
 */
class LogParser {
    /**
     * XML output tags and attributes
     */
    const XML_ROOT_TAG = 'log';
    const XML_ITEM_TAG = 'logentry';
    const XML_REVISION_ATTRIBUTE = 'revision';
    const XML_AUTHOR_TAG = 'author';
    const XML_DATE_TAG = 'date';
    const XML_MESSAGE_TAG = 'msg';
    const XML_PATHS_TAG = 'paths';
    const XML_PATH_TAG = 'path';
    const XML_ACTION_ATTRIBUTE = 'action';
    const XML_KIND_ATTRIBUTE = 'kind';

    private $fileActions = array('A' => 'Added', 'D' => 'Deleted', 'M' => 'Modified', 'C' => 'Conflicted', 'G' => 'Merged', 'R' => 'Replaced');
    private $xml;

    /**
     *
     * @param string $xml
     */
    public function __construct($xml) {
        $this->setXml($xml);
        $this->load();
    }

    /**
     * Returns explanation of action
     *
     * @param string $action
     * @return string
     */
    private function getFileAction($action) {
        return $this->fileActions[(string) $action];
    }

    /**
     * Loads XML as SimpleXMLElement for parsing
     *
     * @return LogParser
     */
    private function load() {
        libxml_use_internal_errors(true);
        $xml = \simplexml_load_string($this->getXml());
        if (!libxml_get_errors()) {
            $this->setXml($xml);
        } else {
            libxml_clear_errors();
            $this->setXml(false);
        }
        return $this;
    }

    /**
     * Parses XML document for outputting JSON output
     *
     * @return array
     */
    public function parse() {
        if (!$this->getXml()) {
            return false;
        }
        $result = new \ArrayObject();
        foreach ($this->getXml() as $commits) {
            $changedfiles = new \ArrayObject();
            if ($commits->{self::XML_PATHS_TAG}->{self::XML_PATH_TAG}) {
                foreach ($commits->{self::XML_PATHS_TAG}->{self::XML_PATH_TAG} as $files) {
                    $changedfiles->append(array(
                        'filename' => (string) $files,
                        'action' => $this->getFileAction($files[self::XML_ACTION_ATTRIBUTE])
                    ));
                }
            }

            $result->append(new \ArrayObject(array(
                        'revision' => (int) $commits[self::XML_REVISION_ATTRIBUTE],
                        'author' => (string) $commits->{self::XML_AUTHOR_TAG},
                        'message' => (string) trim($commits->{self::XML_MESSAGE_TAG}),
                        'date' => (string) $this->time2str($commits->{self::XML_DATE_TAG}),
                        'files' => \iterator_to_array($changedfiles)
                    )));
        };
        return \iterator_to_array($result);
    }

    /**
     * Returns an English representation of a past date within the last month, stolen from http://ejohn.org/files/pretty.js
     *
     * @param string $ts
     * @return string
     */
    private function time2str($ts) {
        if (!ctype_digit($ts))
            $ts = strtotime($ts);

        $diff = time() - $ts;
        if ($diff == 0)
            return 'now';
        elseif ($diff > 0) {
            $day_diff = floor($diff / 86400);
            if ($day_diff == 0) {
                if ($diff < 60)
                    return 'just now';
                if ($diff < 120)
                    return '1 minute ago';
                if ($diff < 3600)
                    return floor($diff / 60) . ' minutes ago';
                if ($diff < 7200)
                    return '1 hour ago';
                if ($diff < 86400)
                    return floor($diff / 3600) . ' hours ago';
            }
            if ($day_diff == 1)
                return 'Yesterday';
            if ($day_diff < 7)
                return $day_diff . ' days ago';
            if ($day_diff < 31)
                return ceil($day_diff / 7) . ' weeks ago';
            if ($day_diff < 60)
                return 'last month';
            return date('F Y', $ts);
        }
        return date('F Y', $ts);
    }

    /**
     *
     * @return string|SimpleXMLElement
     */
    private function getXml() {
        return $this->xml;
    }

    /**
     *
     * @param string|SimpleXMLElement $xml
     * @return LogParser 
     */
    private function setXml($xml) {
        $this->xml = $xml;
        return $this;
    }

}