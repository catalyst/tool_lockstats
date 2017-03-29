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
 * Proxy lock factory, detail table.
 *
 * @package    tool_lockstats
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lockstats\table;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

use moodle_url;
use stdClass;
use table_sql;

/**
 * Proxy lock factory, detail table.
 *
 * @package    tool_lockstats
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class detail extends table_sql {
    /** @var int Incrementing table id. */
    private static $autoid = 0;

    /** @var int The taskid. */
    private $taskid;

    /**
     * Constructor
     * @param moodle_url $baseurl
     * @param integer $taskid
     * @param string|null $id to be used by the table, autogenerated if null.
     */
    public function __construct($baseurl, $taskid, $id = null) {

        $id = (is_null($id) ? self::$autoid++ : $id);

        $this->taskid = $taskid;

        parent::__construct('tool_lockstats_detail' . $id);

        $columns = array(
            'task'  => get_string('table_resource', 'tool_lockstats'),
            'duration'  => get_string('table_duration', 'tool_lockstats'),
            'lockcount' => get_string('table_lockcount', 'tool_lockstats'),
            'host'      => get_string('table_host', 'tool_lockstats'),
            'gained'    => get_string('table_gained', 'tool_lockstats'),
            'released'  => get_string('table_released', 'tool_lockstats'),
            'pid'       => get_string('table_pid', 'tool_lockstats'),
        );

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->define_baseurl($baseurl);

        $this->set_attribute('class', 'generaltable admintable');
        $this->set_attribute('cellspacing', '0');

        $this->sortable(true, 'released', SORT_DESC);

        $this->collapsible(false);

        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        $select = '*';
        $from = '{tool_lockstats_history}';
        $where = 'taskid = :taskid';
        $params = ['taskid' => $taskid];

        $this->set_sql($select, $from, $where, $params);
    }

    /**
     * Download the data.
     */
    public function download() {
        global $DB;

        $params = ['taskid' => $this->taskid];

        $sql = "SELECT COUNT(1)
                  FROM {tool_lockstats_history}
                 WHERE taskid = :taskid";

        $total = $DB->count_records_sql($sql, $params);
        $this->out($total, false);
    }

    /**
     * The time the lock was gained.
     *
     * @param stdClass $values
     * @return string
     */
    public function col_gained($values) {
        if ($this->is_downloading()) {
            return $values->gained;
        }

        return userdate($values->gained, '%Y-%m-%d %H:%M:%S');
    }

    /**
     * The time the lock was released.
     *
     * @param stdClass $values
     * @return string
     */
    public function col_released($values) {
        if ($this->is_downloading()) {
            return $values->released;
        }

        return userdate($values->released, '%Y-%m-%d %H:%M:%S');
    }

    /**
     * The time the lock was held for.
     *
     * @param stdClass $values
     * @return string
     */
    public function col_duration($values) {
        $lockcount = $values->lockcount;
        $duration = $values->duration;

        if ($lockcount > 0) {
            $duration = $values->duration / $values->lockcount;
        }

        if ($this->is_downloading()) {
            return $duration;
        }

        if ($duration > 0) {
            $data = explode(' ', format_time($duration));
            if (count($data) == 2) {
                return sprintf('%.4g %s', $data[0], $data[1]);
            }
        }

        return $duration;

    }

}
