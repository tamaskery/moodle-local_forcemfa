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

namespace local_forcemfa\local;

/**
 * Stores and restores a one-time site-local return URL in the user session.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class return_url_manager {
    /** Session property used for the root-relative return path. */
    private const SESSION_PROPERTY = 'local_forcemfa_returnurl';

    /**
     * Stores the path and query string from a URL, without its scheme or host.
     *
     * @param \moodle_url $url
     * @return bool Whether the URL was stored.
     */
    public function store(\moodle_url $url): bool {
        global $SESSION;

        try {
            // Moodle strips the current installation prefix, including subdirectory installs.
            $localurl = $url->out_as_local_url(false);
        } catch (\coding_exception) {
            return false;
        }

        if (!$this->is_valid_local_url($localurl)) {
            return false;
        }

        $SESSION->{self::SESSION_PROPERTY} = $localurl;
        return true;
    }

    /**
     * Removes and returns the stored one-time return URL.
     *
     * Invalid values are discarded.
     *
     * @return \moodle_url|null
     */
    public function take(): ?\moodle_url {
        global $SESSION;

        if (empty($SESSION->{self::SESSION_PROPERTY})) {
            return null;
        }

        $localurl = $SESSION->{self::SESSION_PROPERTY};
        unset($SESSION->{self::SESSION_PROPERTY});

        if (!is_string($localurl) || !$this->is_valid_local_url($localurl)) {
            return null;
        }

        return new \moodle_url($localurl);
    }

    /**
     * Clears a pending return URL.
     *
     * @return void
     */
    public function clear(): void {
        global $SESSION;

        unset($SESSION->{self::SESSION_PROPERTY});
    }

    /**
     * Checks that a URL is root-relative and cannot be interpreted as an external URL.
     *
     * @param string $url
     * @return bool
     */
    private function is_valid_local_url(string $url): bool {
        if ($url === '' || $url[0] !== '/' || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return false;
        }

        return clean_param($url, PARAM_LOCALURL) === $url;
    }
}
