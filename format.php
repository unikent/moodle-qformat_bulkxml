<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The format.
 *
 * @package    qformat_bulkxml
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/format/xml/format.php');

class qformat_bulkxml extends qformat_xml 
{
	private $tempdir;

    public function provide_export() {
        return false;
    }

    public function can_import_file($file) {
        return $file->get_mimetype() == mimeinfo('type', '.zip');
    }

    public function mime_type() {
        return mimeinfo('type', '.zip');
    }

    /**
     * Does any post-processing that may be desired
     * Clean the temporary directory if a zip file was imported
     * @return bool success
     */
    public function importpostprocess() {
        if ($this->tempdir != '') {
            fulldelete($this->tempdir);
        }
        return true;
    }

    /**
     * Return complete file within an array, one item per line
     * @param string filename name of file
     * @return mixed contents array or false on failure
     */
    protected function readdata($filename) {
        if (!is_readable($filename)) {
        	return false;
        }

        // Extract the zip.
        $uniquecode = time() . uniqid();
        $this->tempdir = make_temp_directory('bulkxml_import/' . $uniquecode);

        // Ready the zip file!
        if (!copy($filename, $this->tempdir . '/data.zip')) {
            $this->error(get_string('cannotcopybackup', 'question'));
            fulldelete($this->tempdir);
            return false;
        }

        // Unzip it.
        if (unzip_file($this->tempdir . '/data.zip', '', false)) {
            $dir = $this->tempdir;
            if ((($handle = opendir($dir))) == false) {
                // The directory could not be opened.
                fulldelete($this->tempdir);
                return false;
            }

        	$data = array();

            // Loop through all directory entries, and construct two temporary arrays containing files and sub directories.
            while (false !== ($entry = readdir($handle))) {
                if (strpos($entry, 'xml') == strlen($entry) - 3) {
            		$data[] = parent::readdata($dir. '/' . $entry);
                }
            }

			return $data;
        } else {
            $this->error(get_string('cannotunzip', 'question'));
            fulldelete($this->tempdir);
        }

        return false;
    }

    /**
     * Parse the array of lines into an array of questions
     * this *could* burn memory - but it won't happen that much
     * so fingers crossed!
     * @param array of lines from the input file.
     * @param stdClass $context
     * @return array (of objects) question objects.
     */
    protected function readquestions($lines) {
        $data = array();
        foreach ($lines as $line) {
        	$data = array_merge($data, parent::readquestions($line));
	    }
        return $data;
    }
}
